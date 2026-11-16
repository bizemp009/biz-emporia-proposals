<section id="section-{{ $n }}" class="card scroll-mt-32 p-4 sm:p-5">
    <div class="mb-4 flex flex-wrap items-center justify-between gap-3">
        <h2 class="flex items-center gap-2 font-semibold text-slate-900"><span class="flex size-6 items-center justify-center rounded-full bg-brand-accent/10 text-xs text-brand-accent">{{ $n }}</span> Execution roadmap</h2>
        <div class="flex items-center gap-2 text-sm">
            <label for="tenure" class="text-slate-600">Tenure</label>
            <input id="tenure" type="number" min="1" max="24" wire:model.live.debounce.400ms="tenureMonths" class="input w-20 py-1">
            <span class="text-slate-500">months</span>
        </div>
    </div>
    <div class="space-y-3">
        @foreach ($roadmap as $i => $m)
            <div wire:key="ms-{{ $i }}" class="grid gap-3 rounded-lg border border-slate-200 p-3 sm:grid-cols-[180px_minmax(0,1fr)_auto]">
                <div><input wire:model="roadmap.{{ $i }}.title" class="input font-semibold">@error("roadmap.$i.title")<p class="error">{{ $message }}</p>@enderror</div>
                <textarea wire:model="roadmap.{{ $i }}.items" rows="3" class="input text-sm" placeholder="One deliverable per line"></textarea>
                <button type="button" wire:click="removeMilestone({{ $i }})" class="self-start rounded px-2 py-1 text-rose-600 hover:bg-rose-50" title="Remove">✕</button>
            </div>
        @endforeach
        <button type="button" wire:click="addMilestone" class="btn-ghost py-1.5 text-xs">+ Add phase</button>
    </div>
</section>
