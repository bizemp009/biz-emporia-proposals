@php
    $money = fn ($v) => \App\Models\Proposal::formatMoney($v, $currency);
    $tiles = [
        ['Total proposals', number_format($stats['total']), null, 'M9 12h6m-6 4h6M7 4h7l5 5v11a1 1 0 0 1-1 1H7a1 1 0 0 1-1-1V5a1 1 0 0 1 1-1Z', 'bg-slate-100 text-slate-600'],
        ['Open (sent / viewed)', number_format($stats['open']), 'Awaiting a decision', 'M2 12s3.6-7 10-7 10 7 10 7-3.6 7-10 7S2 12 2 12Zm10 3a3 3 0 1 0 0-6 3 3 0 0 0 0 6Z', 'bg-sky-50 text-sky-700'],
        ['Open pipeline', $money($stats['pipeline_value']), 'Value of open proposals', 'M3 3v18h18M7 15l4-4 3 3 5-6', 'bg-amber-50 text-amber-700'],
        ['Won value', $money($stats['accepted_value']), $stats['accepted'].' signed', 'M20 6 9 17l-5-5', 'bg-emerald-50 text-emerald-700'],
        ['Win rate', $stats['win_rate'] !== null ? $stats['win_rate'].'%' : '—', $stats['decided'] ? 'of '.$stats['decided'].' decided' : 'No decisions yet', 'M12 2 15 8.5l7 .9-5 4.9 1.2 7L12 18l-6.2 3.3L7 14.3 2 9.4l7-.9L12 2Z', 'bg-brand-accent/10 text-brand-accent'],
    ];
    $statusLabels = ['' => 'All'] + array_combine(\App\Models\Proposal::STATUSES, array_map('ucfirst', \App\Models\Proposal::STATUSES));
@endphp
<div>
    {{-- Header --}}
    <div class="mb-6 flex flex-wrap items-end justify-between gap-4">
        <div>
            <h1 class="text-2xl font-bold text-slate-900">Proposals</h1>
            <p class="text-sm text-slate-500">Create, send and track interactive client proposals.</p>
        </div>
        <a href="{{ route('admin.proposals.create') }}" wire:navigate class="btn-primary hidden sm:inline-flex">
            <svg class="size-4" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24" aria-hidden="true"><path d="M12 5v14M5 12h14"/></svg>
            Create proposal
        </a>
    </div>

    {{-- KPI tiles --}}
    <div class="mb-6 grid grid-cols-2 gap-3 sm:grid-cols-3 xl:grid-cols-5">
        @foreach ($tiles as $i => [$label, $value, $sub, $icon, $tone])
            <div @class(['card min-w-0 p-4', 'col-span-2 sm:col-span-1' => $i === 4])>
                <div class="flex items-center justify-between gap-2">
                    <div class="truncate text-xs font-medium text-slate-500">{{ $label }}</div>
                    <span class="flex size-7 shrink-0 items-center justify-center rounded-lg {{ $tone }}">
                        <svg class="size-4" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" viewBox="0 0 24 24" aria-hidden="true"><path d="{{ $icon }}"/></svg>
                    </span>
                </div>
                <div class="mt-2 truncate text-lg font-bold text-slate-900 sm:text-xl" title="{{ $value }}">{{ $value }}</div>
                @if ($sub)<div class="truncate text-xs text-slate-400">{{ $sub }}</div>@endif
            </div>
        @endforeach
    </div>

    <livewire:admin.proposals.insights wire:key="dashboard-insights" />

    {{-- Toolbar --}}
    <div class="mb-3 space-y-3" x-data="{ more: @js($advancedCount > 0) }">
        <div class="flex flex-wrap items-center gap-2">
            <div class="relative w-full min-w-0 sm:w-auto sm:flex-1 sm:max-w-xs">
                <svg class="pointer-events-none absolute top-1/2 left-3 size-4 -translate-y-1/2 text-slate-400" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" aria-hidden="true"><circle cx="11" cy="11" r="7"/><path d="m20 20-3.5-3.5"/></svg>
                <input type="search" wire:model.live.debounce.300ms="search" placeholder="Search client, title, link, strategist…" aria-label="Search proposals" class="input pl-9">
            </div>
            <button type="button" @click="more = !more" :aria-expanded="more.toString()" aria-controls="more-filters" class="btn-ghost px-3">
                <svg class="size-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" aria-hidden="true"><path d="M3 5h18M6 12h12M10 19h4"/></svg>
                Filters
                @if ($advancedCount)<span class="rounded-full bg-brand-accent px-1.5 text-[11px] font-bold text-white">{{ $advancedCount }}</span>@endif
            </button>
            <label class="sr-only" for="sort-select">Sort by</label>
            <select id="sort-select" wire:key="sort-{{ $sort }}-{{ $dir }}" class="input w-auto py-2 pr-8"
                    @change="const [c, d] = $event.target.value.split(':'); $wire.applySort(c, d)">
                @foreach (\App\Support\ProposalFilters::SORT_LABELS as $key => $label)
                    @foreach (['desc', 'asc'] as $d)
                        @php($dirLabel = in_array($key, ['client', 'status'], true) ? ($d === 'asc' ? 'A→Z' : 'Z→A') : (in_array($key, ['value']) ? ($d === 'desc' ? 'high→low' : 'low→high') : ($d === 'desc' ? 'newest' : 'oldest')))
                        <option value="{{ $key }}:{{ $d }}" @selected($sort === $key && $dir === $d)>{{ $label }} ({{ $dirLabel }})</option>
                    @endforeach
                @endforeach
            </select>
            <a href="{{ $exportUrl }}" class="btn-ghost px-3" title="Download the filtered list as CSV">
                <svg class="size-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" aria-hidden="true"><path d="M12 4v12m0 0-4-4m4 4 4-4M5 20h14"/></svg>
                <span class="hidden sm:inline">Export CSV</span><span class="sm:hidden">CSV</span>
            </a>
        </div>

        {{-- Advanced filters --}}
        <div id="more-filters" x-show="more" x-collapse x-cloak>
            <div class="card grid gap-3 p-4 sm:grid-cols-2 lg:grid-cols-5">
                <div>
                    <label class="label" for="f-strategist">Strategist</label>
                    <select id="f-strategist" wire:model.live="strategist" class="input">
                        <option value="">Anyone</option>
                        <option value="__none">Unassigned</option>
                        @foreach ($strategists as $s)<option value="{{ $s }}">{{ $s }}</option>@endforeach
                    </select>
                </div>
                <div>
                    <label class="label" for="f-from">Created from</label>
                    <input id="f-from" type="date" wire:model.live="from" class="input">
                </div>
                <div>
                    <label class="label" for="f-to">Created to</label>
                    <input id="f-to" type="date" wire:model.live="to" class="input">
                </div>
                <div>
                    <label class="label" for="f-min">Min value</label>
                    <input id="f-min" type="number" min="0" step="1000" inputmode="numeric" wire:model.live.debounce.500ms="min" class="input" placeholder="0">
                </div>
                <div>
                    <label class="label" for="f-max">Max value</label>
                    <input id="f-max" type="number" min="0" step="1000" inputmode="numeric" wire:model.live.debounce.500ms="max" class="input" placeholder="Any">
                </div>
            </div>
        </div>

        {{-- Status chips: one swipeable row on phones --}}
        <div class="no-scrollbar -mx-4 flex gap-1.5 overflow-x-auto px-4 sm:mx-0 sm:flex-wrap sm:overflow-visible sm:px-0" role="group" aria-label="Filter by status">
            @foreach ($statusLabels as $s => $label)
                <button type="button" wire:click="$set('status', '{{ $s }}')" aria-pressed="{{ $status === $s ? 'true' : 'false' }}" class="{{ $status === $s ? 'chip-on' : 'chip-off' }}">
                    {{ $label }} <span class="opacity-60">{{ $s === '' ? $stats['total'] : ($counts[$s] ?? 0) }}</span>
                </button>
            @endforeach
        </div>

        @if ($hasFilters)
            <div class="flex items-center gap-2 text-xs text-slate-500">
                <span>{{ $proposals->total() }} {{ str('result')->plural($proposals->total()) }}</span>
                <button type="button" wire:click="clearFilters" class="font-semibold text-brand-accent hover:underline">Clear all filters</button>
            </div>
        @endif
    </div>

    {{-- List --}}
    <div class="relative"
         x-data="{
            ids() { return [...new Set([...$root.querySelectorAll('[data-row-check]')].map(e => e.value))] },
            allChecked() { const ids = this.ids(); return ids.length > 0 && ids.every(i => $wire.selected.includes(i)) },
            toggleAll(on) { const ids = this.ids(); $wire.selected = on ? [...new Set([...$wire.selected, ...ids])] : $wire.selected.filter(i => !ids.includes(i)) },
         }">
        {{-- loading indicator --}}
        <div wire:loading.delay class="absolute inset-x-0 -top-1 z-10 h-0.5 overflow-hidden rounded bg-brand-accent/20" aria-hidden="true">
            <div class="h-full w-1/3 animate-pulse bg-brand-accent"></div>
        </div>

        <div wire:loading.delay.class="opacity-60 pointer-events-none" class="transition-opacity">
            @if ($proposals->isEmpty())
                <div class="card">
                    @if ($hasFilters)
                        <x-empty-state title="No proposals match these filters" icon="M21 21l-4.3-4.3M11 18a7 7 0 1 1 0-14 7 7 0 0 1 0 14Z">
                            Try a different search or remove some filters.
                            <x-slot:actions><button type="button" wire:click="clearFilters" class="btn-ghost">Clear filters</button></x-slot:actions>
                        </x-empty-state>
                    @else
                        <x-empty-state title="No proposals yet">
                            Build your first interactive proposal, or start from a template.
                            <x-slot:actions>
                                <a href="{{ route('admin.proposals.create') }}" wire:navigate class="btn-primary">Create proposal</a>
                                <a href="{{ route('admin.templates') }}" wire:navigate class="btn-ghost">Browse templates</a>
                            </x-slot:actions>
                        </x-empty-state>
                    @endif
                </div>
            @else
                {{-- Desktop table --}}
                <div class="card hidden overflow-hidden lg:block">
                    <table class="min-w-full divide-y divide-slate-200 text-sm">
                        <thead class="bg-slate-50 text-left text-xs font-semibold text-slate-500">
                            <tr>
                                <th scope="col" class="w-10 py-3 pl-4">
                                    <input type="checkbox" class="rounded border-slate-300" aria-label="Select all on this page"
                                           :checked="allChecked()" @change="toggleAll($event.target.checked)">
                                </th>
                                <x-sort-th column="client" :sort="$sort" :dir="$dir">Client / proposal</x-sort-th>
                                <th scope="col" class="hidden px-4 py-3 tracking-wide uppercase xl:table-cell">Strategist</th>
                                <x-sort-th column="status" :sort="$sort" :dir="$dir">Status</x-sort-th>
                                <x-sort-th column="value" :sort="$sort" :dir="$dir" align="right">Value</x-sort-th>
                                <x-sort-th column="expiry" :sort="$sort" :dir="$dir">Expires</x-sort-th>
                                <th scope="col" class="px-4 py-3 text-right tracking-wide uppercase"><span class="sr-only">Actions</span></th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100">
                            @foreach ($proposals as $p)
                                <tr wire:key="row-{{ $p->id }}" class="align-top hover:bg-slate-50/60" :class="$wire.selected.includes('{{ $p->id }}') && 'bg-brand-accent/5'">
                                    <td class="py-3.5 pl-4">
                                        <input type="checkbox" data-row-check value="{{ $p->id }}" wire:model="selected" class="rounded border-slate-300" aria-label="Select proposal for {{ $p->client->company_name }}">
                                    </td>
                                    <td class="max-w-0 px-4 py-3 xl:w-2/5">
                                        <a href="{{ route('admin.proposals.edit', $p) }}" wire:navigate class="block truncate font-semibold text-slate-900 hover:text-brand-accent">
                                            {{ $p->client->company_name }}@if ($p->client->brand_name)<span class="font-normal text-slate-500"> · {{ $p->client->brand_name }}</span>@endif
                                        </a>
                                        <div class="truncate text-xs text-slate-500">{{ $p->title }}</div>
                                        <div class="truncate font-mono text-[11px] text-slate-400">/proposal/{{ $p->slug }}</div>
                                        <div class="mt-1 text-xs xl:hidden">
                                            @if ($p->collaborator_name)<span class="text-brand-accent">{{ $p->collaborator_name }}</span>@else<span class="text-slate-400">Unassigned</span>@endif
                                        </div>
                                    </td>
                                    <td class="hidden px-4 py-3 xl:table-cell">
                                        @if ($p->collaborator_name)
                                            <span class="inline-flex items-center rounded-md bg-brand-accent/10 px-2 py-0.5 text-xs font-medium text-brand-accent">{{ $p->collaborator_name }}</span>
                                        @else
                                            <span class="text-xs text-slate-400">Unassigned</span>
                                        @endif
                                    </td>
                                    <td class="px-4 py-3 whitespace-nowrap">
                                        <x-status-badge :status="$p->status" />
                                        @if ($p->activities_max_created_at)
                                            <div class="mt-1 text-xs text-slate-400" title="Last activity">{{ \Illuminate\Support\Carbon::parse($p->activities_max_created_at)->diffForHumans() }}</div>
                                        @endif
                                    </td>
                                    <td class="px-4 py-3 text-right font-semibold whitespace-nowrap text-slate-900">{{ $p->money($p->total_amount) }}</td>
                                    <td class="px-4 py-3 text-xs whitespace-nowrap">
                                        @if ($p->expires_at)
                                            <span class="{{ $p->isPastExpiry() ? 'text-rose-600' : ($p->expires_at->lt(now()->addDays(7)) && $p->canExtend() ? 'font-semibold text-amber-700' : 'text-slate-500') }}">{{ $p->expires_at->format('d M Y') }}</span>
                                        @else
                                            <span class="text-slate-400">—</span>
                                        @endif
                                    </td>
                                    <td class="px-4 py-3">@include('livewire.admin.proposals.partials.row-actions', ['p' => $p])</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>

                {{-- Phone / tablet cards --}}
                <div class="lg:hidden">
                    <label class="mb-2 flex items-center gap-2 px-1 text-xs font-semibold text-slate-500">
                        <input type="checkbox" class="rounded border-slate-300" :checked="allChecked()" @change="toggleAll($event.target.checked)">
                        Select all on this page
                    </label>
                    <ul class="grid grid-cols-1 gap-3 md:grid-cols-2">
                        @foreach ($proposals as $p)
                            <li wire:key="card-{{ $p->id }}" class="card flex min-w-0 gap-3 p-4" :class="$wire.selected.includes('{{ $p->id }}') && 'ring-2 ring-brand-accent/40'">
                                <input type="checkbox" data-row-check value="{{ $p->id }}" wire:model="selected" class="mt-1 shrink-0 self-start rounded border-slate-300" aria-label="Select proposal for {{ $p->client->company_name }}">
                                <div class="min-w-0 flex-1">
                                    <div class="flex items-start justify-between gap-2">
                                        <div class="min-w-0">
                                            <a href="{{ route('admin.proposals.edit', $p) }}" wire:navigate class="block truncate font-semibold text-slate-900 hover:text-brand-accent">{{ $p->client->company_name }}</a>
                                            <div class="truncate text-xs text-slate-500">{{ $p->client->brand_name ? $p->client->brand_name.' · ' : '' }}{{ $p->title }}</div>
                                        </div>
                                        <x-status-badge :status="$p->status" class="shrink-0" />
                                    </div>
                                    <dl class="mt-3 grid grid-cols-2 gap-x-4 gap-y-2 text-xs">
                                        <div><dt class="text-slate-400">Value</dt><dd class="font-semibold text-slate-900">{{ $p->money($p->total_amount) }}</dd></div>
                                        <div class="min-w-0"><dt class="text-slate-400">Strategist</dt><dd class="truncate text-slate-700">{{ $p->collaborator_name ?: 'Unassigned' }}</dd></div>
                                        <div><dt class="text-slate-400">Expires</dt>
                                            <dd class="{{ $p->isPastExpiry() ? 'text-rose-600' : 'text-slate-700' }}">{{ $p->expires_at?->format('d M Y') ?? '—' }}</dd></div>
                                        <div><dt class="text-slate-400">Last activity</dt>
                                            <dd class="text-slate-700">{{ $p->activities_max_created_at ? \Illuminate\Support\Carbon::parse($p->activities_max_created_at)->diffForHumans() : '—' }}</dd></div>
                                    </dl>
                                    <div class="mt-3 flex items-center justify-between gap-2 border-t border-slate-100 pt-3">
                                        <span class="min-w-0 truncate font-mono text-[11px] text-slate-400">/proposal/{{ $p->slug }}</span>
                                        @include('livewire.admin.proposals.partials.row-actions', ['p' => $p])
                                    </div>
                                </div>
                            </li>
                        @endforeach
                    </ul>
                </div>
            @endif
        </div>

        <div class="mt-4">{{ $proposals->links() }}</div>

        {{-- Bulk action bar --}}
        <div x-show="$wire.selected.length > 0" x-cloak x-transition
             class="fixed inset-x-3 bottom-3 z-30 mx-auto flex max-w-3xl flex-wrap items-center gap-2 rounded-xl bg-gray-900 px-4 py-3 text-sm text-white shadow-2xl ring-1 ring-white/10 lg:left-[17rem]"
             role="region" aria-label="Bulk actions">
            <span class="font-semibold"><span x-text="$wire.selected.length"></span> selected</span>
            <button type="button" @click="$wire.selected = []" class="text-xs text-white/60 underline hover:text-white">Clear</button>
            <div class="ml-auto flex flex-wrap gap-1.5">
                <button type="button" wire:click="bulkMarkSent" class="rounded-md bg-white/10 px-2.5 py-1.5 text-xs font-semibold hover:bg-white/20">Mark sent</button>
                <button type="button" wire:click="bulkExtend(7)" class="rounded-md bg-white/10 px-2.5 py-1.5 text-xs font-semibold hover:bg-white/20">+7 days</button>
                <button type="button" wire:click="bulkExtend(30)" class="rounded-md bg-white/10 px-2.5 py-1.5 text-xs font-semibold hover:bg-white/20">+30 days</button>
                <button type="button"
                        @click="$confirm({ title: 'Delete ' + $wire.selected.length + ' proposal(s)?', message: 'They will be permanently deleted along with their activity logs.', confirmText: 'Delete' }).then(ok => ok && $wire.bulkDelete())"
                        class="rounded-md bg-rose-600 px-2.5 py-1.5 text-xs font-semibold hover:brightness-110">Delete</button>
            </div>
        </div>
    </div>

    <livewire:admin.proposals.analytics wire:key="dashboard-analytics" />
</div>
