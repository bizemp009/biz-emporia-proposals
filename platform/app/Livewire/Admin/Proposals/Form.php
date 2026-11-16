<?php

namespace App\Livewire\Admin\Proposals;

use App\Livewire\Concerns\EditsProposalContent;
use App\Models\Client;
use App\Models\Proposal;
use App\Models\ProposalRoiMetric;
use App\Models\ProposalTemplate;
use App\Models\Setting;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Layout;
use Livewire\Component;
use Livewire\WithFileUploads;

#[Layout('layouts.admin')]
class Form extends Component
{
    use EditsProposalContent;
    use WithFileUploads;

    public ?Proposal $proposal = null;

    // 1. Client metadata
    public ?int $clientId = null;
    public string $companyName = '';
    public string $brandName = '';
    public string $contactPerson = '';
    public string $email = '';
    public string $website = '';
    public string $industry = '';

    // 2. Branding & URL
    public string $title = '';
    public string $autoTitle = '';
    public string $titlePattern = ProposalTemplate::DEFAULT_TITLE;
    public string $slug = '';
    public bool $slugTouched = false;

    // Validity
    public string $expiresAt = '';

    public ?int $templateId = null;
    public string $templateName = '';

    public function mount(?Proposal $proposal = null): void
    {
        $this->roi = ProposalRoiMetric::DEFAULTS;
        $this->currency = Setting::get('default_currency', 'INR');
        $this->expiresAt = now()->addDays((int) Setting::get('default_validity_days', 30))->toDateString();

        if ($proposal?->exists) {
            $this->loadProposal($proposal);

            return;
        }

        $this->proposal = null;
        $this->roadmap = $this->defaultRoadmap(3);

        // "New proposal" from the Clients page pre-selects that client.
        if (($cid = request()->integer('client')) && Client::whereKey($cid)->exists()) {
            $this->clientId = $cid;
            $this->updatedClientId($cid);
        }

        if ($tpl = request()->integer('template')) {
            $this->templateId = $tpl;
            $this->applyTemplate();
        }
    }

    private function loadProposal(Proposal $proposal): void
    {
        $proposal->load(['client', 'services', 'roiMetrics', 'caseStudies']);
        $this->proposal = $proposal;

        $c = $proposal->client;
        $this->clientId = $c->id;
        $this->companyName = $c->company_name;
        $this->brandName = (string) $c->brand_name;
        $this->contactPerson = $c->contact_person;
        $this->email = $c->email;
        $this->website = (string) $c->website;
        $this->industry = (string) $c->industry;

        $this->title = $proposal->title;
        $this->slug = $proposal->slug;
        $this->slugTouched = true;
        $this->collaboratorName = (string) $proposal->collaborator_name;
        $this->executiveSummary = (string) $proposal->executive_summary;
        $this->auditReport = (string) $proposal->audit_report;
        $this->screenshots = $proposal->audit_screenshots ?? [];
        $this->tenureMonths = $proposal->tenure_months ?: 3;
        $this->currency = $proposal->currency;
        $this->bookingUrl = (string) $proposal->booking_url;
        $this->expiresAt = (string) $proposal->expires_at?->toDateString();
        $this->caseStudyIds = $proposal->caseStudies->pluck('id')->map(fn ($id) => (string) $id)->all();
        $this->roadmap = $this->formRoadmap($proposal->roadmap_milestones);

        $this->lines = $proposal->services->map(fn ($s) => [
            'id' => $s->id,
            'service_id' => $s->service_id,
            'name' => $s->name,
            'description' => (string) $s->description,
            'deliverables' => implode("\n", $s->deliverables ?? []),
            'price' => (float) $s->price,
            'billing_type' => $s->billing_type,
            'is_optional' => $s->is_optional,
            'is_selected' => $s->is_selected,
        ])->all();

        if ($proposal->roiMetrics) {
            $this->roi = collect(ProposalRoiMetric::DEFAULTS)
                ->map(fn ($v, $k) => $proposal->roiMetrics->{$k} ?? $v)->all();
        }
    }

    // ---------------------------------------------------------------- reactive helpers

    public function updatedClientId($value): void
    {
        if (! $value) {
            return;
        }
        $client = Client::find($value);
        if (! $client) {
            return;
        }
        $this->companyName = $client->company_name;
        $this->brandName = (string) $client->brand_name;
        $this->contactPerson = $client->contact_person;
        $this->email = $client->email;
        $this->website = (string) $client->website;
        $this->industry = (string) $client->industry;
        $this->autoFill();
    }

    public function updatedCompanyName(): void
    {
        $this->autoFill();
    }

    public function updatedBrandName(): void
    {
        $this->autoFill();
    }

    public function updatedSlug(): void
    {
        $this->slugTouched = true;
        $this->slug = Str::slug($this->slug);
    }

    public function regenerateSlug(): void
    {
        $this->slugTouched = false;
        $this->autoFill();
    }

    private function clientName(): string
    {
        return $this->brandName ?: $this->companyName;
    }

    private function autoFill(): void
    {
        $name = $this->clientName();
        if (! $this->slugTouched && $name !== '') {
            $prefix = trim((string) Setting::get('slug_prefix', ''), '-');
            $this->slug = Proposal::uniqueSlug(trim($prefix.'-'.$name, '-'), $this->proposal?->id);
        }
        // Keep the auto-title in sync until someone types their own.
        if ($name !== '' && ($this->title === '' || $this->title === $this->autoTitle)) {
            $this->title = $this->autoTitle = str_replace('{client}', $name, $this->titlePattern);
        }
    }

    // ---------------------------------------------------------------- templates

    public function applyTemplate(): void
    {
        $tpl = ProposalTemplate::find($this->templateId);
        if (! $tpl) {
            return;
        }

        $this->fillContentFromTemplate($tpl);
        $this->titlePattern = $tpl->title_pattern ?: ProposalTemplate::DEFAULT_TITLE;
        if ($tpl->validity_days) {
            $this->expiresAt = now()->addDays($tpl->validity_days)->toDateString();
        }
        $this->autoFill();

        $this->dispatch('notify', message: "Template \"{$tpl->name}\" applied.");
    }

    /** Saves everything on the form except the client details. */
    public function saveAsTemplate(): void
    {
        $this->validate(['templateName' => 'required|string|max:120'] + $this->contentRules(), $this->contentMessages());

        $name = $this->clientName();
        $pattern = match (true) {
            $this->title === '' => ProposalTemplate::DEFAULT_TITLE,
            $name !== '' && str_contains($this->title, $name) => str_replace($name, '{client}', $this->title),
            default => $this->title,
        };

        $validity = null;
        if ($this->expiresAt && ($days = (int) today()->diffInDays(Carbon::parse($this->expiresAt), false)) > 0) {
            $validity = $days;
        }

        DB::transaction(function () use ($pattern, $validity) {
            $tpl = ProposalTemplate::create($this->templateContentPayload() + [
                'name' => $this->templateName,
                'description' => $this->industry ? "Based on {$this->industry} proposal" : null,
                'title_pattern' => $pattern,
                'validity_days' => $validity,
            ]);

            $shots = ProposalTemplate::copyScreenshots($this->screenshots, "templates/{$tpl->id}");
            foreach ($this->newScreenshots as $upload) {
                $shots[] = ['path' => $upload->store("templates/{$tpl->id}", 'public'), 'caption' => ''];
            }
            $tpl->update(['audit_screenshots' => $shots]);
        });

        $this->templateName = '';
        $this->dispatch('notify', message: 'Saved as template.');
        $this->dispatch('template-saved');
    }

    // ---------------------------------------------------------------- persistence

    protected function rules(): array
    {
        return [
            'clientId' => 'nullable|exists:clients,id',
            'companyName' => 'required|string|max:255',
            'brandName' => 'nullable|string|max:255',
            'contactPerson' => 'required|string|max:255',
            'email' => 'required|email|max:255',
            'website' => 'nullable|url|max:255',
            'industry' => 'nullable|string|max:255',
            'title' => 'required|string|max:255',
            'slug' => ['required', 'alpha_dash', 'max:120', Rule::unique('proposals', 'slug')->ignore($this->proposal?->id)],
            'expiresAt' => 'nullable|date',
        ] + $this->contentRules();
    }

    protected function messages(): array
    {
        return $this->contentMessages();
    }

    public function save(bool $send = false): void
    {
        if ($this->proposal?->status === 'accepted') {
            $this->dispatch('notify', message: 'Signed proposals are locked. Duplicate it to make changes.', type: 'error');

            return;
        }

        $this->validate();

        $proposal = DB::transaction(function () use ($send) {
            $clientData = [
                'company_name' => $this->companyName,
                'brand_name' => $this->brandName ?: null,
                'contact_person' => $this->contactPerson,
                'email' => $this->email,
                'website' => $this->website ?: null,
                'industry' => $this->industry ?: null,
            ];
            $client = $this->clientId ? tap(Client::findOrFail($this->clientId))->update($clientData) : Client::create($clientData);

            $isNew = ! $this->proposal;
            $proposal = $this->proposal ?? new Proposal(['status' => 'draft']);

            $proposal->fill([
                'client_id' => $client->id,
                'title' => $this->title,
                'slug' => $this->slug,
                'collaborator_name' => $this->collaboratorName ?: null,
                'executive_summary' => $this->executiveSummary ?: null,
                'audit_report' => $this->auditReport ?: null,
                'roadmap_milestones' => $this->roadmapPayload(),
                'tenure_months' => $this->tenureMonths,
                'currency' => strtoupper($this->currency),
                'booking_url' => $this->bookingUrl ?: null,
                'expires_at' => $this->expiresAt ? Carbon::parse($this->expiresAt)->endOfDay() : null,
            ]);

            // Extending the validity of an expired link re-opens it.
            if ($proposal->status === 'expired' && ! $proposal->isPastExpiry()) {
                $proposal->status = $proposal->viewed_at ? 'viewed' : ($proposal->sent_at ? 'sent' : 'draft');
            }
            if ($send && $proposal->status === 'draft') {
                $proposal->status = 'sent';
                $proposal->sent_at = now();
            }
            $proposal->save();

            // Screenshots: images that came from a template (or another proposal) are copied into
            // this proposal's folder; only files this proposal owns are ever deleted.
            $dir = "proposals/{$proposal->id}";
            $keptPaths = collect($this->screenshots)->pluck('path')->all();
            foreach (collect($proposal->getOriginal('audit_screenshots') ?? [])->pluck('path')->diff($keptPaths) as $removed) {
                if (str_starts_with($removed, $dir.'/')) {
                    Storage::disk('public')->delete($removed);
                }
            }
            $shots = ProposalTemplate::copyScreenshots($this->screenshots, $dir);
            foreach ($this->newScreenshots as $upload) {
                $shots[] = ['path' => $upload->store($dir, 'public'), 'caption' => ''];
            }
            $proposal->audit_screenshots = array_values($shots);
            $proposal->save();

            // Line items: update / create / delete.
            $keepIds = [];
            foreach (array_values($this->lines) as $i => $line) {
                $optional = (bool) $line['is_optional'];
                $data = [
                    'service_id' => $line['service_id'] ?: null,
                    'name' => $line['name'],
                    'description' => $line['description'] ?: null,
                    'deliverables' => $this->splitLines($line['deliverables'] ?? ''),
                    'price' => (float) $line['price'],
                    'billing_type' => $line['billing_type'],
                    'is_optional' => $optional,
                    'is_selected' => $optional ? (bool) $line['is_selected'] : true,
                    'sort_order' => $i,
                ];
                $row = ! empty($line['id']) ? $proposal->services()->whereKey($line['id'])->first() : null;
                if ($row) {
                    $row->update($data);
                } else {
                    $row = $proposal->services()->create($data);
                }
                $keepIds[] = $row->id;
            }
            $proposal->services()->whereNotIn('id', $keepIds)->delete();

            $proposal->roiMetrics()->updateOrCreate(['proposal_id' => $proposal->id], $this->roi);
            $proposal->caseStudies()->sync(array_map('intval', $this->caseStudyIds));

            $proposal->load('services');
            $proposal->update(['total_amount' => $proposal->pricing()['contract_value']]);

            $proposal->log($isNew ? 'created' : 'updated');
            if ($send && $proposal->wasChanged('sent_at')) {
                $proposal->log('sent');
            }

            return $proposal;
        });

        session()->flash('notify', $send ? 'Proposal generated — the client link is live.' : 'Proposal saved.');
        $this->redirectRoute('admin.proposals.edit', $proposal, navigate: true);
    }

    // ---------------------------------------------------------------- view

    public function render()
    {
        return view('livewire.admin.proposals.form', $this->contentViewData() + [
            'clients' => Client::orderBy('company_name')->get(['id', 'company_name', 'brand_name']),
            'templates' => ProposalTemplate::orderBy('name')->get(['id', 'name']),
            'activities' => $this->proposal?->activities()->limit(25)->get() ?? collect(),
        ])->title($this->proposal ? 'Edit proposal' : 'Create proposal');
    }
}
