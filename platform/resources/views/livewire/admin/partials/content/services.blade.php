<section id="section-{{ $n }}" class="card scroll-mt-32 p-4 sm:p-5">
    <div class="mb-4 flex flex-wrap items-center justify-between gap-2">
        <h2 class="flex items-center gap-2 font-semibold text-slate-900"><span class="flex size-6 items-center justify-center rounded-full bg-brand-accent/10 text-xs text-brand-accent">{{ $n }}</span> Services &amp; packages</h2>
        <div class="flex gap-2" x-data="{ open: false }">
            <div class="relative">
                <button type="button" @click="open = !open" class="btn-ghost py-1.5 text-xs">+ From catalog</button>
                <div x-show="open" x-cloak @click.outside="open = false" class="absolute right-0 z-20 mt-1 max-h-80 w-72 overflow-y-auto rounded-lg bg-white p-1 shadow-lg ring-1 ring-slate-200">
                    @forelse ($catalog as $category => $items)
                        <div class="px-2 pt-2 pb-1 text-[10px] font-semibold tracking-wide text-slate-400 uppercase">{{ $category }}</div>
                        @foreach ($items as $s)
                            <button type="button" wire:click="addCatalogService({{ $s->id }})" @click="open = false" class="flex w-full items-center justify-between rounded px-2 py-1.5 text-left text-sm hover:bg-slate-50">
                                <span class="truncate">{{ $s->name }}</span>
                                <span class="ml-2 shrink-0 text-xs text-slate-400">{{ $preview->money($s->default_price) }}</span>
                            </button>
                        @endforeach
                    @empty
                        <div class="p-3 text-sm text-slate-500">Catalog is empty. <a href="{{ route('admin.services') }}" class="text-brand-accent">Add services →</a></div>
                    @endforelse
                </div>
            </div>
            <button type="button" wire:click="addCustomLine" class="btn-ghost py-1.5 text-xs">+ Custom item</button>
        </div>
    </div>

    <div class="space-y-3">
        @forelse ($lines as $i => $line)
            <div wire:key="line-{{ $line['id'] ?? 'new' }}-{{ $i }}" class="rounded-lg border border-slate-200 p-4" x-data="{ more: false }">
                <div class="grid gap-3 sm:grid-cols-[minmax(0,1fr)_130px_130px]">
                    <div><label class="label">Service name</label><input wire:model="lines.{{ $i }}.name" class="input">@error("lines.$i.name")<p class="error">{{ $message }}</p>@enderror</div>
                    <div><label class="label">Price</label><input type="number" step="0.01" min="0" wire:model.live.debounce.400ms="lines.{{ $i }}.price" class="input">@error("lines.$i.price")<p class="error">{{ $message }}</p>@enderror</div>
                    <div>
                        <label class="label">Billing</label>
                        <select wire:model.live="lines.{{ $i }}.billing_type" class="input">
                            <option value="one_time">One-time</option>
                            <option value="monthly">Monthly</option>
                            <option value="quarterly">Quarterly</option>
                        </select>
                    </div>
                </div>
                <div class="mt-3 flex flex-wrap items-center gap-x-5 gap-y-2 text-sm">
                    <label class="flex items-center gap-2"><input type="checkbox" wire:model.live="lines.{{ $i }}.is_optional" class="rounded border-slate-300"> Optional add-on</label>
                    @if ($line['is_optional'])
                        <label class="flex items-center gap-2"><input type="checkbox" wire:model.live="lines.{{ $i }}.is_selected" class="rounded border-slate-300"> Pre-selected for client</label>
                    @else
                        <span class="text-xs text-slate-400">Mandatory — always included</span>
                    @endif
                    <button type="button" @click="more = !more" class="text-xs font-semibold text-brand-accent" x-text="more ? 'Hide details' : 'Description & deliverables'"></button>
                    <span class="ml-auto flex gap-1">
                        <button type="button" wire:click="moveLine({{ $i }}, -1)" class="rounded px-1.5 text-slate-500 hover:bg-slate-100" title="Move up" @disabled($i === 0)>↑</button>
                        <button type="button" wire:click="moveLine({{ $i }}, 1)" class="rounded px-1.5 text-slate-500 hover:bg-slate-100" title="Move down" @disabled($i === count($lines) - 1)>↓</button>
                        <button type="button" wire:click="removeLine({{ $i }})" class="rounded px-1.5 text-rose-600 hover:bg-rose-50" title="Remove">✕</button>
                    </span>
                </div>
                <div x-show="more" x-cloak class="mt-3 grid gap-3 sm:grid-cols-2">
                    <div><label class="label">Description</label><textarea wire:model="lines.{{ $i }}.description" rows="4" class="input"></textarea></div>
                    <div><label class="label">Deliverables (one per line)</label><textarea wire:model="lines.{{ $i }}.deliverables" rows="4" class="input font-mono text-xs"></textarea></div>
                </div>
            </div>
        @empty
            <div class="rounded-lg border-2 border-dashed border-slate-200 p-6 text-center text-sm text-slate-500">
                No services yet. Pull from the catalog or add a custom line item.
            </div>
        @endforelse
    </div>
</section>
