<section id="section-{{ $n }}" class="card scroll-mt-32 p-4 sm:p-5">
    <h2 class="mb-1 flex items-center gap-2 font-semibold text-slate-900"><span class="flex size-6 items-center justify-center rounded-full bg-brand-accent/10 text-xs text-brand-accent">{{ $n }}</span> ROI &amp; effort parameters</h2>
    <p class="mb-4 text-xs text-slate-500">Defaults the client sees in the simulator. Clients can move budget, traffic, conversion rate and order value; CPC, baseline, lift and hourly rate are fixed assumptions.</p>
    <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
        @foreach ([
            'default_budget_or_spend' => ['Monthly ad spend', '1'],
            'cost_per_click' => ['Avg. cost per click', '0.01'],
            'baseline_monthly_traffic' => ['Current monthly traffic', '1'],
            'target_conversion_rate' => ['Target conversion rate %', '0.01'],
            'average_order_value' => ['Avg. order / deal value', '1'],
            'projected_lift_multiplier' => ['Projected organic lift ×', '0.01'],
            'default_working_hours' => ['Team hours / month', '0.5'],
            'hourly_rate' => ['Blended hourly rate', '1'],
        ] as $key => [$label, $step])
            <div>
                <label class="label">{{ $label }}</label>
                <input type="number" step="{{ $step }}" min="0" wire:model="roi.{{ $key }}" class="input">
                @error("roi.$key")<p class="error">{{ $message }}</p>@enderror
            </div>
        @endforeach
    </div>
</section>
