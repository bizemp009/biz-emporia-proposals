@php
    $visibleGroups = collect($attention)->filter(fn ($g) => $g['count'] > 0);
    $attentionTotal = $visibleGroups->sum('count');
@endphp
<section aria-labelledby="insights-heading" class="mb-6" x-data="{ open: $persist(true).as('dashboard-insights-open') }">
    <div class="mb-3 flex items-center justify-between gap-3">
        <h2 id="insights-heading" class="text-sm font-semibold tracking-wide text-slate-500 uppercase">Insights</h2>
        <button type="button" @click="open = !open" :aria-expanded="open.toString()" aria-controls="insights-body" class="btn-xs">
            <span x-text="open ? 'Hide' : 'Show'"></span>
            <svg class="size-3.5 transition" :class="open ? 'rotate-180' : ''" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24" aria-hidden="true"><path d="m6 9 6 6 6-6"/></svg>
        </button>
    </div>

    <div id="insights-body" x-show="open" x-collapse>
        <div class="grid grid-cols-1 gap-4 lg:grid-cols-3">
            {{-- Needs attention --}}
            <div class="card p-4 sm:p-5 lg:col-span-2">
                <div class="mb-4 flex items-center gap-2">
                    <h3 class="font-semibold text-slate-900">Needs attention</h3>
                    @if ($attentionTotal)
                        <span class="rounded-full bg-amber-50 px-2 py-0.5 text-xs font-semibold text-amber-700 ring-1 ring-amber-200">{{ $attentionTotal }}</span>
                    @endif
                </div>

                @if ($visibleGroups->isEmpty())
                    <x-empty-state title="All clear" icon="M5 13l4 4L19 7" class="py-6">
                        No proposals waiting on a follow-up, expiring this week, or sitting in draft.
                    </x-empty-state>
                @else
                    <div class="grid grid-cols-1 gap-x-6 gap-y-5 sm:grid-cols-2">
                        @foreach ($visibleGroups as $key => $group)
                            <div wire:key="att-{{ $key }}">
                                <div class="mb-2 flex items-baseline justify-between gap-2">
                                    <div class="text-sm font-semibold text-slate-800">{{ $group['title'] }} <span class="font-normal text-slate-400">· {{ $group['count'] }}</span></div>
                                </div>
                                <p class="-mt-1.5 mb-2 text-xs text-slate-400">{{ $group['hint'] }}</p>
                                <ul class="divide-y divide-slate-100 rounded-lg ring-1 ring-slate-100">
                                    @foreach ($group['items'] as [$p, $when])
                                        <li wire:key="att-{{ $key }}-{{ $p->id }}" class="flex items-center gap-2 px-3 py-2">
                                            <div class="min-w-0 flex-1">
                                                <a href="{{ route('admin.proposals.edit', $p) }}" wire:navigate class="block truncate text-sm font-medium text-slate-900 hover:text-brand-accent">{{ $p->client->brand_name ?: $p->client->company_name }}</a>
                                                <div class="truncate text-xs text-slate-500">{{ $when }} · {{ $p->money($p->total_amount) }}</div>
                                            </div>
                                            @switch($key)
                                                @case('expiring')
                                                    <button type="button" wire:click="extend({{ $p->id }}, 7)" class="btn-xs shrink-0" title="Extend validity by 7 days">+7 days</button>
                                                    @break
                                                @case('stale')
                                                    @if ($p->status === 'draft')
                                                        <button type="button" wire:click="markSent({{ $p->id }})" class="btn-xs shrink-0">Mark sent</button>
                                                    @endif
                                                    @break
                                                @default
                                                    <button type="button" x-data="copyLink(@js($p->public_url))"
                                                            @click="copy().then(() => $dispatch('notify', { message: 'Link copied — paste it into your follow-up.' }))"
                                                            class="btn-xs shrink-0" title="Copy the client link">Copy link</button>
                                            @endswitch
                                        </li>
                                    @endforeach
                                </ul>
                                @if ($group['count'] > count($group['items']))
                                    <p class="mt-1.5 text-xs text-slate-400">+{{ $group['count'] - count($group['items']) }} more</p>
                                @endif
                            </div>
                        @endforeach
                    </div>
                @endif
            </div>

            {{-- Activity feed --}}
            <div class="card flex flex-col p-4 sm:p-5">
                <h3 class="mb-4 font-semibold text-slate-900">Recent activity</h3>
                @if ($activities->isEmpty())
                    <p class="text-sm text-slate-400">Nothing yet. Client views, add-on changes and signatures will show up here.</p>
                @else
                    <ol class="-my-1 max-h-96 space-y-3 overflow-y-auto pr-1">
                        @foreach ($activities as $a)
                            <li wire:key="act-{{ $a->id }}" class="flex gap-3">
                                <span @class([
                                    'mt-1.5 size-2 shrink-0 rounded-full',
                                    'bg-emerald-500' => $a->event === 'signed',
                                    'bg-rose-500' => $a->event === 'rejected',
                                    'bg-brand-accent' => $a->isClientEvent() && ! in_array($a->event, ['signed', 'rejected'], true),
                                    'bg-slate-300' => ! $a->isClientEvent(),
                                ]) aria-hidden="true"></span>
                                <div class="min-w-0 text-sm">
                                    @if ($a->proposal)
                                        <a href="{{ route('admin.proposals.edit', $a->proposal_id) }}" wire:navigate class="font-medium text-slate-900 hover:text-brand-accent">{{ $a->proposal->client?->brand_name ?: $a->proposal->client?->company_name }}</a>
                                    @endif
                                    <span class="text-slate-600">{{ $a->description }}</span>
                                    <div class="text-xs text-slate-400">
                                        <time datetime="{{ $a->created_at->toIso8601String() }}" title="{{ $a->created_at->format('d M Y, H:i') }}">{{ $a->created_at->diffForHumans() }}</time>
                                        @if ($a->isClientEvent())<span class="ml-1 rounded bg-brand-accent/10 px-1 text-[10px] font-semibold text-brand-accent uppercase">Client</span>@endif
                                    </div>
                                </div>
                            </li>
                        @endforeach
                    </ol>
                @endif
            </div>
        </div>

    </div>
</section>
