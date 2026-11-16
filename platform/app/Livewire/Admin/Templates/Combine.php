<?php

namespace App\Livewire\Admin\Templates;

use App\Models\CaseStudy;
use App\Models\ProposalRoiMetric;
use App\Models\ProposalTemplate;
use Illuminate\Support\Facades\DB;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

/**
 * Build a new template from two existing ones, choosing section by section
 * where each part comes from (e.g. E-com SEO + Ads → E-com Ads).
 * The two source templates are never modified.
 */
#[Layout('layouts.admin')]
#[Title('Combine templates')]
class Combine extends Component
{
    public ?int $aId = null;
    public ?int $bId = null;

    public string $name = '';
    public string $description = '';

    /** Per-section source: a | b | both (text), a | b | merge | append (roadmap), a | b | both | none (screenshots). */
    public array $pick = [
        'summary' => 'both',
        'audit' => 'a',
        'screenshots' => 'a',
        'roadmap' => 'merge',
        'roi' => 'b',
        'tenure' => 'b',
        'settings' => 'a',
    ];

    /** Selected service lines as "a:0", "b:2" … */
    public array $serviceKeys = [];
    public array $caseStudyIds = [];

    public function mount(): void
    {
        $this->aId = request()->integer('a') ?: null;
        $this->bId = request()->integer('b') ?: null;
        $this->resetSelections();
    }

    public function updatedAId(): void
    {
        $this->resetSelections();
    }

    public function updatedBId(): void
    {
        $this->resetSelections();
    }

    /** Default: every service (skipping B's duplicates of A's catalog services) and every case study from both. */
    private function resetSelections(): void
    {
        [$a, $b] = $this->sources();
        $this->serviceKeys = [];
        $seen = [];
        foreach (['a' => $a, 'b' => $b] as $side => $tpl) {
            foreach ($tpl?->lines() ?? [] as $i => $line) {
                $sid = $line['service_id'] ?? null;
                if ($sid && in_array($sid, $seen, true)) {
                    continue;
                }
                if ($sid) {
                    $seen[] = $sid;
                }
                $this->serviceKeys[] = "{$side}:{$i}";
            }
        }
        $this->caseStudyIds = collect([$a?->case_study_ids, $b?->case_study_ids])
            ->flatten()->filter()->unique()->map(fn ($id) => (string) $id)->values()->all();

        if ($a && $b && $this->name === '') {
            $this->description = "Combined from {$a->name} + {$b->name}";
        }
    }

    /** @return array{0: ?ProposalTemplate, 1: ?ProposalTemplate} */
    private function sources(): array
    {
        return [
            $this->aId ? ProposalTemplate::find($this->aId) : null,
            $this->bId ? ProposalTemplate::find($this->bId) : null,
        ];
    }

    private static function joinHtml(?string $x, ?string $y): ?string
    {
        $parts = array_filter([trim((string) $x), trim((string) $y)]);

        return $parts ? implode("\n", $parts) : null;
    }

    private function pickText(string $key, ?string $a, ?string $b): ?string
    {
        return match ($this->pick[$key]) {
            'a' => $a,
            'b' => $b,
            default => self::joinHtml($a, $b),
        };
    }

    private function combinedRoadmap(ProposalTemplate $a, ProposalTemplate $b): array
    {
        $ra = $a->roadmap_milestones ?? [];
        $rb = $b->roadmap_milestones ?? [];

        return match ($this->pick['roadmap']) {
            'a' => $ra,
            'b' => $rb,
            'append' => array_values(array_merge($ra, $rb)),
            // Phase-by-phase: Month 1 of A + Month 1 of B, and so on.
            default => collect(range(0, max(count($ra), count($rb)) - 1))
                ->filter(fn ($i) => isset($ra[$i]) || isset($rb[$i]))
                ->map(fn ($i) => [
                    'title' => $ra[$i]['title'] ?? $rb[$i]['title'] ?? 'Phase '.($i + 1),
                    'items' => array_values(array_unique(array_merge($ra[$i]['items'] ?? [], $rb[$i]['items'] ?? []))),
                ])->values()->all(),
        };
    }

    public function create(): void
    {
        $this->validate([
            'aId' => 'required|exists:proposal_templates,id',
            'bId' => 'required|different:aId|exists:proposal_templates,id',
            'name' => 'required|string|max:120',
            'description' => 'nullable|string|max:255',
            'pick.summary' => 'in:a,b,both',
            'pick.audit' => 'in:a,b,both',
            'pick.screenshots' => 'in:a,b,both,none',
            'pick.roadmap' => 'in:a,b,merge,append',
            'pick.roi' => 'in:a,b',
            'pick.tenure' => 'in:a,b',
            'pick.settings' => 'in:a,b',
            'serviceKeys' => 'array',
            'serviceKeys.*' => 'regex:/^[ab]:\d+$/',
            'caseStudyIds' => 'array',
            'caseStudyIds.*' => 'exists:case_studies,id',
        ], [
            'bId.different' => 'Pick two different templates.',
        ], ['aId' => 'template A', 'bId' => 'template B']);

        [$a, $b] = $this->sources();
        $src = ['a' => $a, 'b' => $b];
        $lines = ['a' => $a->lines(), 'b' => $b->lines()];

        // Keep services in display order: all of A's chosen lines, then B's.
        $chosen = collect(['a', 'b'])->flatMap(fn ($side) => collect($lines[$side])
            ->filter(fn ($l, $i) => in_array("{$side}:{$i}", $this->serviceKeys, true))
            ->values())->values()->all();

        $settings = $src[$this->pick['settings']];

        $tpl = DB::transaction(function () use ($a, $b, $src, $chosen, $settings) {
            $tpl = ProposalTemplate::create([
                'name' => $this->name,
                'description' => $this->description ?: null,
                'title_pattern' => $settings->title_pattern,
                'collaborator_name' => $settings->collaborator_name,
                'currency' => $settings->currency,
                'booking_url' => $settings->booking_url,
                'validity_days' => $settings->validity_days,
                'executive_summary' => $this->pickText('summary', $a->executive_summary, $b->executive_summary),
                'audit_report' => $this->pickText('audit', $a->audit_report, $b->audit_report),
                'roadmap_milestones' => $this->combinedRoadmap($a, $b),
                'service_lines' => $chosen,
                'service_ids' => collect($chosen)->pluck('service_id')->filter()->values()->all(),
                'case_study_ids' => array_map('intval', $this->caseStudyIds),
                'roi_defaults' => array_merge(ProposalRoiMetric::DEFAULTS, $src[$this->pick['roi']]->roi_defaults ?? []),
                'tenure_months' => $src[$this->pick['tenure']]->tenure_months ?: 3,
            ]);

            $shots = match ($this->pick['screenshots']) {
                'a' => $a->audit_screenshots ?? [],
                'b' => $b->audit_screenshots ?? [],
                'both' => array_merge($a->audit_screenshots ?? [], $b->audit_screenshots ?? []),
                default => [],
            };
            $tpl->update(['audit_screenshots' => ProposalTemplate::copyScreenshots($shots, "templates/{$tpl->id}")]);

            return $tpl;
        });

        session()->flash('notify', "Template \"{$tpl->name}\" created — review and polish it below.");
        $this->redirectRoute('admin.templates.edit', $tpl, navigate: true);
    }

    public function render()
    {
        [$a, $b] = $this->sources();

        return view('livewire.admin.templates.combine', [
            'templates' => ProposalTemplate::orderBy('name')->get(['id', 'name']),
            'a' => $a,
            'b' => $b,
            'linesA' => $a?->lines() ?? [],
            'linesB' => $b?->lines() ?? [],
            'caseStudies' => CaseStudy::whereIn('id', collect([$a?->case_study_ids, $b?->case_study_ids])->flatten()->filter()->unique())->orderBy('title')->get(),
        ]);
    }
}
