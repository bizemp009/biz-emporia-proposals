{{-- Listens for Livewire `$this->dispatch('notify', message: '...', type: 'success|error')` --}}
<div x-data="{ toasts: [] }"
     x-init="@if (session('notify')) $nextTick(() => $dispatch('notify', { message: @js(session('notify')) })) @endif"
     @notify.window="const t = { id: Date.now() + Math.random(), message: $event.detail.message, type: $event.detail.type ?? 'success' }; toasts.push(t); setTimeout(() => toasts = toasts.filter(x => x.id !== t.id), 3500)"
     class="pointer-events-none fixed right-4 bottom-4 z-50 flex w-80 max-w-[calc(100vw-2rem)] flex-col gap-2">
    <template x-for="t in toasts" :key="t.id">
        <div x-transition.opacity
             class="pointer-events-auto rounded-lg px-4 py-3 text-sm font-medium text-white shadow-lg"
             role="status"
             :class="t.type === 'error' ? 'bg-rose-600' : 'bg-gray-900 ring-1 ring-white/10'"
             x-text="t.message"></div>
    </template>
</div>
