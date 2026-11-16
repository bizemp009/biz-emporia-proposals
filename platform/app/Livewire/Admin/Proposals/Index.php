<?php

namespace App\Livewire\Admin\Proposals;

use App\Models\Proposal;
use App\Models\Setting;
use App\Support\ProposalFilters;
use Illuminate\Support\Facades\DB;
use Livewire\Attributes\Layout;
use Livewire\Attributes\On;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

#[Layout('layouts.admin')]
#[Title('Proposals')]
class Index extends Component
{
    use WithPagination;

    #[Url(except: '')]
    public string $search = '';

    #[Url(except: '')]
    public string $status = '';

    #[Url(except: '')]
    public string $strategist = '';

    #[Url(except: '')]
    public string $from = '';

    #[Url(except: '')]
    public string $to = '';

    #[Url(except: '')]
    public string $min = '';

    #[Url(except: '')]
    public string $max = '';

    #[Url(except: 'updated')]
    public string $sort = 'updated';

    #[Url(except: 'desc')]
    public string $dir = 'desc';

    /** @var array<int, string> ids ticked for bulk actions */
    public array $selected = [];

    private const FILTER_PROPS = ['search', 'status', 'strategist', 'from', 'to', 'min', 'max'];

    public function mount(): void
    {
        // Keep the dashboard honest about links that lapsed since last visit.
        Proposal::whereIn('status', ['draft', 'sent', 'viewed'])
            ->whereNotNull('expires_at')->where('expires_at', '<', now())
            ->get()->each->expireIfNeeded();
    }

    public function updated(string $property): void
    {
        if (in_array($property, self::FILTER_PROPS, true)) {
            $this->resetPage();
            $this->selected = [];
        }
    }

    // ---------------------------------------------------------------- sorting & filters

    public function sortBy(string $column): void
    {
        if (! in_array($column, ProposalFilters::SORTS, true)) {
            return;
        }
        if ($this->sort === $column) {
            $this->dir = $this->dir === 'asc' ? 'desc' : 'asc';
        } else {
            $this->sort = $column;
            // Text-ish columns read naturally A→Z; numbers and dates newest / biggest first.
            $this->dir = in_array($column, ['client', 'status'], true) ? 'asc' : 'desc';
        }
        $this->resetPage();
    }

    public function applySort(string $column, string $dir): void
    {
        if (in_array($column, ProposalFilters::SORTS, true)) {
            $this->sort = $column;
            $this->dir = $dir === 'asc' ? 'asc' : 'desc';
            $this->resetPage();
        }
    }

    public function clearFilters(): void
    {
        $this->reset(...self::FILTER_PROPS);
        $this->resetPage();
        $this->selected = [];
    }

    private function filters(): ProposalFilters
    {
        return ProposalFilters::fromArray($this->only(ProposalFilters::KEYS));
    }

    // ---------------------------------------------------------------- row actions

    public function duplicate(int $id): void
    {
        $copy = Proposal::findOrFail($id)->duplicate();
        $this->redirectRoute('admin.proposals.edit', $copy, navigate: true);
    }

    public function markSent(int $id): void
    {
        if (Proposal::findOrFail($id)->markSent()) {
            $this->changed('Marked as sent.');
        }
    }

    public function extend(int $id, int $days): void
    {
        $days = in_array($days, [7, 30], true) ? $days : 7;
        $proposal = Proposal::findOrFail($id);
        if ($proposal->extendExpiry($days)) {
            $this->changed("Extended to {$proposal->expires_at->format('d M Y')}.");
        } else {
            $this->dispatch('notify', message: 'Signed or declined proposals cannot be extended.', type: 'error');
        }
    }

    public function delete(int $id): void
    {
        Proposal::findOrFail($id)->delete();
        $this->selected = array_values(array_diff($this->selected, [(string) $id]));
        $this->changed('Proposal deleted.');
    }

    // ---------------------------------------------------------------- bulk actions

    /** @return \Illuminate\Database\Eloquent\Collection<int, Proposal> */
    private function selectedProposals()
    {
        $ids = array_filter(array_map('intval', $this->selected));

        return Proposal::whereIn('id', $ids)->get();
    }

    public function bulkMarkSent(): void
    {
        $n = $this->selectedProposals()->filter->markSent()->count();
        $this->selected = [];
        $this->changed($n ? "Marked {$n} ".str('proposal')->plural($n).' as sent.' : 'Only drafts can be marked as sent — nothing changed.', $n ? 'success' : 'error');
    }

    public function bulkExtend(int $days): void
    {
        $days = in_array($days, [7, 30], true) ? $days : 7;
        $proposals = $this->selectedProposals();
        $n = $proposals->filter(fn (Proposal $p) => $p->extendExpiry($days))->count();
        $skipped = $proposals->count() - $n;
        $this->selected = [];
        $this->changed(
            "Extended {$n} ".str('proposal')->plural($n)." by {$days} days.".($skipped ? " {$skipped} signed/declined skipped." : ''),
            $n ? 'success' : 'error'
        );
    }

    public function bulkDelete(): void
    {
        $n = DB::transaction(fn () => $this->selectedProposals()->each->delete()->count());
        $this->selected = [];
        $this->resetPage();
        $this->changed("Deleted {$n} ".str('proposal')->plural($n).'.');
    }

    /** Toast + tell the insights panel to refresh. */
    private function changed(string $message, string $type = 'success'): void
    {
        $this->dispatch('notify', message: $message, type: $type);
        $this->dispatch('proposals-changed')->to(Insights::class);
        $this->dispatch('proposals-changed')->to(Analytics::class);
    }

    #[On('proposals-changed')]
    public function refreshList(): void
    {
        // Re-render only (an action in the insights panel changed data).
    }

    // ---------------------------------------------------------------- view

    public function render()
    {
        $filters = $this->filters();
        $proposals = $filters->query()->paginate(15);

        $counts = Proposal::select('status', DB::raw('count(*) as n'))->groupBy('status')->pluck('n', 'status');
        $acceptedValue = (float) Proposal::where('status', 'accepted')->sum('total_amount');
        $pipelineValue = (float) Proposal::whereIn('status', ['sent', 'viewed'])->sum('total_amount');
        $openCount = ($counts['sent'] ?? 0) + ($counts['viewed'] ?? 0);
        $decided = ($counts['accepted'] ?? 0) + ($counts['rejected'] ?? 0) + ($counts['expired'] ?? 0);

        return view('livewire.admin.proposals.index', [
            'proposals' => $proposals,
            'counts' => $counts,
            'stats' => [
                'total' => $counts->sum(),
                'open' => $openCount,
                'accepted' => $counts['accepted'] ?? 0,
                'accepted_value' => $acceptedValue,
                'pipeline_value' => $pipelineValue,
                'decided' => $decided,
                'win_rate' => $decided > 0 ? round(($counts['accepted'] ?? 0) / $decided * 100) : null,
            ],
            'strategists' => Proposal::query()->whereNotNull('collaborator_name')->where('collaborator_name', '!=', '')
                ->distinct()->orderBy('collaborator_name')->pluck('collaborator_name'),
            'advancedCount' => $filters->advancedCount(),
            'hasFilters' => $filters->advancedCount() > 0 || $this->search !== '' || $this->status !== '',
            'exportUrl' => route('admin.proposals.export', $filters->toQuery()),
            'pageIds' => $proposals->getCollection()->pluck('id')->map(fn ($id) => (string) $id)->values()->all(),
            'currency' => Setting::get('default_currency', 'INR'),
        ]);
    }
}
