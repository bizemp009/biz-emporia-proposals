@props(['n', 'kicker', 'title', 'sub' => null])
<div class="mb-8 max-w-3xl">
    <div class="flex items-center gap-2 text-xs font-bold tracking-widest text-brand-accent uppercase">
        <span class="flex size-6 items-center justify-center rounded-full bg-brand-accent/10">{{ $n }}</span>
        {{ $kicker }}
    </div>
    <h2 class="mt-2 text-2xl font-bold text-slate-900 sm:text-3xl">{{ $title }}</h2>
    @if ($sub)<p class="mt-2 text-slate-600">{{ $sub }}</p>@endif
</div>
