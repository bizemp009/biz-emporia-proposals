<div>
    <div class="mb-6 flex flex-wrap items-end justify-between gap-4">
        <div>
            <h1 class="text-2xl font-bold text-slate-900">Service catalog</h1>
            <p class="text-sm text-slate-500">Pre-defined services that can be pulled into any proposal. Prices are defaults and can be overridden per proposal.</p>
        </div>
        <button wire:click="create" class="btn-primary"><svg class="size-4" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24" aria-hidden="true"><path d="M12 5v14M5 12h14"/></svg>New service</button>
    </div>

    @forelse ($groups as $category => $services)
        <h2 class="mt-6 mb-2 text-xs font-semibold tracking-wide text-slate-500 uppercase">{{ $category }}</h2>
        <div class="grid gap-3 md:grid-cols-2 xl:grid-cols-3">
            @foreach ($services as $s)
                <div wire:key="s-{{ $s->id }}" class="card flex flex-col p-4 {{ $s->is_active ? '' : 'opacity-60' }}">
                    <div class="flex items-start justify-between gap-3">
                        <div>
                            <div class="font-semibold text-slate-900">{{ $s->name }}</div>
                            <div class="text-sm font-medium text-brand-accent">
                                {{ \App\Models\Proposal::formatMoney((float) $s->default_price, $currency) }}
                                <span class="text-xs text-slate-400">{{ \App\Models\ProposalService::BILLING_LABELS[$s->billing_type] }}</span>
                            </div>
                        </div>
                        <div class="flex shrink-0 gap-1">
                            @if ($s->is_optional)<span class="rounded bg-amber-50 px-1.5 py-0.5 text-[10px] font-semibold text-amber-700">ADD-ON</span>@endif
                            @unless ($s->is_active)<span class="rounded bg-slate-100 px-1.5 py-0.5 text-[10px] font-semibold text-slate-500">HIDDEN</span>@endunless
                        </div>
                    </div>
                    @if ($s->description)<p class="mt-2 line-clamp-2 text-sm text-slate-600">{{ $s->description }}</p>@endif
                    @if ($s->deliverables)<p class="mt-2 text-xs text-slate-400">{{ count($s->deliverables) }} deliverables</p>@endif
                    <div class="mt-auto flex items-center gap-1 pt-3">
                        <button wire:click="edit({{ $s->id }})" class="btn-xs">Edit</button>
                        <button wire:click="toggleActive({{ $s->id }})" class="btn-xs" title="{{ $s->is_active ? 'Hide from the proposal builder' : 'Show in the proposal builder' }}">{{ $s->is_active ? 'Hide' : 'Show' }}</button>
                        <x-dropdown label="More actions for {{ $s->name }}" class="ml-auto">
                            <button type="button" class="menu-item-danger" role="menuitem"
                                    @click="$confirm(@js(['title' => 'Delete '.$s->name.'?', 'message' => 'It is removed from the catalog. Existing proposals keep their own copy.', 'confirmText' => 'Delete'])).then(ok => ok && $wire.delete({{ $s->id }}))">Delete</button>
                        </x-dropdown>
                    </div>
                </div>
            @endforeach
        </div>
    @empty
        <div class="card">
            <x-empty-state title="No services yet" icon="M20 7 12 3 4 7m16 0-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4">
                Add the services you sell so they can be pulled into any proposal with one click.
                <x-slot:actions><button wire:click="create" class="btn-primary">New service</button></x-slot:actions>
            </x-empty-state>
        </div>
    @endforelse

    <x-modal show="editing" :title="$editId ? 'Edit service' : 'New service'" wide>
        <form wire:submit="save" class="grid gap-4 sm:grid-cols-2">
            <div><label class="label">Name *</label><input wire:model="form.name" class="input">@error('form.name')<p class="error">{{ $message }}</p>@enderror</div>
            <div><label class="label">Category</label><input wire:model="form.category" class="input" placeholder="SEO, Paid Media, CRO…" list="categories">
                <datalist id="categories">@foreach ($groups->keys() as $cat)<option value="{{ $cat }}">@endforeach</datalist></div>
            <div><label class="label">Default price *</label><input type="number" step="0.01" min="0" wire:model="form.default_price" class="input">@error('form.default_price')<p class="error">{{ $message }}</p>@enderror</div>
            <div><label class="label">Billing</label>
                <select wire:model="form.billing_type" class="input"><option value="one_time">One-time</option><option value="monthly">Monthly</option><option value="quarterly">Quarterly</option></select></div>
            <div class="sm:col-span-2"><label class="label">Description</label><textarea wire:model="form.description" rows="3" class="input"></textarea></div>
            <div class="sm:col-span-2"><label class="label">Deliverables (one per line)</label><textarea wire:model="form.deliverables" rows="5" class="input font-mono text-xs"></textarea></div>
            <label class="flex items-center gap-2 text-sm"><input type="checkbox" wire:model="form.is_optional" class="rounded border-slate-300"> Optional add-on by default</label>
            <label class="flex items-center gap-2 text-sm"><input type="checkbox" wire:model="form.is_active" class="rounded border-slate-300"> Available in proposal builder</label>
            <div class="flex justify-end gap-2 sm:col-span-2">
                <button type="button" wire:click="$set('editing', false)" class="btn-ghost">Cancel</button>
                <button class="btn-primary">Save</button>
            </div>
        </form>
    </x-modal>
</div>
