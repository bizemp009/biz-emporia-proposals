<?php

namespace App\Livewire\Admin\Proposals;

use App\Models\Proposal;
use App\Models\Setting;
use Illuminate\Support\Carbon;
use Livewire\Attributes\Lazy;
use Livewire\Attributes\On;
use Livewire\Component;

/** Dashboard charts (funnel, won value by month, win rate by strategist, time to sign). Lazy-loaded below the list. */
#[Lazy]
class Analytics extends Component
{
    public function placeholder()
    {
        return view('livewire.admin.proposals.analytics-placeholder');
    }

    #[On('proposals-changed')]
    public function refresh(): void
    {
        // Re-render with fresh numbers.
    }

    private function charts(string $currency): array
    {
        $all = Proposal::query()->get(['id', 'status', 'collaborator_name', 'total_amount', 'sent_at', 'viewed_at', 'signed_at', 'updated_at', 'currency']);

        // Funnel: how far each proposal got (counted cumulatively).
        $reachedSent = $all->filter(fn ($p) => $p->sent_at || in_array($p->status, ['sent', 'viewed', 'accepted', 'rejected'], true))->count();
        $reachedViewed = $all->filter(fn ($p) => $p->viewed_at || in_array($p->status, ['viewed', 'accepted', 'rejected'], true))->count();
        $accepted = $all->where('status', 'accepted');
        $total = $all->count();
        $funnel = collect([
            ['Created', $total],
            ['Sent', $reachedSent],
            ['Viewed', $reachedViewed],
            ['Accepted', $accepted->count()],
        ])->map(fn ($r) => [
            'label' => $r[0],
            'value' => $r[1],
            'display' => $r[1].' '.str('proposal')->plural($r[1]).($total ? ' · '.round($r[1] / $total * 100).'% of created' : ''),
        ])->all();

        // Won value per month, last 12 months (by signing date).
        $months = collect(range(11, 0))->map(fn ($i) => now()->startOfMonth()->subMonths($i));
        $won = $accepted->groupBy(fn ($p) => ($p->signed_at ?? $p->updated_at)->format('Y-m'));
        $monthly = $months->map(function (Carbon $m) use ($won, $currency) {
            $sum = (float) ($won[$m->format('Y-m')] ?? collect())->sum('total_amount');

            return ['label' => $m->format('M'), 'value' => $sum, 'display' => $m->format('M Y').': '.Proposal::formatMoney($sum, $currency), 'full' => $m->format('M Y')];
        })->all();

        // Win rate per strategist (decided proposals only).
        $byStrategist = $all->whereIn('status', ['accepted', 'rejected', 'expired'])
            ->groupBy(fn ($p) => $p->collaborator_name ?: 'Unassigned')
            ->map(function ($group, $name) {
                $wins = $group->where('status', 'accepted')->count();
                $rate = (int) round($wins / $group->count() * 100);

                return ['label' => $name, 'value' => $rate, 'display' => "{$rate}% · {$wins} of {$group->count()} won", 'decided' => $group->count(), 'wins' => $wins];
            })
            ->sortByDesc('value')->take(8)->values()->all();

        // Time to sign.
        $durations = $accepted->filter(fn ($p) => $p->signed_at && $p->sent_at)
            ->map(fn ($p) => max(0, $p->sent_at->diffInHours($p->signed_at) / 24))->values();
        $sorted = $durations->sort()->values();
        $median = $sorted->isEmpty() ? null
            : ($sorted->count() % 2 ? $sorted[intdiv($sorted->count(), 2)] : ($sorted[$sorted->count() / 2 - 1] + $sorted[$sorted->count() / 2]) / 2);

        return [
            'funnel' => $funnel,
            'monthly' => $monthly,
            'monthly_total' => array_sum(array_column($monthly, 'value')),
            'strategists' => $byStrategist,
            'sign_avg' => $durations->isEmpty() ? null : round($durations->avg(), 1),
            'sign_median' => $median === null ? null : round($median, 1),
            'sign_count' => $durations->count(),
        ];
    }

    public function render()
    {
        $currency = Setting::get('default_currency', 'INR');

        return view('livewire.admin.proposals.analytics', [
            'charts' => $this->charts($currency),
            'currency' => $currency,
        ]);
    }
}
