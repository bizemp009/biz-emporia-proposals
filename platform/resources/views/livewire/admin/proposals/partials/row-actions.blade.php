{{-- Edit + ⋯ menu for one proposal. Expects $p. --}}
<div class="flex items-center justify-end gap-1">
    <a href="{{ route('admin.proposals.edit', $p) }}" wire:navigate class="btn-xs">Edit</a>
    <x-dropdown label="More actions for {{ $p->client->company_name }}" width="w-56">
        <a href="{{ $p->public_url }}" target="_blank" rel="noopener" class="menu-item" role="menuitem">
            <svg class="size-4 text-slate-400" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" aria-hidden="true"><path d="M14 4h6v6m0-6L10 14M19 14v5a1 1 0 0 1-1 1H5a1 1 0 0 1-1-1V6a1 1 0 0 1 1-1h5"/></svg>
            Open client view
        </a>
        <button type="button" class="menu-item" role="menuitem" x-data="copyLink(@js($p->public_url))"
                @click="copy().then(() => $dispatch('notify', { message: 'Link copied to clipboard.' }))">
            <svg class="size-4 text-slate-400" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" aria-hidden="true"><path d="M10 14a5 5 0 0 0 7 0l3-3a5 5 0 0 0-7-7l-1 1m2 5a5 5 0 0 0-7 0l-3 3a5 5 0 0 0 7 7l1-1"/></svg>
            Copy link
        </button>
        <a href="{{ route('admin.proposals.pdf', $p) }}" target="_blank" class="menu-item" role="menuitem">
            <svg class="size-4 text-slate-400" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" aria-hidden="true"><path d="M12 4v12m0 0-4-4m4 4 4-4M5 20h14"/></svg>
            View PDF
        </a>
        <button type="button" wire:click="duplicate({{ $p->id }})" class="menu-item" role="menuitem">
            <svg class="size-4 text-slate-400" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" aria-hidden="true"><rect x="8" y="8" width="12" height="12" rx="2"/><path d="M16 8V5a1 1 0 0 0-1-1H5a1 1 0 0 0-1 1v10a1 1 0 0 0 1 1h3"/></svg>
            Duplicate
        </button>
        @if ($p->status === 'draft')
            <button type="button" wire:click="markSent({{ $p->id }})" class="menu-item" role="menuitem">
                <svg class="size-4 text-slate-400" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" aria-hidden="true"><path d="m22 2-7 20-4-9-9-4 20-7Z"/></svg>
                Mark as sent
            </button>
        @endif
        @if ($p->canExtend())
            <div class="my-1 border-t border-slate-100"></div>
            <div class="px-3 pt-1 pb-0.5 text-[11px] font-semibold tracking-wide text-slate-400 uppercase">Extend validity</div>
            <div class="flex gap-1 px-2 pb-1">
                <button type="button" wire:click="extend({{ $p->id }}, 7)" class="menu-item justify-center ring-1 ring-slate-200" role="menuitem">+7 days</button>
                <button type="button" wire:click="extend({{ $p->id }}, 30)" class="menu-item justify-center ring-1 ring-slate-200" role="menuitem">+30 days</button>
            </div>
        @endif
        <div class="my-1 border-t border-slate-100"></div>
        <button type="button" class="menu-item-danger" role="menuitem"
                @click="$confirm(@js(['title' => 'Delete proposal?', 'message' => 'The proposal for '.$p->client->company_name.' and its activity log will be permanently deleted.', 'confirmText' => 'Delete'])).then(ok => ok && $wire.delete({{ $p->id }}))">
            <svg class="size-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" aria-hidden="true"><path d="M4 7h16M10 11v6m4-6v6M6 7l1 13h10l1-13M9 7V4h6v3"/></svg>
            Delete
        </button>
    </x-dropdown>
</div>
