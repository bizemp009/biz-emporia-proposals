<?php

namespace App\Livewire\Admin\Proposals;

use App\Models\Proposal;
use App\Models\ProposalActivity;
use App\Models\Setting;
use Illuminate\Support\Carbon;
use Livewire\Attributes\Lazy;
use Livewire\Attributes\On;
use Livewire\Component;

/**
 * Dashboard insights: what needs attention and recent activity across all proposals.
 * A separate (lazy) component so typing in the list's search box never re-renders the charts.
 */
#[Lazy]
class Insights extends Component
{
    public const FOLLOW_UP_DAYS = 3;
    public const EXPIRING_DAYS = 7;
    public const STALE_DRAFT_DAYS = 7;

    public function placeholder()
    {
        return view('livewire.admin.proposals.insights-placeholder');
    }

    #[On('proposals-changed')]
    public function refresh(): void
    {
        // Re-render with fresh numbers.
    }

    public function extend(int $id, int $days): void
    {
        $days = in_array($days, [7, 30], true) ? $days : 7;
        $proposal = Proposal::findOrFail($id);
        if ($proposal->extendExpiry($days)) {
            $this->dispatch('notify', message: "Extended to {$proposal->expires_at->format('d M Y')}.");
            $this->dispatch('proposals-changed')->to(Index::class);
            $this->dispatch('proposals-changed')->to(Analytics::class);
        }
    }

    public function markSent(int $id): void
    {
        if (Proposal::findOrFail($id)->markSent()) {
            $this->dispatch('notify', message: 'Marked as sent.');
            $this->dispatch('proposals-changed')->to(Index::class);
            $this->dispatch('proposals-changed')->to(Analytics::class);
        }
    }

    // ---------------------------------------------------------------- data

    private function attention(): array
    {
        $now = now();

        $viewedNoReply = Proposal::with('client')->where('status', 'viewed')
            ->where('viewed_at', '<=', $now->copy()->subDays(self::FOLLOW_UP_DAYS))->orderBy('viewed_at');
        $notOpened = Proposal::with('client')->where('status', 'sent')->whereNull('viewed_at')
            ->where('sent_at', '<=', $now->copy()->subDays(self::FOLLOW_UP_DAYS))->orderBy('sent_at');
        $expiring = Proposal::with('client')->whereIn('status', ['draft', 'sent', 'viewed'])
            ->whereBetween('expires_at', [$now, $now->copy()->addDays(self::EXPIRING_DAYS)->endOfDay()])->orderBy('expires_at');
        $stale = Proposal::with('client')->where('status', 'draft')
            ->where('updated_at', '<=', $now->copy()->subDays(self::STALE_DRAFT_DAYS))->orderBy('updated_at');

        return [
            'follow_up' => [
                'title' => 'Follow up',
                'hint' => 'Opened '.self::FOLLOW_UP_DAYS.'+ days ago, no decision yet',
                'count' => (clone $viewedNoReply)->count(),
                'items' => $viewedNoReply->limit(5)->get()->map(fn ($p) => [$p, 'Opened '.$p->viewed_at->diffForHumans()]),
            ],
            'not_opened' => [
                'title' => 'Not opened yet',
                'hint' => 'Sent '.self::FOLLOW_UP_DAYS.'+ days ago, never viewed',
                'count' => (clone $notOpened)->count(),
                'items' => $notOpened->limit(5)->get()->map(fn ($p) => [$p, 'Sent '.$p->sent_at->diffForHumans()]),
            ],
            'expiring' => [
                'title' => 'Expiring soon',
                'hint' => 'Link closes within '.self::EXPIRING_DAYS.' days',
                'count' => (clone $expiring)->count(),
                'items' => $expiring->limit(5)->get()->map(fn ($p) => [$p, 'Expires '.$p->expires_at->diffForHumans()]),
            ],
            'stale' => [
                'title' => 'Stale drafts',
                'hint' => 'Untouched for '.self::STALE_DRAFT_DAYS.'+ days',
                'count' => (clone $stale)->count(),
                'items' => $stale->limit(5)->get()->map(fn ($p) => [$p, 'Edited '.$p->updated_at->diffForHumans()]),
            ],
        ];
    }

    public function render()
    {
        $currency = Setting::get('default_currency', 'INR');

        return view('livewire.admin.proposals.insights', [
            'attention' => $this->attention(),
            'activities' => ProposalActivity::with('proposal.client')
                ->where('event', '!=', 'updated')
                ->latest('created_at')->latest('id')->limit(12)->get(),
            'currency' => $currency,
        ]);
    }
}
