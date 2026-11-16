@props(['show', 'title', 'wide' => false])
{{-- Entangled modal: <x-modal show="editing" title="…">…</x-modal> where `editing` is a bool Livewire property --}}
<div x-data="{ open: $wire.entangle('{{ $show }}').live }" x-show="open" x-cloak
     @keydown.escape.window="open = false"
     class="fixed inset-0 z-50 flex items-end justify-center overflow-y-auto bg-black/50 p-0 sm:items-start sm:p-8"
     x-transition.opacity>
    <div @click.outside="open = false" x-show="open" x-trap.noscroll="open" x-transition
         role="dialog" aria-modal="true" aria-labelledby="modal-title-{{ $show }}"
         class="card max-h-[92vh] w-full overflow-y-auto rounded-b-none p-5 sm:max-h-none sm:rounded-b-xl sm:p-6 {{ $wide ? 'sm:max-w-3xl' : 'sm:max-w-lg' }}">
        <div class="mb-4 flex items-center justify-between">
            <h2 id="modal-title-{{ $show }}" class="text-lg font-semibold text-slate-900">{{ $title }}</h2>
            <button type="button" @click="open = false" class="rounded p-1 text-slate-400 hover:bg-slate-100 hover:text-slate-600 focus:outline-none focus-visible:ring-2 focus-visible:ring-brand-accent/50" aria-label="Close">
                <svg class="size-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" aria-hidden="true"><path d="M6 6l12 12M18 6 6 18"/></svg>
            </button>
        </div>
        {{ $slot }}
    </div>
</div>
