<div>
    <div class="mb-6">
        <a href="{{ route('admin.templates') }}" wire:navigate class="text-sm text-slate-500 hover:text-slate-700">← All templates</a>
        <h1 class="mt-1 text-2xl font-bold text-slate-900">Combine templates</h1>
        <p class="text-sm text-slate-500">Pick two templates and choose where each section comes from, for example <em>E-com SEO</em> + <em>Ads</em> → <em>E-com Ads</em>. This creates a new template; the two originals stay unchanged.</p>
    </div>

    <form wire:submit="create" class="space-y-6">
        <section class="card p-5">
            <div class="grid gap-4 sm:grid-cols-2">
                <div>
                    <label class="label">Template A</label>
                    <select wire:model.live="aId" class="input">
                        <option value="">— Choose —</option>
                        @foreach ($templates as $t)<option value="{{ $t->id }}">{{ $t->name }}</option>@endforeach
                    </select>
                    @error('aId')<p class="error">{{ $message }}</p>@enderror
                </div>
                <div>
                    <label class="label">Template B</label>
                    <select wire:model.live="bId" class="input">
                        <option value="">— Choose —</option>
                        @foreach ($templates as $t)<option value="{{ $t->id }}">{{ $t->name }}</option>@endforeach
                    </select>
                    @error('bId')<p class="error">{{ $message }}</p>@enderror
                </div>
            </div>
        </section>

        @if ($a && $b && $a->id !== $b->id)
            @php
                $rows = [
                    'summary' => ['Executive summary', ['a' => 'A', 'b' => 'B', 'both' => 'Both (A then B)']],
                    'audit' => ['Audit findings', ['a' => 'A', 'b' => 'B', 'both' => 'Both (A then B)']],
                    'screenshots' => ['Audit screenshots', ['a' => 'A ('.count($a->audit_screenshots ?? []).')', 'b' => 'B ('.count($b->audit_screenshots ?? []).')', 'both' => 'Both', 'none' => 'None']],
                    'roadmap' => ['Roadmap', ['a' => 'A ('.count($a->roadmap_milestones ?? []).' phases)', 'b' => 'B ('.count($b->roadmap_milestones ?? []).' phases)', 'merge' => 'Merge phase by phase', 'append' => 'A’s phases then B’s']],
                    'roi' => ['ROI defaults', ['a' => 'A', 'b' => 'B']],
                    'tenure' => ['Tenure', ['a' => 'A ('.$a->tenure_months.' mo)', 'b' => 'B ('.$b->tenure_months.' mo)']],
                    'settings' => ['Title, strategist, currency, booking link & validity', ['a' => 'A', 'b' => 'B']],
                ];
            @endphp

            <section class="card p-5">
                <h2 class="mb-4 font-semibold text-slate-900">Where each section comes from</h2>
                <div class="divide-y divide-slate-100">
                    @foreach ($rows as $key => [$label, $options])
                        <div wire:key="pick-{{ $key }}" class="flex flex-col gap-2 py-3 sm:flex-row sm:items-center sm:justify-between">
                            <div class="text-sm font-medium text-slate-700">{{ $label }}</div>
                            <div class="flex flex-wrap gap-1.5">
                                @foreach ($options as $value => $text)
                                    <label class="cursor-pointer rounded-full px-3 py-1 text-xs font-semibold ring-1 ring-slate-200 text-slate-600 hover:bg-slate-50 has-[:checked]:bg-brand-accent has-[:checked]:text-white has-[:checked]:ring-brand-accent">
                                        <input type="radio" wire:model="pick.{{ $key }}" value="{{ $value }}" class="sr-only">{{ $text }}
                                    </label>
                                @endforeach
                            </div>
                        </div>
                    @endforeach
                </div>
            </section>

            <section class="card p-5">
                <h2 class="mb-1 font-semibold text-slate-900">Services to include</h2>
                <p class="mb-4 text-xs text-slate-500">Prices, copy and optional/selected flags come with each line. If both templates have the same catalog service, only A's copy is ticked by default.</p>
                <div class="grid gap-5 md:grid-cols-2">
                    @foreach (['a' => [$a, $linesA], 'b' => [$b, $linesB]] as $side => [$tpl, $lines])
                        <div>
                            <div class="mb-2 text-xs font-semibold tracking-wide text-slate-400 uppercase">{{ strtoupper($side) }} · {{ $tpl->name }}</div>
                            <div class="space-y-2">
                                @forelse ($lines as $i => $line)
                                    <label wire:key="svc-{{ $side }}-{{ $tpl->id }}-{{ $i }}" class="flex items-start gap-2 rounded-lg border border-slate-200 p-3 text-sm hover:bg-slate-50 has-[:checked]:border-brand-accent has-[:checked]:bg-brand-accent/5">
                                        <input type="checkbox" value="{{ $side }}:{{ $i }}" wire:model="serviceKeys" class="mt-0.5 rounded border-slate-300">
                                        <span class="min-w-0 flex-1">
                                            <span class="font-medium">{{ $line['name'] }}</span>
                                            <span class="block text-xs text-slate-500">
                                                {{ \App\Models\Proposal::formatMoney((float) $line['price'], $tpl->currency ?: \App\Models\Setting::get('default_currency', 'INR')) }}
                                                · {{ ['one_time' => 'One-time', 'monthly' => 'Monthly', 'quarterly' => 'Quarterly'][$line['billing_type']] ?? $line['billing_type'] }}
                                                @if ($line['is_optional']) · Optional @endif
                                            </span>
                                        </span>
                                    </label>
                                @empty
                                    <p class="text-sm text-slate-400">No services.</p>
                                @endforelse
                            </div>
                        </div>
                    @endforeach
                </div>
            </section>

            <section class="card p-5">
                <h2 class="mb-4 font-semibold text-slate-900">Case studies</h2>
                @if ($caseStudies->isEmpty())
                    <p class="text-sm text-slate-400">Neither template has case studies.</p>
                @else
                    <div class="grid gap-2 sm:grid-cols-2">
                        @foreach ($caseStudies as $cs)
                            <label wire:key="cs-{{ $cs->id }}" class="flex items-start gap-2 rounded-lg border border-slate-200 p-3 text-sm hover:bg-slate-50 has-[:checked]:border-brand-accent has-[:checked]:bg-brand-accent/5">
                                <input type="checkbox" value="{{ $cs->id }}" wire:model="caseStudyIds" class="mt-0.5 rounded border-slate-300">
                                <span><span class="font-medium">{{ $cs->title }}</span><span class="block text-xs text-slate-500">{{ $cs->industry }}</span></span>
                            </label>
                        @endforeach
                    </div>
                @endif
            </section>

            <section class="card p-5">
                <div class="grid gap-4 sm:grid-cols-2">
                    <div><label class="label">New template name *</label><input wire:model="name" class="input" placeholder="e.g. E-commerce Ads (90 days)">@error('name')<p class="error">{{ $message }}</p>@enderror</div>
                    <div><label class="label">Description</label><input wire:model="description" class="input">@error('description')<p class="error">{{ $message }}</p>@enderror</div>
                </div>
                <div class="mt-5 flex justify-end">
                    <button class="btn-primary" wire:loading.attr="disabled">
                        <span wire:loading.remove wire:target="create">Create combined template</span>
                        <span wire:loading wire:target="create">Creating…</span>
                    </button>
                </div>
            </section>
        @elseif ($a && $b)
            <div class="card p-6 text-center text-sm text-slate-500">Pick two different templates.</div>
        @else
            <div class="card p-6 text-center text-sm text-slate-500">Choose template A and template B to start.</div>
        @endif
    </form>
</div>
