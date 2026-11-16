<div>
    <div class="mb-6 flex flex-wrap items-start justify-between gap-4">
        <div>
            <a href="{{ route('admin.templates') }}" wire:navigate class="text-sm text-slate-500 hover:text-slate-700">← All templates</a>
            <h1 class="mt-1 text-2xl font-bold text-slate-900">{{ $template ? 'Edit template' : 'New template' }}</h1>
            <p class="text-sm text-slate-500">Everything a proposal has except the client details. Proposals already made from this template are not changed.</p>
        </div>
        @if ($template)
            <a href="{{ route('admin.proposals.create', ['template' => $template->id]) }}" wire:navigate class="btn-ghost">Use template</a>
        @endif
    </div>

    <form wire:submit="save" class="grid gap-6 lg:grid-cols-[minmax(0,1fr)_320px]">
        <div class="min-w-0 space-y-6">
            <section class="card p-5">
                <h2 class="mb-4 flex items-center gap-2 font-semibold text-slate-900"><span class="flex size-6 items-center justify-center rounded-full bg-brand-accent/10 text-xs text-brand-accent">1</span> Template basics</h2>
                <div class="grid gap-4 sm:grid-cols-2">
                    <div><label class="label">Template name *</label><input wire:model="name" class="input" placeholder="e.g. E-commerce Ads (90 days)">@error('name')<p class="error">{{ $message }}</p>@enderror</div>
                    <div><label class="label">Description</label><input wire:model="description" class="input" placeholder="Shown on the Templates page">@error('description')<p class="error">{{ $message }}</p>@enderror</div>
                    <div class="sm:col-span-2">
                        <label class="label">Proposal title *</label>
                        <input wire:model="titlePattern" class="input">
                        <p class="mt-1 text-xs text-slate-400"><code>{client}</code> is replaced with the client's brand (or company) name.</p>
                        @error('titlePattern')<p class="error">{{ $message }}</p>@enderror
                    </div>
                    <div><label class="label">Assigned strategist</label><input wire:model="collaboratorName" class="input" placeholder="e.g. Srishti">@error('collaboratorName')<p class="error">{{ $message }}</p>@enderror</div>
                    <div>
                        <label class="label">Currency</label>
                        <select wire:model.live="currency" class="input">
                            @foreach (['INR', 'USD', 'EUR', 'GBP', 'AUD', 'CAD', 'AED'] as $cur)
                                <option>{{ $cur }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>
            </section>

            @include('livewire.admin.partials.content.summary', ['n' => 2])
            @include('livewire.admin.partials.content.services', ['n' => 3])
            @include('livewire.admin.partials.content.roi', ['n' => 4])
            @include('livewire.admin.partials.content.roadmap', ['n' => 5])
            @include('livewire.admin.partials.content.proof', ['n' => 6, 'validity' => 'days'])
        </div>

        <aside class="space-y-4 lg:sticky lg:top-20 lg:self-start">
            <div class="card p-5">
                <h3 class="mb-3 text-sm font-semibold text-slate-900">Contract value (default selection)</h3>
                <dl class="space-y-1.5 text-sm">
                    <div class="flex justify-between"><dt class="text-slate-500">One-time</dt><dd>{{ $preview->money($pricing['one_time']) }}</dd></div>
                    <div class="flex justify-between"><dt class="text-slate-500">Monthly</dt><dd>{{ $preview->money($pricing['monthly']) }} × {{ $pricing['tenure'] }}</dd></div>
                    @if ($pricing['quarterly'] > 0)
                        <div class="flex justify-between"><dt class="text-slate-500">Quarterly</dt><dd>{{ $preview->money($pricing['quarterly']) }} × {{ (int) ceil($pricing['tenure'] / 3) }}</dd></div>
                    @endif
                    <div class="flex justify-between border-t border-slate-100 pt-2 text-base font-bold text-slate-900"><dt>Total</dt><dd>{{ $preview->money($pricing['contract_value']) }}</dd></div>
                </dl>
                <button type="submit" wire:loading.attr="disabled" class="btn-primary mt-5 w-full">
                    <span wire:loading.remove wire:target="save">{{ $template ? 'Save template' : 'Create template' }}</span>
                    <span wire:loading wire:target="save">Saving…</span>
                </button>
                @if ($errors->any())
                    <p class="mt-3 rounded-lg bg-rose-50 p-2 text-xs text-rose-700">Please fix {{ $errors->count() }} {{ \Illuminate\Support\Str::plural('field', $errors->count()) }} highlighted in the form.</p>
                @endif
            </div>
        </aside>
    </form>
</div>
