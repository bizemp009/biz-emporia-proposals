<?php

namespace Tests\Feature;

use App\Livewire\Admin\Proposals\Form;
use App\Livewire\Admin\Proposals\Index;
use App\Livewire\Client\ProposalViewer;
use App\Models\Proposal;
use App\Models\Service;
use App\Models\User;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class ProposalFlowTest extends TestCase
{
    use RefreshDatabase;

    private const SIGNATURE = 'data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAYAAAAfFcSJAAAADUlEQVR42mNkYPhfDwAChwGA60e6kgAAAABJRU5ErkJggg==';

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(DatabaseSeeder::class);
    }

    private function admin(): User
    {
        return User::first();
    }

    private function demo(): Proposal
    {
        return Proposal::where('slug', 'biz-emporia-acme-living')->firstOrFail();
    }

    public function test_pricing_uses_tenure_for_recurring_items(): void
    {
        $p = $this->demo()->load('services');
        $pricing = $p->pricing();

        // Selected: SEO audit 45k (one-time) + SEO retainer 60k + CRO 40k + Meta 30k (monthly) + Analytics 15k monthly? (optional, not selected)
        $this->assertSame(45000.0, $pricing['one_time']);
        $this->assertSame(130000.0, $pricing['monthly']);
        $this->assertSame(45000.0 + 130000.0 * 3, $pricing['contract_value']);
        $this->assertSame('₹4,35,000', $p->money($pricing['contract_value']));
    }

    public function test_admin_can_generate_a_proposal(): void
    {
        $services = Service::whereIn('name', ['Technical SEO Audit & Fixes', 'Meta Ads Management'])->get();

        $lw = Livewire::actingAs($this->admin())->test(Form::class)
            ->set('companyName', 'Globex Corp')
            ->set('brandName', 'Globex')
            ->assertSet('slug', 'biz-emporia-globex')
            ->assertSet('title', 'Growth Proposal for Globex')
            ->set('contactPerson', 'Hank Scorpio')
            ->set('email', 'hank@globex.example')
            ->set('collaboratorName', 'Srishti')
            ->set('executiveSummary', '<div>Hello <script>alert(1)</script><strong>world</strong></div>');

        foreach ($services as $s) {
            $lw->call('addCatalogService', $s->id);
        }

        $lw->call('save', true)->assertHasNoErrors()->assertRedirect();

        $p = Proposal::where('slug', 'biz-emporia-globex')->with('services', 'roiMetrics', 'client')->firstOrFail();
        $this->assertSame('sent', $p->status);
        $this->assertNotNull($p->sent_at);
        $this->assertSame('Globex Corp', $p->client->company_name);
        $this->assertCount(2, $p->services);
        $this->assertNotNull($p->roiMetrics);
        // Meta is optional and not pre-selected → only the 45k audit counts.
        $this->assertEquals(45000, $p->total_amount);

        // Public page renders and strips the script tag.
        auth()->logout();
        $this->get('/proposal/biz-emporia-globex')
            ->assertOk()
            ->assertSee('<strong>world</strong>', false)
            ->assertDontSee('<script>alert(1)</script>', false);
    }

    public function test_validation_blocks_bad_input(): void
    {
        Livewire::actingAs($this->admin())->test(Form::class)
            ->set('companyName', '')
            ->set('slug', 'biz-emporia-acme-living') // already taken
            ->call('save')
            ->assertHasErrors(['companyName' => 'required', 'contactPerson', 'email', 'slug' => 'unique']);
    }

    public function test_first_client_view_marks_viewed_but_staff_preview_does_not(): void
    {
        $p = $this->demo();

        Livewire::actingAs($this->admin())->test(ProposalViewer::class, ['slug' => $p->slug])->assertSet('preview', true);
        $this->assertSame('sent', $p->fresh()->status);

        auth()->logout();
        Livewire::test(ProposalViewer::class, ['slug' => $p->slug])->assertSet('preview', false);
        $p->refresh();
        $this->assertSame('viewed', $p->status);
        $this->assertNotNull($p->viewed_at);
        $this->assertTrue($p->activities()->where('event', 'viewed')->exists());
    }

    public function test_client_can_toggle_only_optional_services(): void
    {
        $p = $this->demo()->load('services');
        $analytics = $p->services->firstWhere('name', 'Analytics & Reporting Dashboard'); // optional, off
        $audit = $p->services->firstWhere('name', 'Technical SEO Audit & Fixes');          // mandatory

        $lw = Livewire::test(ProposalViewer::class, ['slug' => $p->slug])
            ->assertSet('monthlyInvestment', round(435000 / 3, 2))
            ->call('toggleService', $analytics->id)
            ->assertSet('monthlyInvestment', round((435000 + 15000 * 3) / 3, 2))
            ->call('toggleService', $audit->id); // ignored

        $this->assertContains($audit->id, $lw->get('selectedServices'));
        $this->assertTrue($analytics->fresh()->is_selected);
        $this->assertEquals(480000, $p->fresh()->total_amount);
    }

    public function test_client_can_sign_and_proposal_locks(): void
    {
        $p = $this->demo();

        Livewire::test(ProposalViewer::class, ['slug' => $p->slug])
            ->call('signProposal')
            ->assertHasErrors(['signerTitle', 'signatureData', 'agree'])
            ->set('signerName', 'Priya Sharma')
            ->set('signerTitle', 'CMO')
            ->set('agree', true)
            ->set('signatureData', 'javascript:alert(1)')
            ->call('signProposal')
            ->assertHasErrors(['signatureData'])
            ->set('signatureData', self::SIGNATURE)
            ->call('signProposal')
            ->assertHasNoErrors()
            ->assertSee('Thank you, Priya Sharma');

        $p->refresh();
        $this->assertSame('accepted', $p->status);
        $this->assertSame('CMO', $p->signer_title);
        $this->assertNotNull($p->signed_at);
        $this->assertEquals(435000, $p->total_amount);

        // Locked: toggles do nothing, admin form refuses to save.
        $analytics = $p->services()->where('name', 'Analytics & Reporting Dashboard')->first();
        Livewire::test(ProposalViewer::class, ['slug' => $p->slug])->call('toggleService', $analytics->id);
        $this->assertFalse($analytics->fresh()->is_selected);

        Livewire::actingAs($this->admin())->test(Form::class, ['proposal' => $p])
            ->set('title', 'Changed')->call('save');
        $this->assertNotSame('Changed', $p->fresh()->title);
    }

    public function test_staff_preview_cannot_sign(): void
    {
        $p = $this->demo();
        Livewire::actingAs($this->admin())->test(ProposalViewer::class, ['slug' => $p->slug])
            ->set('signerName', 'X')->set('signerTitle', 'Y')->set('agree', true)->set('signatureData', self::SIGNATURE)
            ->call('signProposal');
        $this->assertSame('sent', $p->fresh()->status);
    }

    public function test_client_can_decline(): void
    {
        $p = $this->demo();
        Livewire::test(ProposalViewer::class, ['slug' => $p->slug])
            ->set('declineReason', 'Budget not approved')
            ->call('decline');
        $p->refresh();
        $this->assertSame('rejected', $p->status);
        $this->assertSame('Budget not approved', $p->rejection_reason);
    }

    public function test_expired_and_draft_links(): void
    {
        $this->get('/proposal/biz-emporia-ledgerflow')->assertNotFound(); // draft
        $this->get('/proposal/does-not-exist')->assertNotFound();

        $p = $this->demo();
        $p->update(['expires_at' => now()->subDay()]);
        $this->get('/proposal/'.$p->slug)->assertOk()->assertSee('This proposal expired');
        $this->assertSame('expired', $p->fresh()->status);

        $this->artisan('proposals:expire')->assertSuccessful();
    }

    public function test_duplicate_and_delete_from_dashboard(): void
    {
        $p = $this->demo();
        Livewire::actingAs($this->admin())->test(Index::class)
            ->assertSee('Acme Retail Pvt Ltd')
            ->call('duplicate', $p->id)
            ->assertRedirect();

        $copy = Proposal::where('slug', 'biz-emporia-acme-living-copy')->with('services', 'roiMetrics', 'caseStudies')->firstOrFail();
        $this->assertSame('draft', $copy->status);
        $this->assertCount(6, $copy->services);
        $this->assertNotNull($copy->roiMetrics);
        $this->assertCount(2, $copy->caseStudies);

        Livewire::actingAs($this->admin())->test(Index::class)->call('delete', $copy->id);
        $this->assertModelMissing($copy);
    }

    public function test_pdf_downloads(): void
    {
        $p = $this->demo();
        $this->get("/proposal/{$p->slug}/pdf")->assertOk()->assertHeader('content-type', 'application/pdf');
        $this->actingAs($this->admin())->get("/admin/proposals/{$p->id}/pdf")->assertOk();
    }

    public function test_template_application(): void
    {
        Livewire::actingAs($this->admin())->test(Form::class)
            ->set('templateId', 1)
            ->call('applyTemplate')
            ->assertCount('lines', 5)
            ->assertSet('roi.baseline_monthly_traffic', 22000)
            ->assertCount('roadmap', 3);
    }
}
