@php($money = fn ($v) => \App\Models\Proposal::formatMoney((float) $v, $currency))
<div>
    <div class="mb-6 flex flex-wrap items-end justify-between gap-4">
        <div>
            <h1 class="text-2xl font-bold text-slate-900">Clients</h1>
            <p class="text-sm text-slate-500">Company and contact records used across proposals.</p>
        </div>
        <button wire:click="create" class="btn-primary">
            <svg class="size-4" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24" aria-hidden="true"><path d="M12 5v14M5 12h14"/></svg>
            New client
        </button>
    </div>

    <div class="mb-3 flex flex-wrap items-center gap-2">
        <div class="relative min-w-0 flex-1 sm:max-w-xs">
            <svg class="pointer-events-none absolute top-1/2 left-3 size-4 -translate-y-1/2 text-slate-400" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" aria-hidden="true"><circle cx="11" cy="11" r="7"/><path d="m20 20-3.5-3.5"/></svg>
            <input type="search" wire:model.live.debounce.300ms="search" placeholder="Search clients…" aria-label="Search clients" class="input pl-9">
        </div>
        <a href="{{ $exportUrl }}" class="btn-ghost px-3" title="Download as CSV">
            <svg class="size-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" aria-hidden="true"><path d="M12 4v12m0 0-4-4m4 4 4-4M5 20h14"/></svg>
            <span class="hidden sm:inline">Export CSV</span><span class="sm:hidden">CSV</span>
        </a>
    </div>

    <div wire:loading.delay.class="opacity-60" wire:target="search,gotoPage,nextPage,previousPage,delete" class="transition-opacity">
        @if ($clients->isEmpty())
            <div class="card">
                <x-empty-state :title="$search ? 'No clients match “'.$search.'”' : 'No clients yet'" icon="M17 20h5v-2a4 4 0 0 0-5-3.87M9 20H2v-2a4 4 0 0 1 5-3.87m10-4a4 4 0 1 1-8 0 4 4 0 0 1 8 0Z">
                    {{ $search ? 'Try another name, contact or email.' : 'Clients are also created automatically when you generate a proposal.' }}
                    <x-slot:actions>
                        @if ($search)
                            <button type="button" wire:click="$set('search', '')" class="btn-ghost">Clear search</button>
                        @else
                            <button type="button" wire:click="create" class="btn-primary">Add a client</button>
                        @endif
                    </x-slot:actions>
                </x-empty-state>
            </div>
        @else
            {{-- Desktop table --}}
            <div class="card hidden overflow-hidden md:block">
                <table class="min-w-full divide-y divide-slate-200 text-sm">
                    <thead class="bg-slate-50 text-left text-xs font-semibold tracking-wide text-slate-500 uppercase">
                        <tr>
                            <th scope="col" class="px-4 py-3">Company / Brand</th>
                            <th scope="col" class="px-4 py-3">Contact</th>
                            <th scope="col" class="hidden px-4 py-3 lg:table-cell">Industry</th>
                            <th scope="col" class="px-4 py-3 text-right">Proposals</th>
                            <th scope="col" class="px-4 py-3 text-right">Won</th>
                            <th scope="col" class="px-4 py-3"><span class="sr-only">Actions</span></th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        @foreach ($clients as $c)
                            <tr wire:key="c-{{ $c->id }}" class="hover:bg-slate-50/60">
                                <td class="max-w-0 px-4 py-3 lg:w-1/3">
                                    <div class="truncate font-semibold text-slate-900">{{ $c->company_name }}</div>
                                    @if ($c->brand_name)<div class="truncate text-xs text-slate-500">{{ $c->brand_name }}</div>@endif
                                    @if ($c->website)<a href="{{ $c->website }}" target="_blank" rel="noopener" class="block truncate text-xs text-brand-accent hover:underline">{{ preg_replace('#^https?://#', '', $c->website) }}</a>@endif
                                </td>
                                <td class="max-w-0 px-4 py-3">
                                    <div class="truncate">{{ $c->contact_person }}</div>
                                    <a href="mailto:{{ $c->email }}" class="block truncate text-xs text-slate-500 hover:text-brand-accent">{{ $c->email }}</a>
                                </td>
                                <td class="hidden px-4 py-3 text-slate-600 lg:table-cell">{{ $c->industry ?: '—' }}</td>
                                <td class="px-4 py-3 text-right">{{ $c->proposals_count }}</td>
                                <td class="px-4 py-3 text-right whitespace-nowrap">{{ $money($c->won_value) }}</td>
                                <td class="px-4 py-3">@include('livewire.admin.partials.client-actions', ['c' => $c])</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            {{-- Phone cards --}}
            <ul class="grid grid-cols-1 gap-3 md:hidden">
                @foreach ($clients as $c)
                    <li wire:key="cc-{{ $c->id }}" class="card min-w-0 p-4">
                        <div class="flex items-start justify-between gap-3">
                            <div class="min-w-0">
                                <div class="truncate font-semibold text-slate-900">{{ $c->company_name }}</div>
                                <div class="truncate text-xs text-slate-500">{{ collect([$c->brand_name, $c->industry])->filter()->join(' · ') ?: '—' }}</div>
                            </div>
                            @include('livewire.admin.partials.client-actions', ['c' => $c])
                        </div>
                        <div class="mt-3 text-sm">
                            <div class="truncate">{{ $c->contact_person }}</div>
                            <a href="mailto:{{ $c->email }}" class="block truncate text-xs text-slate-500">{{ $c->email }}</a>
                        </div>
                        <dl class="mt-3 grid grid-cols-2 gap-3 border-t border-slate-100 pt-3 text-xs">
                            <div><dt class="text-slate-400">Proposals</dt><dd class="font-semibold text-slate-900">{{ $c->proposals_count }}</dd></div>
                            <div><dt class="text-slate-400">Won</dt><dd class="font-semibold text-slate-900">{{ $money($c->won_value) }}</dd></div>
                        </dl>
                    </li>
                @endforeach
            </ul>
        @endif
    </div>
    <div class="mt-4">{{ $clients->links() }}</div>

    <x-modal show="editing" :title="$editId ? 'Edit client' : 'New client'">
        <form wire:submit="save" class="grid gap-4 sm:grid-cols-2">
            @foreach (['company_name' => 'Company name *', 'brand_name' => 'Brand name', 'contact_person' => 'Contact person *', 'email' => 'Email *', 'website' => 'Website', 'industry' => 'Industry'] as $field => $label)
                <div>
                    <label class="label" for="client-{{ $field }}">{{ $label }}</label>
                    <input id="client-{{ $field }}" wire:model="form.{{ $field }}" class="input" @if ($field === 'email') type="email" @elseif ($field === 'website') type="url" placeholder="https://" @endif>
                    @error("form.$field")<p class="error">{{ $message }}</p>@enderror
                </div>
            @endforeach
            <div class="flex flex-col-reverse gap-2 sm:col-span-2 sm:flex-row sm:justify-end">
                <button type="button" wire:click="$set('editing', false)" class="btn-ghost">Cancel</button>
                <button class="btn-primary" wire:loading.attr="disabled" wire:target="save">Save</button>
            </div>
        </form>
    </x-modal>
</div>
