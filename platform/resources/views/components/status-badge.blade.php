@props(['status'])
@php
    $styles = [
        'draft' => 'bg-slate-100 text-slate-700 ring-slate-300',
        'sent' => 'bg-sky-50 text-sky-700 ring-sky-200',
        'viewed' => 'bg-amber-50 text-amber-700 ring-amber-200',
        'accepted' => 'bg-emerald-50 text-emerald-700 ring-emerald-200',
        'rejected' => 'bg-rose-50 text-rose-700 ring-rose-200',
        'expired' => 'bg-zinc-100 text-zinc-500 ring-zinc-300',
    ];
@endphp
<span {{ $attributes->class(['inline-flex items-center gap-1.5 rounded-full px-2.5 py-0.5 text-xs font-semibold ring-1 ring-inset', $styles[$status] ?? $styles['draft']]) }}>
    <span class="size-1.5 rounded-full bg-current"></span>{{ ucfirst($status) }}
</span>
