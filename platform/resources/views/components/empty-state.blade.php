@props(['title', 'icon' => 'M9 12h6m-6 4h6M7 4h7l5 5v11a1 1 0 0 1-1 1H7a1 1 0 0 1-1-1V5a1 1 0 0 1 1-1Z'])
<div {{ $attributes->class('flex flex-col items-center px-6 py-12 text-center') }}>
    <div class="flex size-12 items-center justify-center rounded-full bg-brand-accent/10 text-brand-accent">
        <svg class="size-6" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" viewBox="0 0 24 24" aria-hidden="true"><path d="{{ $icon }}"/></svg>
    </div>
    <h3 class="mt-4 font-semibold text-slate-900">{{ $title }}</h3>
    @if ($slot->isNotEmpty())
        <div class="mt-1 max-w-sm text-sm text-slate-500">{{ $slot }}</div>
    @endif
    @isset($actions)
        <div class="mt-5 flex flex-wrap justify-center gap-2">{{ $actions }}</div>
    @endisset
</div>
