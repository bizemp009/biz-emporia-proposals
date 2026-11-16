<?php

namespace Tests\Feature;

use App\Livewire\Admin\Proposals\Form;
use App\Livewire\Admin\Templates;
use App\Livewire\Admin\Templates\Combine;
use App\Livewire\Admin\Templates\Edit;
use App\Models\Proposal;
use App\Models\ProposalTemplate;
use App\Models\Service;
use App\Models\User;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;
use Tests\TestCase;

class TemplateTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(DatabaseSeeder::class);
        Storage::fake('public');
        $this->actingAs(User::first());
    }

    private function adsTemplate(): ProposalTemplate
    {
        $google = Service::where('name', 'Google Ads Management')->firstOrFail();
        $meta = Service::where('name', 'Meta Ads Management')->firstOrFail();

        return ProposalTemplate::create([
            'name' => 'Ads',
            'title_pattern' => 'Paid Growth Plan for {client}',
            'collaborator_name' => 'Aman',
            'currency' => 'USD',
            'executive_summary' => '<p>Ads summary</p>',
            'audit_report' => '<p>Ads audit</p>',
            'roadmap_milestones' => [['title' => 'Month 1', 'items' => ['Launch campaigns']], ['title' => 'Month 2', 'items' => ['Scale winners']]],
            'service_lines' => [
                ['service_id' => $google->id, 'name' => 'Google Ads', 'description' => null, 'deliverables' => ['Search'], 'price' => 40000, 'billing_type' => 'monthly', 'is_optional' => false, 'is_selected' => true],
                ['service_id' => $meta->id, 'name' => 'Meta Ads', 'description' => null, 'deliverables' => [], 'price' => 30000, 'billing_type' => 'monthly', 'is_optional' => false, 'is_selected' => true],
            ],
            'case_study_ids' => [],
            'roi_defaults' => ['default_budget_or_spend' => 200000, 'cost_per_click' => 12],
            'tenure_months' => 2,
            'validity_days' => 14,
        ]);
    }

    public function test_save_as_template_keeps_everything_except_client_details(): void
    {
        $seo = Service::where('name', 'Technical SEO Audit & Fixes')->firstOrFail();

        $lw = Livewire::test(Form::class)
            ->set('companyName', 'Globex Corp')
            ->set('brandName', 'Globex')
            ->set('contactPerson', 'Hank Scorpio')
            ->set('email', 'hank@globex.example')
            ->set('website', 'https://globex.example')
            ->set('title', 'SEO Sprint for Globex')
            ->set('collaboratorName', 'Srishti')
            ->set('currency', 'USD')
            ->set('bookingUrl', 'https://cal.com/biz')
            ->set('expiresAt', now()->addDays(10)->toDateString())
            ->set('executiveSummary', '<p>Summary</p>')
            ->set('newScreenshots', [UploadedFile::fake()->image('audit.png')])
            ->call('addCatalogService', $seo->id)
            ->set('lines.0.price', 52000)
            ->set('lines.0.description', 'Custom copy')
            ->call('addCustomLine')
            ->set('lines.1.name', 'Bespoke landing page')
            ->set('lines.1.price', 9000)
            ->set('lines.1.billing_type', 'one_time')
            ->set('templateName', 'SEO Sprint')
            ->call('saveAsTemplate')
            ->assertHasNoErrors();

        $tpl = ProposalTemplate::where('name', 'SEO Sprint')->firstOrFail();
        $this->assertSame('SEO Sprint for {client}', $tpl->title_pattern);
        $this->assertSame('Srishti', $tpl->collaborator_name);
        $this->assertSame('USD', $tpl->currency);
        $this->assertSame('https://cal.com/biz', $tpl->booking_url);
        $this->assertSame(10, $tpl->validity_days);
        $this->assertCount(2, $tpl->service_lines);
        $this->assertEquals(52000, $tpl->service_lines[0]['price']);
        $this->assertSame('Custom copy', $tpl->service_lines[0]['description']);
        $this->assertSame('Bespoke landing page', $tpl->service_lines[1]['name']);
        $this->assertCount(1, $tpl->audit_screenshots);
        Storage::disk('public')->assertExists($tpl->audit_screenshots[0]['path']);

        // No client details anywhere in the template.
        $json = json_encode($tpl->toArray());
        foreach (['Globex', 'Hank', 'hank@globex', 'globex.example'] as $clientBit) {
            $this->assertStringNotContainsString($clientBit, $json);
        }

        // Applying it to a new client brings everything back, with the new client's name in the title.
        Livewire::withQueryParams(['template' => $tpl->id])->test(Form::class)
            ->set('brandName', 'Initech')
            ->assertSet('title', 'SEO Sprint for Initech')
            ->assertSet('collaboratorName', 'Srishti')
            ->assertSet('currency', 'USD')
            ->assertSet('bookingUrl', 'https://cal.com/biz')
            ->assertSet('expiresAt', now()->addDays(10)->toDateString())
            ->assertSet('lines.0.price', 52000.0)
            ->assertSet('lines.1.name', 'Bespoke landing page')
            ->assertCount('screenshots', 1)
            ->assertSet('companyName', '');
    }

    public function test_proposal_copies_template_screenshots_so_removing_them_is_safe(): void
    {
        Storage::disk('public')->put('templates/99/shot.png', 'img');
        $tpl = $this->adsTemplate();
        $tpl->update(['audit_screenshots' => [['path' => 'templates/99/shot.png', 'caption' => 'Before']]]);

        Livewire::withQueryParams(['template' => $tpl->id])->test(Form::class)
            ->set('companyName', 'Globex Corp')
            ->set('contactPerson', 'Hank')
            ->set('email', 'hank@globex.example')
            ->call('save')->assertHasNoErrors();

        $p = Proposal::latest('id')->first();
        $this->assertStringStartsWith("proposals/{$p->id}/", $p->audit_screenshots[0]['path']);
        $this->assertSame('Before', $p->audit_screenshots[0]['caption']);

        Livewire::test(Form::class, ['proposal' => $p])->call('removeScreenshot', 0)->call('save')->assertHasNoErrors();
        Storage::disk('public')->assertExists('templates/99/shot.png');
    }

    public function test_template_editor_updates_all_sections(): void
    {
        $tpl = ProposalTemplate::firstOrFail();

        Livewire::test(Edit::class, ['template' => $tpl])
            ->assertSet('name', $tpl->name)
            ->set('name', 'E-com SEO')
            ->set('titlePattern', 'SEO plan for {client}')
            ->set('executiveSummary', '<p>New summary</p>')
            ->set('lines.0.price', 1234)
            ->call('removeLine', 1)
            ->call('addMilestone')
            ->set('roi.cost_per_click', 9)
            ->set('validityDays', 21)
            ->call('save')
            ->assertHasNoErrors()
            ->assertRedirect(route('admin.templates.edit', $tpl));

        $tpl->refresh();
        $this->assertSame('E-com SEO', $tpl->name);
        $this->assertSame('SEO plan for {client}', $tpl->title_pattern);
        $this->assertSame('<p>New summary</p>', $tpl->executive_summary);
        $this->assertEquals(1234, $tpl->service_lines[0]['price']);
        $this->assertCount(4, $tpl->service_lines);
        $this->assertCount(4, $tpl->roadmap_milestones);
        $this->assertEquals(9, $tpl->roi_defaults['cost_per_click']);
        $this->assertSame(21, $tpl->validity_days);
    }

    public function test_new_template_can_be_created_from_scratch(): void
    {
        Livewire::test(Edit::class)
            ->set('name', 'Blank start')
            ->call('addCustomLine')
            ->set('lines.0.name', 'Retainer')
            ->set('lines.0.price', 1000)
            ->call('save')
            ->assertHasNoErrors();

        $this->assertSame('Retainer', ProposalTemplate::where('name', 'Blank start')->first()->service_lines[0]['name']);
    }

    public function test_combine_ecom_seo_with_ads(): void
    {
        $seo = ProposalTemplate::firstOrFail(); // E-commerce SEO + CRO (legacy: catalog ids only)
        $ads = $this->adsTemplate();

        $lw = Livewire::withQueryParams(['a' => $seo->id, 'b' => $ads->id])->test(Combine::class);

        // Meta Ads is in both → B's duplicate isn't ticked by default.
        $this->assertContains('b:0', $lw->get('serviceKeys'));
        $this->assertNotContains('b:1', $lw->get('serviceKeys'));

        $lw->set('pick', [
            'summary' => 'both', 'audit' => 'a', 'screenshots' => 'none', 'roadmap' => 'merge',
            'roi' => 'b', 'tenure' => 'b', 'settings' => 'b',
        ])
            ->set('serviceKeys', ['a:0', 'b:0', 'b:1']) // SEO audit + both ad services
            ->set('name', 'E-com Ads')
            ->call('create')
            ->assertHasNoErrors();

        $new = ProposalTemplate::where('name', 'E-com Ads')->firstOrFail();
        $this->assertSame(['Technical SEO Audit & Fixes', 'Google Ads', 'Meta Ads'], array_column($new->service_lines, 'name'));
        $this->assertStringContainsString('Ads summary', $new->executive_summary);
        $this->assertStringStartsWith(trim($seo->executive_summary), $new->executive_summary);
        $this->assertSame($seo->audit_report, $new->audit_report);
        $this->assertEquals(200000, $new->roi_defaults['default_budget_or_spend']);
        $this->assertSame(2, $new->tenure_months);
        $this->assertSame('Paid Growth Plan for {client}', $new->title_pattern);
        $this->assertSame('USD', $new->currency);
        $this->assertSame(14, $new->validity_days);
        // Phase-by-phase merge: phase 1 holds A's items plus B's.
        $this->assertContains('Launch campaigns', $new->roadmap_milestones[0]['items']);
        $this->assertGreaterThan(1, count($new->roadmap_milestones[0]['items']));

        // Sources untouched.
        $this->assertNull($seo->fresh()->service_lines);
        $this->assertSame('Ads', $ads->fresh()->name);
    }

    public function test_combine_requires_two_different_templates(): void
    {
        $tpl = ProposalTemplate::firstOrFail();
        Livewire::test(Combine::class)
            ->set('aId', $tpl->id)->set('bId', $tpl->id)->set('name', 'X')
            ->call('create')
            ->assertHasErrors(['bId']);
    }

    public function test_duplicate_and_delete_template(): void
    {
        $tpl = $this->adsTemplate();
        Livewire::test(Templates::class)->call('duplicate', $tpl->id);
        $copy = ProposalTemplate::where('name', 'Ads (Copy)')->firstOrFail();
        $this->assertEquals($tpl->service_lines, $copy->service_lines);

        Livewire::test(Templates::class)->call('delete', $copy->id);
        $this->assertNull($copy->fresh());
        $this->assertNotNull($tpl->fresh());
    }

    public function test_template_pages_render(): void
    {
        $tpl = ProposalTemplate::firstOrFail();
        $ads = $this->adsTemplate();
        $this->get(route('admin.templates'))->assertOk()->assertSee('Combine templates');
        $this->get(route('admin.templates.create'))->assertOk();
        $this->get(route('admin.templates.edit', $tpl))->assertOk()->assertSee($tpl->name);
        $this->get(route('admin.templates.combine', ['a' => $tpl->id, 'b' => $ads->id]))->assertOk()->assertSee('Where each section comes from');
    }
}
