{{-- Edit + ⋯ menu for one client. Expects $c (with proposals_count). --}}
<div class="flex shrink-0 items-center justify-end gap-1">
    <button type="button" wire:click="edit({{ $c->id }})" class="btn-xs">Edit</button>
    <x-dropdown label="More actions for {{ $c->company_name }}">
        <a href="{{ route('admin.proposals.create', ['client' => $c->id]) }}" wire:navigate class="menu-item" role="menuitem">
            <svg class="size-4 text-slate-400" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" aria-hidden="true"><path d="M12 5v14M5 12h14"/></svg>
            New proposal
        </a>
        @if ($c->proposals_count)
            <a href="{{ route('admin.proposals.index', ['search' => $c->company_name]) }}" wire:navigate class="menu-item" role="menuitem">
                <svg class="size-4 text-slate-400" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" aria-hidden="true"><path d="M9 12h6m-6 4h6M7 4h7l5 5v11a1 1 0 0 1-1 1H7a1 1 0 0 1-1-1V5a1 1 0 0 1 1-1Z"/></svg>
                View proposals ({{ $c->proposals_count }})
            </a>
        @endif
        <div class="my-1 border-t border-slate-100"></div>
        <button type="button" class="menu-item-danger" role="menuitem"
                @click="$confirm(@js([
                    'title' => 'Delete '.$c->company_name.'?',
                    'message' => $c->proposals_count
                        ? 'This also permanently deletes their '.$c->proposals_count.' '.str('proposal')->plural($c->proposals_count).', including signed ones.'
                        : 'This client has no proposals.',
                    'confirmText' => 'Delete client',
                ])).then(ok => ok && $wire.delete({{ $c->id }}))">
            <svg class="size-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" aria-hidden="true"><path d="M4 7h16M10 11v6m4-6v6M6 7l1 13h10l1-13M9 7V4h6v3"/></svg>
            Delete
        </button>
    </x-dropdown>
</div>
