@php($indian = strtoupper($currency) === 'INR')
<section aria-labelledby="analytics-heading" class="mt-8" x-data="{ open: $persist(true).as('dashboard-analytics-open') }">
    <div class="mb-3 flex items-center justify-between gap-3">
        <h2 id="analytics-heading" class="text-sm font-semibold tracking-wide text-slate-500 uppercase">Analytics</h2>
        <button type="button" @click="open = !open" :aria-expanded="open.toString()" aria-controls="analytics-body" class="btn-xs">
            <span x-text="open ? 'Hide' : 'Show'"></span>
            <svg class="size-3.5 transition" :class="open ? 'rotate-180' : ''" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24" aria-hidden="true"><path d="m6 9 6 6 6-6"/></svg>
        </button>
    </div>
    <div id="analytics-body" x-show="open" x-collapse>
        <div class="grid grid-cols-1 gap-4 md:grid-cols-2">
        {{-- Funnel --}}
        <figure class="card p-4 sm:p-5" wire:key="chart-funnel-{{ md5(json_encode($charts['funnel'])) }}">
            <figcaption>
                <h3 class="font-semibold text-slate-900">Proposal funnel</h3>
                <p class="text-xs text-slate-500">How far proposals get, from created to accepted</p>
            </figcaption>
            @if (($charts['funnel'][0]['value'] ?? 0) === 0)
                <p class="py-12 text-center text-sm text-slate-400">No proposals yet.</p>
            @else
                <div class="relative mt-3 h-48" x-data="barChart(@js($charts['funnel']), { horizontal: true })">
                    <canvas x-ref="canvas" role="img" aria-label="Bar chart of the proposal funnel"></canvas>
                </div>
                @include('livewire.admin.proposals.partials.chart-table', ['rows' => $charts['funnel'], 'head' => ['Stage', 'Proposals']])
            @endif
        </figure>

        {{-- Won value by month --}}
        <figure class="card p-4 sm:p-5" wire:key="chart-monthly-{{ md5(json_encode($charts['monthly'])) }}">
            <figcaption class="flex items-start justify-between gap-3">
                <div>
                    <h3 class="font-semibold text-slate-900">Won value by month</h3>
                    <p class="text-xs text-slate-500">Signed contract value, last 12 months</p>
                </div>
                <div class="text-right">
                    <div class="text-lg font-bold text-slate-900">{{ \App\Models\Proposal::formatMoney($charts['monthly_total'], $currency) }}</div>
                    <div class="text-xs text-slate-500">12-month total</div>
                </div>
            </figcaption>
            @if ($charts['monthly_total'] <= 0)
                <p class="py-12 text-center text-sm text-slate-400">No signed proposals in the last 12 months.</p>
            @else
                <div class="relative mt-3 h-48" x-data="barChart(@js($charts['monthly']), { indian: @js($indian) })">
                    <canvas x-ref="canvas" role="img" aria-label="Bar chart of won value per month"></canvas>
                </div>
                @include('livewire.admin.proposals.partials.chart-table', [
                    'rows' => array_map(fn ($r) => ['label' => $r['full'], 'display' => \App\Models\Proposal::formatMoney($r['value'], $currency)], $charts['monthly']),
                    'head' => ['Month', 'Won value'],
                ])
            @endif
        </figure>

        {{-- Win rate per strategist --}}
        <figure class="card p-4 sm:p-5" wire:key="chart-strat-{{ md5(json_encode($charts['strategists'])) }}">
            <figcaption>
                <h3 class="font-semibold text-slate-900">Win rate by strategist</h3>
                <p class="text-xs text-slate-500">Accepted ÷ decided (accepted, declined or expired)</p>
            </figcaption>
            @if (empty($charts['strategists']))
                <p class="py-12 text-center text-sm text-slate-400">No decided proposals yet.</p>
            @else
                <div class="relative mt-3" style="height: {{ max(120, count($charts['strategists']) * 36 + 30) }}px"
                     x-data="barChart(@js($charts['strategists']), { horizontal: true, percent: true })">
                    <canvas x-ref="canvas" role="img" aria-label="Bar chart of win rate per strategist"></canvas>
                </div>
                @include('livewire.admin.proposals.partials.chart-table', ['rows' => $charts['strategists'], 'head' => ['Strategist', 'Win rate']])
            @endif
        </figure>

        {{-- Time to sign --}}
        <div class="card flex flex-col p-4 sm:p-5">
            <h3 class="font-semibold text-slate-900">Time to sign</h3>
            <p class="text-xs text-slate-500">From sending the link to the client's signature</p>
            @if ($charts['sign_avg'] === null)
                <p class="py-12 text-center text-sm text-slate-400">Shown once a sent proposal has been signed.</p>
            @else
                <div class="mt-4 grid flex-1 grid-cols-2 items-center gap-4">
                    <div>
                        <div class="text-4xl font-bold tracking-tight text-slate-900">{{ rtrim(rtrim(number_format($charts['sign_avg'], 1), '0'), '.') }}<span class="ml-1 text-base font-semibold text-slate-500">days</span></div>
                        <div class="text-sm text-slate-500">Average</div>
                    </div>
                    <div>
                        <div class="text-4xl font-bold tracking-tight text-slate-900">{{ rtrim(rtrim(number_format($charts['sign_median'], 1), '0'), '.') }}<span class="ml-1 text-base font-semibold text-slate-500">days</span></div>
                        <div class="text-sm text-slate-500">Median</div>
                    </div>
                </div>
                <p class="mt-4 text-xs text-slate-400">Based on {{ $charts['sign_count'] }} signed {{ str('proposal')->plural($charts['sign_count']) }}.</p>
            @endif
        </div>
    </div>
    </div>
</section>
