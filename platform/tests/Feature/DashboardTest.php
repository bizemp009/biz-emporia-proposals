<?php

namespace Tests\Feature;

use App\Livewire\Admin\Clients;
use App\Livewire\Admin\Profile;
use App\Livewire\Admin\Proposals\Analytics;
use App\Livewire\Admin\Proposals\Form;
use App\Livewire\Admin\Proposals\Index;
use App\Livewire\Admin\Proposals\Insights;
use App\Models\Client;
use App\Models\Proposal;
use App\Models\User;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Livewire\Livewire;
use Tests\TestCase;

class DashboardTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(DatabaseSeeder::class);
    }

    private function admin(): User
    {
        return User::first();
    }

    private function acme(): Proposal
    {
        return Proposal::where('slug', 'biz-emporia-acme-living')->firstOrFail();
    }

    private function draft(): Proposal
    {
        return Proposal::where('slug', 'biz-emporia-ledgerflow')->firstOrFail();
    }

    public function test_every_admin_page_renders(): void
    {
        $this->actingAs($this->admin());

        foreach ([
            route('admin.proposals.index'),
            route('admin.proposals.create'),
            route('admin.proposals.create', ['client' => Client::first()->id]),
            route('admin.proposals.edit', $this->acme()),
            route('admin.clients'),
            route('admin.clients', ['new' => 1]),
            route('admin.services', ['new' => 1]),
            route('admin.case-studies', ['new' => 1]),
            route('admin.templates'),
            route('admin.settings'),
            route('admin.profile'),
        ] as $url) {
            $this->get($url)->assertOk()->assertSee('Skip to content', false);
        }
    }

    public function test_insights_panel_renders_with_charts_and_attention_items(): void
    {
        // A proposal viewed 5 days ago with no decision → "Follow up".
        $this->acme()->update(['status' => 'viewed', 'viewed_at' => now()->subDays(5)]);
        // A proposal signed 4 days after sending → time-to-sign stats.
        $signed = $this->draft();
        $signed->update(['status' => 'accepted', 'sent_at' => now()->subDays(6), 'signed_at' => now()->subDays(2), 'total_amount' => 100000]);
        $signed->log('signed', ['signer_name' => 'Jane', 'signer_title' => 'CEO']);

        Livewire::withoutLazyLoading()->actingAs($this->admin())->test(Insights::class)
            ->assertSee('Needs attention')
            ->assertSee('Follow up')
            ->assertSee('Acme Living')
            ->assertSee('Signed by Jane (CEO)');

        Livewire::withoutLazyLoading()->actingAs($this->admin())->test(Analytics::class)
            ->assertSee('Proposal funnel')
            ->assertSee('Won value by month')
            ->assertSee('Win rate by strategist')
            ->assertSee('4')       // average days to sign
            ->assertViewHas('charts', fn ($c) => $c['sign_avg'] === 4.0 && $c['monthly_total'] === 100000.0
                && $c['strategists'][0]['label'] === 'Arjun' && $c['strategists'][0]['value'] === 100);
    }

    public function test_list_can_be_sorted_and_filtered(): void
    {
        $lw = Livewire::actingAs($this->admin())->test(Index::class);

        $lw->call('sortBy', 'value')->assertSet('sort', 'value')->assertSet('dir', 'desc');
        $lw->call('sortBy', 'value')->assertSet('dir', 'asc');
        $lw->call('applySort', 'client', 'asc')->assertSet('sort', 'client');
        $lw->call('sortBy', 'nonsense')->assertSet('sort', 'client');
        foreach (['status', 'activity', 'expiry', 'created', 'updated'] as $col) {
            $lw->call('sortBy', $col)->assertOk();
        }

        $lw->set('strategist', 'Arjun')
            ->assertSee('LedgerFlow')
            ->assertDontSee('Acme Retail');

        $lw->call('clearFilters')->assertSet('strategist', '')
            ->set('min', '999999999')
            ->assertSee('No proposals match these filters');

        $lw->call('clearFilters')
            ->set('from', now()->addDay()->toDateString())
            ->assertSee('No proposals match these filters')
            ->set('from', 'not-a-date') // ignored, not an error
            ->assertSee('LedgerFlow');
    }

    public function test_extend_expiry_reopens_expired_proposals_and_refuses_signed_ones(): void
    {
        $p = $this->acme();
        $p->update(['status' => 'expired', 'expires_at' => now()->subDay(), 'viewed_at' => now()->subDays(3)]);

        Livewire::actingAs($this->admin())->test(Index::class)->call('extend', $p->id, 7);

        $p->refresh();
        $this->assertSame('viewed', $p->status);
        $this->assertSame(now()->addDays(7)->toDateString(), $p->expires_at->toDateString());
        $this->assertSame('extended', $p->activities()->first()->event);

        // From a future date it adds on top of that date.
        $p->extendExpiry(30);
        $this->assertSame(now()->addDays(37)->toDateString(), $p->fresh()->expires_at->toDateString());

        $p->update(['status' => 'accepted']);
        $this->assertFalse($p->fresh()->extendExpiry(7));
    }

    public function test_bulk_actions(): void
    {
        $acme = $this->acme();
        $draft = $this->draft();
        $ids = [(string) $acme->id, (string) $draft->id];

        $lw = Livewire::actingAs($this->admin())->test(Index::class);

        $lw->set('selected', $ids)->call('bulkMarkSent')->assertSet('selected', []);
        $this->assertSame('sent', $draft->fresh()->status);

        $before = $acme->fresh()->expires_at->copy();
        $lw->set('selected', $ids)->call('bulkExtend', 30);
        $this->assertSame($before->addDays(30)->toDateString(), $acme->fresh()->expires_at->toDateString());

        $lw->set('selected', [(string) $draft->id])->call('bulkDelete');
        $this->assertNull(Proposal::find($draft->id));
        $this->assertNotNull(Proposal::find($acme->id));
    }

    public function test_csv_export_respects_filters_and_neutralises_formulas(): void
    {
        $this->acme()->client->update(['company_name' => '=HYPERLINK("http://evil")']);

        $csv = $this->actingAs($this->admin())
            ->get(route('admin.proposals.export', ['strategist' => 'Srishti']))
            ->assertOk()
            ->assertHeader('content-type', 'text/csv; charset=UTF-8')
            ->streamedContent();

        $this->assertStringStartsWith("\xEF\xBB\xBF", $csv);
        $this->assertStringContainsString("'=HYPERLINK", $csv);
        $this->assertStringNotContainsString('LedgerFlow', $csv);

        $clients = $this->get(route('admin.clients.export'))->assertOk()->streamedContent();
        $this->assertStringContainsString('Company,Brand,Contact', $clients);
    }

    public function test_exports_require_login(): void
    {
        $this->get(route('admin.proposals.export'))->assertRedirect(route('login'));
    }

    public function test_profile_and_password_can_be_updated(): void
    {
        $admin = $this->admin();

        Livewire::actingAs($admin)->test(Profile::class)
            ->set('name', 'New Name')
            ->set('email', 'new@example.com')
            ->call('saveProfile')
            ->assertHasNoErrors()
            ->set('currentPassword', 'wrong-password')
            ->set('password', 'brand-new-pass')
            ->set('passwordConfirmation', 'brand-new-pass')
            ->call('updatePassword')
            ->assertHasErrors(['currentPassword']);

        $this->assertSame('new@example.com', $admin->fresh()->email);

        $admin->update(['password' => 'old-password-1']);
        Livewire::actingAs($admin->fresh())->test(Profile::class)
            ->set('currentPassword', 'old-password-1')
            ->set('password', 'brand-new-pass')
            ->set('passwordConfirmation', 'mismatch')
            ->call('updatePassword')
            ->assertHasErrors(['password'])
            ->set('passwordConfirmation', 'brand-new-pass')
            ->call('updatePassword')
            ->assertHasNoErrors();

        $this->assertTrue(Hash::check('brand-new-pass', $admin->fresh()->password));
    }

    public function test_new_proposal_can_start_from_a_client(): void
    {
        $client = Client::first();

        Livewire::withQueryParams(['client' => $client->id])
            ->actingAs($this->admin())->test(Form::class)
            ->assertSet('clientId', $client->id)
            ->assertSet('companyName', $client->company_name);
    }

    public function test_clients_page_opens_new_client_form_from_query_string(): void
    {
        Livewire::withQueryParams(['new' => 1])
            ->actingAs($this->admin())->test(Clients::class)
            ->assertSet('editing', true)
            ->assertSet('editId', null);
    }
}
