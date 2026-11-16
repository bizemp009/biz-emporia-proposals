<div>
    @php
        $p = $proposal;
        $client = $p->client;
        $locked = $p->isLocked();
        $sections = array_filter([
            'overview' => 'Overview',
            'strategy' => $p->services->isNotEmpty() ? 'Strategy' : null,
            'roi' => 'ROI Simulator',
            'results' => $p->caseStudies->isNotEmpty() ? 'Results' : null,
            'pricing' => $p->services->isNotEmpty() ? 'Pricing' : null,
            'roadmap' => ! empty($p->roadmap_milestones) ? 'Roadmap' : null,
            'sign' => 'Approve',
        ]);
        $num = array_flip(array_keys($sections));
    @endphp

    @if ($preview)
        <div class="sticky top-0 z-40 bg-amber-400 px-4 py-1.5 text-center text-xs font-semibold text-amber-950">
            Staff preview — views and clicks are not tracked{{ $p->status === 'draft' ? ' · This proposal is still a DRAFT and not visible to the client' : '' }}.
            <a href="{{ route('admin.proposals.edit', $p) }}" class="underline">Back to editor</a>
        </div>
    @endif

    {{-- ================================================================ HERO --}}
    <header class="relative overflow-hidden bg-brand text-white">
        <div class="pointer-events-none absolute inset-0 opacity-60" style="background: radial-gradient(60rem 30rem at 85% -10%, var(--color-brand-accent), transparent 60%), radial-gradient(40rem 20rem at -10% 110%, var(--color-brand-highlight), transparent 60%);"></div>
        <div class="relative mx-auto max-w-6xl px-4 pt-8 pb-14 sm:px-6 sm:pt-10 sm:pb-20">
            <div class="flex flex-wrap items-center justify-between gap-4">
                <div class="flex items-center gap-3">
                    <div class="flex size-11 items-center justify-center rounded-xl bg-white/15 text-sm font-bold ring-1 ring-white/25 backdrop-blur">
                        {{ \Illuminate\Support\Str::of($settings['agency_name'])->explode(' ')->map(fn ($w) => mb_substr($w, 0, 1))->take(2)->join('') }}
                    </div>
                    <div>
                        <div class="font-semibold">{{ $settings['agency_name'] }}</div>
                        <div class="text-xs text-white/70">{{ $settings['agency_tagline'] }}</div>
                    </div>
                </div>
                <a href="{{ route('proposal.pdf', $p->slug) }}" class="inline-flex items-center gap-2 rounded-lg bg-white/10 px-3 py-1.5 text-xs font-semibold ring-1 ring-white/25 hover:bg-white/20">
                    <svg class="size-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M12 4v12m0 0-4-4m4 4 4-4M4 20h16"/></svg>
                    Download PDF
                </a>
            </div>

            <div class="mt-12 max-w-3xl sm:mt-16">
                <div class="text-sm font-semibold tracking-wide text-brand-highlight uppercase">Prepared for {{ $client->brand_name ?: $client->company_name }}</div>
                <h1 class="mt-2 text-3xl leading-tight font-bold sm:text-5xl">{{ $p->title }}</h1>
                <dl class="mt-6 flex flex-wrap gap-x-8 gap-y-3 text-sm">
                    <div><dt class="text-white/60">Client</dt><dd class="font-medium">{{ $client->company_name }}</dd></div>
                    <div><dt class="text-white/60">Attention</dt><dd class="font-medium">{{ $client->contact_person }}</dd></div>
                    @if ($p->collaborator_name)<div><dt class="text-white/60">Your strategist</dt><dd class="font-medium">{{ $p->collaborator_name }}</dd></div>@endif
                    <div><dt class="text-white/60">Issued</dt><dd class="font-medium">{{ ($p->sent_at ?? $p->created_at)->format('d M Y') }}</dd></div>
                    @if ($p->expires_at)<div><dt class="text-white/60">Valid until</dt><dd class="font-medium">{{ $p->expires_at->format('d M Y') }}</dd></div>@endif
                </dl>
            </div>
        </div>
    </header>

    {{-- ================================================================ STATUS BANNERS --}}
    @if ($p->status === 'accepted')
        <div class="bg-emerald-600 px-4 py-3 text-center text-sm font-medium text-white">
            ✓ Approved by {{ $p->signer_name }} on {{ $p->signed_at->format('d M Y') }}. Thank you — we're excited to get started!
        </div>
    @elseif ($p->status === 'expired')
        <div class="bg-zinc-700 px-4 py-3 text-center text-sm font-medium text-white">
            This proposal expired on {{ $p->expires_at?->format('d M Y') }}. Contact <a class="underline" href="mailto:{{ $settings['agency_email'] }}">{{ $settings['agency_email'] }}</a> for an updated version.
        </div>
    @elseif ($p->status === 'rejected')
        <div class="bg-rose-600 px-4 py-3 text-center text-sm font-medium text-white">
            You declined this proposal. Changed your mind? Email <a class="underline" href="mailto:{{ $settings['agency_email'] }}">{{ $settings['agency_email'] }}</a>.
        </div>
    @endif

    {{-- ================================================================ STICKY NAV --}}
    <nav class="sticky {{ $preview ? 'top-7' : 'top-0' }} z-30 border-b border-slate-200 bg-white/90 backdrop-blur">
        <div class="mx-auto flex max-w-6xl items-center gap-1 overflow-x-auto px-4 sm:px-6">
            @foreach ($sections as $id => $label)
                <a href="#{{ $id }}" class="shrink-0 border-b-2 border-transparent px-3 py-3 text-sm font-medium whitespace-nowrap text-slate-600 hover:border-brand-accent hover:text-slate-900">{{ $label }}</a>
            @endforeach
            <div class="ml-auto hidden shrink-0 pl-4 text-sm sm:block">
                <span class="text-slate-500">Total</span>
                <span class="font-bold text-slate-900">{{ $p->money($this->pricing['contract_value']) }}</span>
            </div>
        </div>
    </nav>

    <main class="mx-auto max-w-6xl space-y-16 px-4 py-12 sm:space-y-24 sm:px-6 sm:py-16">

        {{-- ================================================================ 1. EXECUTIVE SUMMARY & AUDIT --}}
        <section id="overview" class="scroll-mt-20">
            <x-proposal.heading :n="$num['overview'] + 1" kicker="Executive summary" title="Where you are today — and where we can take you" />
            <div class="grid gap-8 lg:grid-cols-5">
                <div class="prose-content text-slate-700 lg:col-span-3">
                    @if ($summary !== '')
                        {!! $summary !!}
                    @else
                        <p>Thank you for the opportunity to partner with {{ $client->company_name }}. This proposal outlines how we'll accelerate your growth.</p>
                    @endif
                </div>
                @if ($audit !== '')
                    <aside class="card bg-slate-50/50 p-6 lg:col-span-2">
                        <div class="mb-3 flex items-center gap-2 text-sm font-semibold text-slate-900">
                            <span class="flex size-7 items-center justify-center rounded-lg bg-brand-highlight/10 text-brand-highlight">
                                <svg class="size-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="m21 21-4.3-4.3M11 18a7 7 0 1 0 0-14 7 7 0 0 0 0 14Z"/></svg>
                            </span>
                            Mini-audit findings
                        </div>
                        <div class="prose-content text-sm text-slate-700">{!! $audit !!}</div>
                    </aside>
                @endif
            </div>

            @if (count($screenshots))
                <div class="mt-10" x-data="{ zoom: null }">
                    <h3 class="mb-4 text-sm font-semibold tracking-wide text-slate-500 uppercase">Visual evidence</h3>
                    <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
                        @foreach ($screenshots as $shot)
                            <figure class="card group overflow-hidden">
                                <button type="button" @click="zoom = @js($shot)" class="block w-full overflow-hidden">
                                    <img src="{{ $shot['url'] }}" alt="{{ $shot['caption'] }}" loading="lazy" class="aspect-video w-full bg-slate-100 object-cover transition group-hover:scale-105">
                                </button>
                                @if ($shot['caption'])<figcaption class="px-4 py-3 text-sm text-slate-600">{{ $shot['caption'] }}</figcaption>@endif
                            </figure>
                        @endforeach
                    </div>
                    <div x-show="zoom" x-cloak @click="zoom = null" @keydown.escape.window="zoom = null" class="fixed inset-0 z-50 flex items-center justify-center bg-slate-950/85 p-4">
                        <figure class="max-h-full max-w-5xl">
                            <img :src="zoom?.url" class="max-h-[85vh] rounded-lg" alt="">
                            <figcaption class="mt-2 text-center text-sm text-white/80" x-text="zoom?.caption"></figcaption>
                        </figure>
                    </div>
                </div>
            @endif
        </section>

        {{-- ================================================================ 2. STRATEGY & SCOPE --}}
        @if ($p->services->isNotEmpty())
            <section id="strategy" class="scroll-mt-20">
                <x-proposal.heading :n="$num['strategy'] + 1" kicker="Strategic recommendations" title="The services we recommend" sub="Exactly what's included, so expectations are transparent from day one." />
                <div class="grid gap-5 md:grid-cols-2">
                    @foreach ($p->services as $s)
                        <article class="card flex flex-col p-6 transition {{ in_array($s->id, $selectedServices) ? '' : 'opacity-60' }}">
                            <div class="flex items-start justify-between gap-3">
                                <h3 class="text-lg font-semibold text-slate-900">{{ $s->name }}</h3>
                                @if ($s->is_optional)
                                    <span class="shrink-0 rounded-full bg-amber-50 px-2 py-0.5 text-[11px] font-semibold text-amber-700 ring-1 ring-amber-200">Optional add-on</span>
                                @else
                                    <span class="shrink-0 rounded-full bg-brand-accent/10 px-2 py-0.5 text-[11px] font-semibold text-brand-accent">Core</span>
                                @endif
                            </div>
                            @if ($s->description)<p class="mt-2 text-sm text-slate-600">{{ $s->description }}</p>@endif
                            @if ($s->deliverables)
                                <ul class="mt-4 space-y-2 text-sm">
                                    @foreach ($s->deliverables as $d)
                                        <li class="flex gap-2"><svg class="mt-0.5 size-4 shrink-0 text-emerald-500" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><path d="m5 13 4 4L19 7"/></svg><span class="text-slate-700">{{ $d }}</span></li>
                                    @endforeach
                                </ul>
                            @endif
                        </article>
                    @endforeach
                </div>
            </section>
        @endif

        {{-- ================================================================ 3. ROI SIMULATOR --}}
        <section id="roi" class="scroll-mt-20">
            <x-proposal.heading :n="$num['roi'] + 1" kicker="Live KPIs & ROI" title="Model your return" sub="Start from a budget, or from the traffic and conversion targets you want to hit. Everything updates instantly." />

            <div wire:ignore x-data="roiCalculator(@js($roiConfig))" class="card overflow-hidden">
                <div class="flex flex-wrap items-center gap-2 border-b border-slate-100 p-4 sm:p-5">
                    <div class="inline-flex rounded-lg bg-slate-100 p-1 text-sm font-semibold">
                        <button type="button" @click="mode = 'budget'" :class="mode === 'budget' ? 'bg-white text-slate-900 shadow-sm' : 'text-slate-500'" class="rounded-md px-3 py-1.5">Budget-first</button>
                        <button type="button" @click="mode = 'output'" :class="mode === 'output' ? 'bg-white text-slate-900 shadow-sm' : 'text-slate-500'" class="rounded-md px-3 py-1.5">Traffic-first</button>
                    </div>
                    <p class="text-xs text-slate-500" x-text="mode === 'budget' ? 'Enter a monthly ad budget to see projected traffic, conversions and ROI.' : 'Set your traffic and conversion goals to see the spend and effort required.'"></p>
                </div>

                <div class="grid lg:grid-cols-[340px_minmax(0,1fr)]">
                    {{-- Inputs --}}
                    <div class="space-y-6 border-b border-slate-100 p-5 lg:border-r lg:border-b-0">
                        <template x-if="mode === 'budget'">
                            <div>
                                <label class="label">Monthly ad budget</label>
                                <input type="number" min="0" step="1000" x-model.number="budget" class="input text-lg font-semibold">
                                <input type="range" min="0" :max="Math.max(defaultSpend * 5, 10000)" :step="Math.max(Math.round(defaultSpend / 50), 1)" x-model.number="budget" class="mt-3 w-full">
                            </div>
                        </template>
                        <template x-if="mode === 'output'">
                            <div>
                                <div class="flex justify-between"><label class="label">Target monthly traffic</label><span class="text-sm font-semibold" x-text="num(targetTraffic)"></span></div>
                                <input type="range" :min="Math.round(organicTraffic)" :max="Math.round(organicTraffic * 6 + 1000)" step="100" x-model.number="targetTraffic" class="w-full">
                            </div>
                        </template>
                        <div>
                            <div class="flex justify-between"><label class="label">Conversion rate</label><span class="text-sm font-semibold" x-text="Number(cr).toFixed(1) + '%'"></span></div>
                            <input type="range" min="0.2" max="15" step="0.1" x-model.number="cr" class="w-full">
                        </div>
                        <div>
                            <div class="flex justify-between"><label class="label">Average order / deal value</label><span class="text-sm font-semibold" x-text="money(aov)"></span></div>
                            <input type="range" :min="Math.max(Math.round({{ $roiConfig['aov'] }} * 0.2), 1)" :max="Math.round({{ $roiConfig['aov'] }} * 4) || 1000" :step="Math.max(Math.round({{ $roiConfig['aov'] }} / 100), 1)" x-model.number="aov" class="w-full">
                        </div>
                        <dl class="space-y-1.5 rounded-lg bg-slate-50 p-3 text-xs text-slate-500">
                            <div class="flex justify-between"><dt>Current organic traffic</dt><dd class="font-medium text-slate-700" x-text="num(baseline)"></dd></div>
                            <div class="flex justify-between"><dt>Projected organic lift</dt><dd class="font-medium text-slate-700" x-text="'× ' + lift.toFixed(2)"></dd></div>
                            <div class="flex justify-between"><dt>Avg. cost per click</dt><dd class="font-medium text-slate-700" x-text="money(cpc)"></dd></div>
                            <div class="flex justify-between"><dt>Agency fee (monthly equiv.)</dt><dd class="font-medium text-slate-700" x-text="money(agencyFee)"></dd></div>
                        </dl>
                    </div>

                    {{-- Outputs --}}
                    <div class="p-5">
                        <div class="grid grid-cols-2 gap-3 xl:grid-cols-4">
                            <div class="rounded-xl bg-slate-50 p-4">
                                <div class="text-xs font-medium text-slate-500" x-text="mode === 'budget' ? 'Monthly traffic' : 'Required ad spend'"></div>
                                <div class="mt-1 text-xl font-bold text-slate-900" x-text="mode === 'budget' ? num(traffic) : money(spend)"></div>
                            </div>
                            <div class="rounded-xl bg-slate-50 p-4">
                                <div class="text-xs font-medium text-slate-500">Conversions / mo</div>
                                <div class="mt-1 text-xl font-bold text-slate-900" x-text="num(conversions)"></div>
                            </div>
                            <div class="rounded-xl bg-slate-50 p-4">
                                <div class="text-xs font-medium text-slate-500">Projected revenue / mo</div>
                                <div class="mt-1 text-xl font-bold text-slate-900" x-text="money(revenue)"></div>
                            </div>
                            <div class="rounded-xl p-4 text-white" :class="roi >= 0 ? 'bg-emerald-600' : 'bg-rose-600'">
                                <div class="text-xs font-medium text-white/80">Net ROI</div>
                                <div class="mt-1 text-xl font-bold" x-text="Math.round(roi) + '%'"></div>
                            </div>
                        </div>
                        <div class="mt-3 grid grid-cols-3 gap-3 text-center text-xs">
                            <div class="rounded-lg ring-1 ring-slate-200 p-2"><div class="text-slate-500">Team effort</div><div class="font-semibold text-slate-900" x-text="Math.round(hours) + ' hrs / mo'"></div></div>
                            <div class="rounded-lg ring-1 ring-slate-200 p-2"><div class="text-slate-500">Effort value</div><div class="font-semibold text-slate-900" x-text="money(effortValue)"></div></div>
                            <div class="rounded-lg ring-1 ring-slate-200 p-2"><div class="text-slate-500">ROAS</div><div class="font-semibold text-slate-900" x-text="roas ? roas.toFixed(1) + '×' : '—'"></div></div>
                        </div>
                        <div class="relative mt-5 h-72">
                            <canvas x-ref="canvas" aria-label="Monthly revenue projection chart" role="img"></canvas>
                        </div>
                        <p class="mt-3 text-[11px] leading-relaxed text-slate-400">
                            Projections are illustrative estimates based on the assumptions shown and industry benchmarks; they are not a guarantee of results.
                            Organic lift ramps up over the first three months; paid performance ramps from ~70% as campaigns optimise.
                        </p>
                    </div>
                </div>
            </div>
        </section>

        {{-- ================================================================ 4. CASE STUDIES --}}
        @if ($p->caseStudies->isNotEmpty())
            <section id="results" class="scroll-mt-20">
                <x-proposal.heading :n="$num['results'] + 1" kicker="Proven results" title="We've done this before" />
                <div class="grid gap-5 md:grid-cols-2 lg:grid-cols-3">
                    @foreach ($p->caseStudies as $cs)
                        <article class="card flex flex-col overflow-hidden">
                            @if ($cs->image_url)
                                <img src="{{ $cs->image_url }}" alt="" loading="lazy" class="aspect-[16/8] w-full object-cover">
                            @else
                                <div class="flex aspect-[16/8] items-end bg-gradient-to-br from-brand to-brand-accent p-4"><span class="text-sm font-semibold text-white/90">{{ $cs->industry }}</span></div>
                            @endif
                            <div class="flex flex-1 flex-col p-5">
                                @if ($cs->image_url && $cs->industry)<div class="text-xs font-semibold text-brand-accent">{{ $cs->industry }}</div>@endif
                                <h3 class="font-semibold text-slate-900">{{ $cs->title }}</h3>
                                @if ($cs->metrics)
                                    <div class="mt-4 grid grid-cols-{{ min(count($cs->metrics), 3) }} gap-2">
                                        @foreach (array_slice($cs->metrics, 0, 3) as $m)
                                            <div class="rounded-lg bg-slate-50 p-2 text-center">
                                                <div class="text-lg font-bold text-brand-accent">{{ $m['value'] }}</div>
                                                <div class="text-[11px] leading-tight text-slate-500">{{ $m['label'] }}</div>
                                            </div>
                                        @endforeach
                                    </div>
                                @endif
                                @if ($cs->summary)<p class="mt-4 text-sm text-slate-600">{{ $cs->summary }}</p>@endif
                                @if ($cs->testimonial_quote)
                                    <blockquote class="mt-auto border-t border-slate-100 pt-4 text-sm text-slate-700 italic">
                                        “{{ $cs->testimonial_quote }}”
                                        @if ($cs->testimonial_author)<footer class="mt-1 text-xs font-semibold text-slate-500 not-italic">— {{ $cs->testimonial_author }}</footer>@endif
                                    </blockquote>
                                @endif
                            </div>
                        </article>
                    @endforeach
                </div>
            </section>
        @endif

        {{-- ================================================================ 5. PRICING --}}
        @if ($p->services->isNotEmpty())
            <section id="pricing" class="scroll-mt-20">
                <x-proposal.heading :n="$num['pricing'] + 1" kicker="Packages & investment" title="Build your package" sub="Core services are included. Toggle optional add-ons to tailor the scope — your total updates instantly." />
                <div class="grid gap-6 lg:grid-cols-[minmax(0,1fr)_340px]">
                    <div class="space-y-6">
                        @foreach (['core' => 'Included in every engagement', 'optional' => 'Optional add-ons'] as $group => $heading)
                            @if (isset($groupedServices[$group]))
                                <div>
                                    <h3 class="mb-3 text-sm font-semibold tracking-wide text-slate-500 uppercase">{{ $heading }}</h3>
                                    <div class="card divide-y divide-slate-100">
                                        @foreach ($groupedServices[$group] as $s)
                                            @php($on = in_array($s->id, $selectedServices))
                                            <div wire:key="price-{{ $s->id }}" class="flex items-center gap-4 p-4 sm:p-5">
                                                <div class="min-w-0 flex-1">
                                                    <div class="font-medium text-slate-900">{{ $s->name }}</div>
                                                    @if ($s->description)<div class="line-clamp-1 text-sm text-slate-500">{{ $s->description }}</div>@endif
                                                </div>
                                                <div class="text-right whitespace-nowrap">
                                                    <div class="font-semibold text-slate-900">{{ $p->money($s->price) }}</div>
                                                    <div class="text-xs text-slate-400">{{ \App\Models\ProposalService::BILLING_LABELS[$s->billing_type] }}</div>
                                                </div>
                                                @if ($s->is_optional)
                                                    <button type="button" role="switch" aria-checked="{{ $on ? 'true' : 'false' }}" aria-label="Include {{ $s->name }}"
                                                            wire:click="toggleService({{ $s->id }})" @disabled($locked)
                                                            class="relative inline-flex h-6 w-11 shrink-0 cursor-pointer items-center rounded-full transition disabled:cursor-not-allowed disabled:opacity-60 {{ $on ? 'bg-brand-accent' : 'bg-slate-300' }}">
                                                        <span class="inline-block size-5 rounded-full bg-white shadow transition {{ $on ? 'translate-x-5.5' : 'translate-x-0.5' }}"></span>
                                                    </button>
                                                @else
                                                    <span class="flex h-6 w-11 shrink-0 items-center justify-center text-emerald-500" title="Included">
                                                        <svg class="size-5" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><path d="m5 13 4 4L19 7"/></svg>
                                                    </span>
                                                @endif
                                            </div>
                                        @endforeach
                                    </div>
                                </div>
                            @endif
                        @endforeach
                    </div>

                    <aside class="lg:sticky lg:top-20 lg:self-start">
                        <div class="card overflow-hidden" wire:loading.class="opacity-70" wire:target="toggleService">
                            <div class="bg-brand p-5 text-white">
                                <div class="text-xs font-semibold tracking-wide text-white/70 uppercase">Total contract value</div>
                                <div class="mt-1 text-3xl font-bold">{{ $p->money($this->pricing['contract_value']) }}</div>
                                <div class="mt-1 text-xs text-white/70">over {{ $this->pricing['tenure'] }} {{ \Illuminate\Support\Str::plural('month', $this->pricing['tenure']) }}</div>
                            </div>
                            <dl class="space-y-2 p-5 text-sm">
                                <div class="flex justify-between"><dt class="text-slate-500">One-time setup</dt><dd class="font-medium">{{ $p->money($this->pricing['one_time']) }}</dd></div>
                                <div class="flex justify-between"><dt class="text-slate-500">Monthly retainer</dt><dd class="font-medium">{{ $p->money($this->pricing['monthly']) }} <span class="text-slate-400">/ mo</span></dd></div>
                                @if ($this->pricing['quarterly'] > 0)
                                    <div class="flex justify-between"><dt class="text-slate-500">Quarterly</dt><dd class="font-medium">{{ $p->money($this->pricing['quarterly']) }} <span class="text-slate-400">/ qtr</span></dd></div>
                                @endif
                                <div class="flex justify-between border-t border-slate-100 pt-2"><dt class="text-slate-500">Avg. per month</dt><dd class="font-semibold">{{ $p->money($monthlyInvestment) }}</dd></div>
                            </dl>
                            @unless ($locked)
                                <div class="px-5 pb-5"><a href="#sign" class="btn-primary w-full">Approve this package →</a></div>
                            @endunless
                        </div>
                    </aside>
                </div>
            </section>
        @endif

        {{-- ================================================================ 6. ROADMAP --}}
        @if (! empty($p->roadmap_milestones))
            <section id="roadmap" class="scroll-mt-20">
                <x-proposal.heading :n="$num['roadmap'] + 1" kicker="Execution roadmap" :title="'Your '.$p->tenure_months.'-month plan'" />
                <ol class="relative grid gap-6 md:grid-cols-{{ min(count($p->roadmap_milestones), 3) }}">
                    @foreach ($p->roadmap_milestones as $i => $m)
                        <li class="card relative p-6">
                            <div class="mb-4 flex items-center gap-3">
                                <span class="flex size-9 items-center justify-center rounded-full bg-brand-accent text-sm font-bold text-white ring-4 ring-brand-accent/15">{{ $i + 1 }}</span>
                                <h3 class="font-semibold text-slate-900">{{ $m['title'] }}</h3>
                            </div>
                            @if (! empty($m['items']))
                                <ul class="space-y-2 text-sm text-slate-700">
                                    @foreach ($m['items'] as $item)
                                        <li class="flex gap-2"><span class="mt-2 size-1.5 shrink-0 rounded-full bg-brand-highlight"></span>{{ $item }}</li>
                                    @endforeach
                                </ul>
                            @endif
                        </li>
                    @endforeach
                </ol>
            </section>
        @endif

        {{-- ================================================================ 7. AUTHORIZATION --}}
        <section id="sign" class="scroll-mt-20">
            <x-proposal.heading :n="$num['sign'] + 1" kicker="Authorization" :title="$p->status === 'accepted' ? 'Agreement signed' : 'Approve & get started'" />

            <div class="grid gap-6 lg:grid-cols-[minmax(0,1fr)_360px]">
                <div class="card p-6">
                    @if ($p->status === 'accepted')
                        <div class="flex items-start gap-4">
                            <span class="flex size-12 shrink-0 items-center justify-center rounded-full bg-emerald-100 text-emerald-600">
                                <svg class="size-6" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><path d="m5 13 4 4L19 7"/></svg>
                            </span>
                            <div>
                                <div class="text-lg font-semibold text-slate-900">Thank you, {{ $p->signer_name }}!</div>
                                <p class="text-sm text-slate-600">Signed {{ $p->signed_at->format('d M Y \a\t H:i') }} · Contract value {{ $p->money($p->total_amount) }}</p>
                                @if ($p->client_signature)
                                    <img src="{{ $p->client_signature }}" alt="Signature" class="mt-4 h-24 rounded-lg bg-slate-50 p-2 ring-1 ring-slate-200">
                                @endif
                                <p class="mt-2 text-sm text-slate-700">{{ $p->signer_name }} · {{ $p->signer_title }}</p>
                                <a href="{{ route('proposal.pdf', $p->slug) }}" class="btn-ghost mt-4">Download signed copy (PDF)</a>
                            </div>
                        </div>
                    @elseif ($locked)
                        <p class="text-slate-600">This proposal can no longer be signed online ({{ $p->status }}). Please reach out to <a class="text-brand-accent underline" href="mailto:{{ $settings['agency_email'] }}">{{ $settings['agency_email'] }}</a>.</p>
                    @else
                        @if ($settings['terms'])
                            <div class="mb-6 max-h-40 overflow-y-auto rounded-lg bg-slate-50 p-4 text-xs leading-relaxed whitespace-pre-line text-slate-600">{{ $settings['terms'] }}</div>
                        @endif

                        <div x-data="signaturePad()" class="space-y-4">
                            <div class="grid gap-4 sm:grid-cols-2">
                                <div>
                                    <label class="label" for="signer-name">Full name</label>
                                    <input id="signer-name" wire:model="signerName" class="input" autocomplete="name">
                                    @error('signerName')<p class="error">{{ $message }}</p>@enderror
                                </div>
                                <div>
                                    <label class="label" for="signer-title">Job title</label>
                                    <input id="signer-title" wire:model="signerTitle" class="input" autocomplete="organization-title" placeholder="e.g. Founder & CEO">
                                    @error('signerTitle')<p class="error">{{ $message }}</p>@enderror
                                </div>
                            </div>

                            <div>
                                <div class="mb-1 flex items-center justify-between">
                                    <label class="label mb-0">Signature</label>
                                    <button type="button" @click="clear()" class="text-xs font-semibold text-slate-500 hover:text-slate-800">Clear</button>
                                </div>
                                <div wire:ignore class="relative">
                                    <canvas x-ref="canvas"
                                            @pointerdown="start($event)" @pointermove="move($event)" @pointerup="end()" @pointerleave="end()" @pointercancel="end()"
                                            class="h-44 w-full touch-none rounded-lg border-2 border-dashed border-slate-300 bg-white"></canvas>
                                    <span x-show="!hasInk" class="pointer-events-none absolute inset-0 flex items-center justify-center text-sm text-slate-400">Draw your signature here</span>
                                    <span class="pointer-events-none absolute right-4 bottom-10 left-4 border-b border-slate-200"></span>
                                </div>
                                @error('signatureData')<p class="error">{{ $message }}</p>@enderror
                            </div>

                            <label class="flex items-start gap-2 text-sm text-slate-600">
                                <input type="checkbox" wire:model="agree" class="mt-0.5 rounded border-slate-300">
                                <span>I'm authorised to sign on behalf of {{ $client->company_name }} and agree to the scope, pricing ({{ $p->money($this->pricing['contract_value']) }}) and terms above.</span>
                            </label>
                            @error('agree')<p class="error">{{ $message }}</p>@enderror

                            <button type="button" @click="submit()" wire:loading.attr="disabled" wire:target="signProposal" class="btn-primary w-full py-3 text-base">
                                <span wire:loading.remove wire:target="signProposal">Approve &amp; Sign Contract</span>
                                <span wire:loading wire:target="signProposal">Signing…</span>
                            </button>
                        </div>

                        <div x-data="{ open: false }" class="mt-6 border-t border-slate-100 pt-4 text-center">
                            <button type="button" @click="open = !open" class="text-xs text-slate-400 hover:text-slate-600">Not the right fit? Decline this proposal</button>
                            <div x-show="open" x-cloak class="mt-3 space-y-2 text-left">
                                <textarea wire:model="declineReason" rows="2" class="input text-sm" placeholder="Optional: what would make this work for you?"></textarea>
                                <button type="button" wire:click="decline" wire:confirm="Decline this proposal?" class="btn-ghost w-full text-xs">Decline proposal</button>
                            </div>
                        </div>
                    @endif
                </div>

                {{-- Booking --}}
                <aside class="card flex flex-col p-6">
                    <div class="mb-1 text-sm font-semibold text-brand-highlight">{{ $p->status === 'accepted' ? 'Next step' : 'Have questions?' }}</div>
                    <h3 class="text-lg font-semibold text-slate-900">Book a strategy session</h3>
                    <p class="mt-1 text-sm text-slate-600">
                        {{ $p->status === 'accepted' ? 'Pick a time for your kickoff call so we can hit the ground running.' : 'Walk through the proposal with '.($p->collaborator_name ?: 'our team').' before you decide.' }}
                    </p>
                    @if ($bookingUrl)
                        <a href="{{ $bookingUrl }}" target="_blank" rel="noopener noreferrer" class="btn-dark mt-5">Book a call →</a>
                    @endif
                    <a href="mailto:{{ $settings['agency_email'] }}?subject={{ rawurlencode('Re: '.$p->title) }}" class="btn-ghost mt-2">Email {{ $settings['agency_email'] }}</a>
                </aside>
            </div>
        </section>
    </main>

    <footer class="border-t border-slate-200 bg-white">
        <div class="mx-auto flex max-w-6xl flex-wrap items-center justify-between gap-2 px-4 py-6 text-xs text-slate-500 sm:px-6">
            <span>© {{ now()->year }} {{ $settings['agency_name'] }}. Confidential — prepared exclusively for {{ $client->company_name }}.</span>
            @if ($settings['agency_website'])<a href="{{ $settings['agency_website'] }}" target="_blank" rel="noopener" class="hover:text-slate-700">{{ preg_replace('#^https?://#', '', $settings['agency_website']) }}</a>@endif
        </div>
    </footer>
</div>
