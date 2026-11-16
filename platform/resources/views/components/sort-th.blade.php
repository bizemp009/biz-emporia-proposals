@props(['column', 'sort', 'dir', 'align' => 'left'])
@php($active = $sort === $column)
<th scope="col" class="px-4 py-3 {{ $align === 'right' ? 'text-right' : '' }}"
    @if ($active) aria-sort="{{ $dir === 'asc' ? 'ascending' : 'descending' }}" @endif>
    <button type="button" wire:click="sortBy('{{ $column }}')"
            class="group inline-flex items-center gap-1 rounded tracking-wide uppercase hover:text-slate-800 focus:outline-none focus-visible:ring-2 focus-visible:ring-brand-accent/50 {{ $active ? 'text-slate-800' : '' }}">
        {{ $slot }}
        <svg class="size-3.5 {{ $active ? 'text-brand-accent' : 'text-slate-300 group-hover:text-slate-400' }}" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24" aria-hidden="true">
            @if ($active && $dir === 'asc')
                <path d="m6 15 6-6 6 6"/>
            @elseif ($active)
                <path d="m6 9 6 6 6-6"/>
            @else
                <path d="m8 9 4-4 4 4M8 15l4 4 4-4"/>
            @endif
        </svg>
    </button>
</th>
