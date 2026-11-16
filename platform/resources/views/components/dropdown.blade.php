@props(['label' => 'More actions', 'align' => 'bottom-end', 'width' => 'w-52', 'triggerClass' => null])
{{--
    Accessible menu. The panel is teleported to <body> and anchored to the trigger so it is
    never clipped by scrolling tables. Livewire's wire:click still resolves the owning component.

    <x-dropdown>                       (default trigger is a "⋯" button)
        <button class="menu-item">…</button>
    </x-dropdown>
    <x-dropdown><x-slot:trigger>Custom</x-slot:trigger>…</x-dropdown>
--}}
<div x-data="{ open: false }" {{ $attributes->class('relative inline-flex') }}>
    <button type="button" x-ref="trigger" @click="open = !open" :aria-expanded="open.toString()" aria-haspopup="menu"
            @keydown.down.prevent="open = true; $nextTick(() => $focus.within($refs.panel).first())"
            aria-label="{{ $label }}" title="{{ $label }}"
            class="{{ $triggerClass ?? 'btn-xs px-2' }}">
        @isset($trigger)
            {{ $trigger }}
        @else
            <svg class="size-4" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true"><circle cx="5" cy="12" r="1.8"/><circle cx="12" cy="12" r="1.8"/><circle cx="19" cy="12" r="1.8"/></svg>
        @endisset
    </button>
    <template x-teleport="body">
        <div x-ref="panel" x-show="open" x-cloak role="menu"
             x-anchor.{{ $align }}.offset.6="$refs.trigger"
             x-transition:enter="transition ease-out duration-100" x-transition:enter-start="opacity-0 scale-95" x-transition:enter-end="opacity-100 scale-100"
             @click.outside="if (!$refs.trigger.contains($event.target)) open = false"
             @click="if ($event.target.closest('[role=menuitem], .menu-item, .menu-item-danger, a, button')) open = false"
             @keydown.escape.prevent="open = false; $refs.trigger.focus()"
             @keydown.down.prevent="$focus.within($refs.panel).wrap().next()"
             @keydown.up.prevent="$focus.within($refs.panel).wrap().previous()"
             @keydown.tab="open = false"
             class="z-50 {{ $width }} rounded-lg border border-slate-200 bg-white p-1 text-slate-700 shadow-lg shadow-slate-900/10">
            {{ $slot }}
        </div>
    </template>
</div>
