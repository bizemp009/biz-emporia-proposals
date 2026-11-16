<?php

namespace App\Livewire\Concerns;

use App\Models\CaseStudy;
use App\Models\Proposal;
use App\Models\ProposalRoiMetric;
use App\Models\ProposalTemplate;
use App\Models\Service;

/**
 * Everything in a proposal that isn't client-specific: summary, audit, screenshots,
 * service lines, ROI defaults, roadmap, case studies, strategist, currency, booking link.
 * Shared by the proposal editor and the template editor so both stay in sync.
 * The using component must also use Livewire\WithFileUploads.
 */
trait EditsProposalContent
{
    public string $collaboratorName = '';
    public string $executiveSummary = '';
    public string $auditReport = '';
    public array $screenshots = [];      // [{path, caption}]
    public array $newScreenshots = [];   // temporary uploads
    public array $lines = [];
    public array $roi = [];
    public array $roadmap = [];
    public int $tenureMonths = 3;
    public string $currency = 'INR';
    public string $bookingUrl = '';
    public array $caseStudyIds = [];

    protected function defaultRoadmap(int $months): array
    {
        return collect(range(1, max(1, min($months, 12))))
            ->map(fn ($m) => ['title' => "Month {$m}", 'items' => ''])
            ->all();
    }

    public function updatedTenureMonths($value): void
    {
        $value = max(1, min((int) $value, 24));
        $this->tenureMonths = $value;
        // Grow / shrink the roadmap to match the tenure (only drops empty trailing phases).
        while (count($this->roadmap) < min($value, 12)) {
            $this->roadmap[] = ['title' => 'Month '.(count($this->roadmap) + 1), 'items' => ''];
        }
        while (count($this->roadmap) > $value && trim(end($this->roadmap)['items']) === '') {
            array_pop($this->roadmap);
        }
    }

    // ---------------------------------------------------------------- services

    public function addCatalogService(int $serviceId): void
    {
        $s = Service::findOrFail($serviceId);
        $this->lines[] = [
            'id' => null,
            'service_id' => $s->id,
            'name' => $s->name,
            'description' => (string) $s->description,
            'deliverables' => implode("\n", $s->deliverables ?? []),
            'price' => (float) $s->default_price,
            'billing_type' => $s->billing_type,
            'is_optional' => $s->is_optional,
            'is_selected' => ! $s->is_optional,
        ];
    }

    public function addCustomLine(): void
    {
        $this->lines[] = [
            'id' => null, 'service_id' => null, 'name' => '', 'description' => '', 'deliverables' => '',
            'price' => 0, 'billing_type' => 'monthly', 'is_optional' => false, 'is_selected' => true,
        ];
    }

    public function removeLine(int $i): void
    {
        unset($this->lines[$i]);
        $this->lines = array_values($this->lines);
    }

    public function moveLine(int $i, int $dir): void
    {
        $j = $i + $dir;
        if (isset($this->lines[$i], $this->lines[$j])) {
            [$this->lines[$i], $this->lines[$j]] = [$this->lines[$j], $this->lines[$i]];
        }
    }

    /** Stored line (deliverables as array) → editable form line (deliverables as text). */
    protected function formLine(array $l): array
    {
        return [
            'id' => null,
            'service_id' => $l['service_id'] ?? null,
            'name' => (string) ($l['name'] ?? ''),
            'description' => (string) ($l['description'] ?? ''),
            'deliverables' => implode("\n", $l['deliverables'] ?? []),
            'price' => (float) ($l['price'] ?? 0),
            'billing_type' => $l['billing_type'] ?? 'monthly',
            'is_optional' => (bool) ($l['is_optional'] ?? false),
            'is_selected' => (bool) ($l['is_selected'] ?? true),
        ];
    }

    /** Editable form lines → stored shape. */
    protected function linesPayload(): array
    {
        return collect($this->lines)->values()->map(function ($l) {
            $optional = (bool) $l['is_optional'];

            return [
                'service_id' => $l['service_id'] ? (int) $l['service_id'] : null,
                'name' => $l['name'],
                'description' => ($l['description'] ?? '') ?: null,
                'deliverables' => $this->splitLines($l['deliverables'] ?? ''),
                'price' => (float) $l['price'],
                'billing_type' => $l['billing_type'],
                'is_optional' => $optional,
                'is_selected' => $optional ? (bool) $l['is_selected'] : true,
            ];
        })->all();
    }

    // ---------------------------------------------------------------- roadmap

    public function addMilestone(): void
    {
        $this->roadmap[] = ['title' => 'Phase '.(count($this->roadmap) + 1), 'items' => ''];
    }

    public function removeMilestone(int $i): void
    {
        unset($this->roadmap[$i]);
        $this->roadmap = array_values($this->roadmap);
    }

    protected function formRoadmap(?array $milestones): array
    {
        return collect($milestones ?? [])
            ->map(fn ($m) => ['title' => $m['title'] ?? '', 'items' => implode("\n", $m['items'] ?? [])])
            ->all() ?: $this->defaultRoadmap($this->tenureMonths);
    }

    protected function roadmapPayload(): array
    {
        return collect($this->roadmap)->map(fn ($m) => [
            'title' => trim($m['title']),
            'items' => $this->splitLines($m['items'] ?? ''),
        ])->values()->all();
    }

    protected function splitLines(?string $text): array
    {
        return collect(preg_split('/\r\n|\r|\n/', (string) $text))
            ->map(fn ($l) => trim(ltrim(trim($l), '-•*')))
            ->filter()->values()->all();
    }

    // ---------------------------------------------------------------- screenshots

    public function updatedNewScreenshots(): void
    {
        $this->validate(['newScreenshots.*' => 'image|max:5120']);
    }

    public function removeScreenshot(int $i): void
    {
        unset($this->screenshots[$i]);
        $this->screenshots = array_values($this->screenshots);
    }

    public function removeUpload(int $i): void
    {
        unset($this->newScreenshots[$i]);
        $this->newScreenshots = array_values($this->newScreenshots);
    }

    // ---------------------------------------------------------------- templates

    /** Load every content field from a template. */
    protected function fillContentFromTemplate(ProposalTemplate $tpl): void
    {
        $this->collaboratorName = (string) $tpl->collaborator_name;
        $this->executiveSummary = (string) $tpl->executive_summary;
        $this->auditReport = (string) $tpl->audit_report;
        $this->screenshots = $tpl->audit_screenshots ?? [];
        $this->newScreenshots = [];
        $this->tenureMonths = $tpl->tenure_months ?: 3;
        $this->roadmap = $this->formRoadmap($tpl->roadmap_milestones);
        $this->roi = array_merge(ProposalRoiMetric::DEFAULTS, $tpl->roi_defaults ?? []);
        $this->caseStudyIds = array_map('strval', $tpl->case_study_ids ?? []);
        $this->lines = array_map(fn ($l) => $this->formLine($l), $tpl->lines());
        if ($tpl->currency) {
            $this->currency = $tpl->currency;
        }
        $this->bookingUrl = (string) $tpl->booking_url;

        $this->dispatch('rich-text-load', field: 'executiveSummary', html: $this->executiveSummary);
        $this->dispatch('rich-text-load', field: 'auditReport', html: $this->auditReport);
    }

    /** Content fields in the template's storage shape (screenshots are handled by the caller). */
    protected function templateContentPayload(): array
    {
        return [
            'collaborator_name' => $this->collaboratorName ?: null,
            'executive_summary' => $this->executiveSummary ?: null,
            'audit_report' => $this->auditReport ?: null,
            'roadmap_milestones' => $this->roadmapPayload(),
            'service_lines' => $this->linesPayload(),
            'service_ids' => collect($this->lines)->pluck('service_id')->filter()->map(fn ($id) => (int) $id)->values()->all(),
            'case_study_ids' => array_map('intval', $this->caseStudyIds),
            'roi_defaults' => $this->roi,
            'tenure_months' => $this->tenureMonths,
            'currency' => strtoupper($this->currency),
            'booking_url' => $this->bookingUrl ?: null,
        ];
    }

    // ---------------------------------------------------------------- validation & view

    protected function contentRules(): array
    {
        return [
            'collaboratorName' => 'nullable|string|max:255',
            'executiveSummary' => 'nullable|string|max:100000',
            'auditReport' => 'nullable|string|max:100000',
            'screenshots.*.caption' => 'nullable|string|max:255',
            'newScreenshots.*' => 'image|max:5120',
            'lines' => 'array',
            'lines.*.name' => 'required|string|max:255',
            'lines.*.description' => 'nullable|string|max:5000',
            'lines.*.deliverables' => 'nullable|string|max:5000',
            'lines.*.price' => 'required|numeric|min:0|max:99999999',
            'lines.*.billing_type' => 'required|in:one_time,monthly,quarterly',
            'lines.*.is_optional' => 'boolean',
            'lines.*.is_selected' => 'boolean',
            'roi.default_budget_or_spend' => 'required|numeric|min:0|max:99999999',
            'roi.default_working_hours' => 'required|numeric|min:0|max:999999',
            'roi.target_conversion_rate' => 'required|numeric|min:0|max:100',
            'roi.projected_lift_multiplier' => 'required|numeric|min:0.1|max:99',
            'roi.baseline_monthly_traffic' => 'required|integer|min:0|max:100000000',
            'roi.cost_per_click' => 'required|numeric|min:0.01|max:999999',
            'roi.average_order_value' => 'required|numeric|min:0|max:99999999',
            'roi.hourly_rate' => 'required|numeric|min:0|max:999999',
            'roadmap.*.title' => 'required|string|max:120',
            'roadmap.*.items' => 'nullable|string|max:5000',
            'tenureMonths' => 'required|integer|min:1|max:24',
            'currency' => 'required|string|max:8',
            'bookingUrl' => 'nullable|url|max:500',
            'caseStudyIds' => 'array',
            'caseStudyIds.*' => 'exists:case_studies,id',
        ];
    }

    protected function contentMessages(): array
    {
        return [
            'lines.*.name.required' => 'Each service needs a name.',
            'roadmap.*.title.required' => 'Each roadmap phase needs a title.',
        ];
    }

    /** Catalog, case studies and the live price preview used by the shared section partials. */
    protected function contentViewData(): array
    {
        $draft = collect($this->lines)->map(fn ($l) => (object) [
            'price' => (float) ($l['price'] ?? 0),
            'billing_type' => $l['billing_type'] ?? 'monthly',
            'is_selected' => ! ($l['is_optional'] ?? false) || ($l['is_selected'] ?? false),
        ])->where('is_selected', true);

        $preview = new Proposal(['tenure_months' => $this->tenureMonths, 'currency' => $this->currency]);

        return [
            'catalog' => Service::where('is_active', true)->orderBy('category')->orderBy('name')->get()->groupBy(fn ($s) => $s->category ?: 'Other'),
            'caseStudies' => CaseStudy::where('is_published', true)->orderBy('title')->get(),
            'pricing' => $preview->pricing($draft),
            'preview' => $preview,
        ];
    }
}
