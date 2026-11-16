<div>
    <div class="mb-6 flex flex-wrap items-start justify-between gap-4">
        <div>
            <h1 class="text-2xl font-bold text-slate-900">Templates</h1>
            <p class="text-sm text-slate-500">Reusable starting points. Create one here, or build a proposal you like and use <em>Save as reusable template</em> in the proposal editor sidebar.</p>
        </div>
        <div class="flex flex-wrap gap-2">
            @if ($templates->count() >= 2)
                <a href="{{ route('admin.templates.combine') }}" wire:navigate class="btn-ghost">Combine templates</a>
            @endif
            <a href="{{ route('admin.templates.create') }}" wire:navigate class="btn-primary">+ New template</a>
        </div>
    </div>

    <div class="grid gap-4 md:grid-cols-2 xl:grid-cols-3">
        @forelse ($templates as $t)
            @php($lines = $t->lines())
            <div wire:key="t-{{ $t->id }}" class="card flex flex-col p-5">
                <div class="font-semibold text-slate-900">{{ $t->name }}</div>
                @if ($t->description)<div class="text-sm text-slate-500">{{ $t->description }}</div>@endif
                <dl class="mt-3 space-y-1 text-xs text-slate-500">
                    <div><dt class="inline font-semibold text-slate-600">Tenure:</dt> {{ $t->tenure_months }} months · {{ count($t->roadmap_milestones ?? []) }} phases</div>
                    <div><dt class="inline font-semibold text-slate-600">Services:</dt> {{ collect($lines)->pluck('name')->join(', ') ?: '—' }}</div>
                    <div><dt class="inline font-semibold text-slate-600">Case studies:</dt> {{ count($t->case_study_ids ?? []) }} · <dt class="inline font-semibold text-slate-600">Screenshots:</dt> {{ count($t->audit_screenshots ?? []) }}</div>
                </dl>
                <div class="mt-auto flex items-center gap-1 pt-4">
                    <a href="{{ route('admin.proposals.create', ['template' => $t->id]) }}" wire:navigate class="rounded-md bg-brand-accent px-2.5 py-1 text-xs font-semibold text-white hover:brightness-110">Use template</a>
                    <a href="{{ route('admin.templates.edit', $t) }}" wire:navigate class="btn-xs">Edit</a>
                    <x-dropdown label="More actions for {{ $t->name }}" class="ml-auto">
                        <button type="button" wire:click="duplicate({{ $t->id }})" class="menu-item" role="menuitem">Duplicate</button>
                        <a href="{{ route('admin.templates.combine', ['a' => $t->id]) }}" wire:navigate class="menu-item" role="menuitem">Combine with another…</a>
                        <div class="my-1 border-t border-slate-100"></div>
                        <button type="button" class="menu-item-danger" role="menuitem"
                                @click="$confirm(@js(['title' => 'Delete template?', 'message' => '“'.$t->name.'” will be removed. Proposals already created from it are not affected.', 'confirmText' => 'Delete'])).then(ok => ok && $wire.delete({{ $t->id }}))">Delete</button>
                    </x-dropdown>
                </div>
            </div>
        @empty
            <div class="card md:col-span-2 xl:col-span-3">
                <x-empty-state title="No templates yet" icon="M4 5h16M4 12h16M4 19h10">
                    Save time on repeat proposals: create a template here, or use <em>Save as reusable template</em> while editing a proposal.
                    <x-slot:actions><a href="{{ route('admin.templates.create') }}" wire:navigate class="btn-primary">New template</a></x-slot:actions>
                </x-empty-state>
            </div>
        @endforelse
    </div>
</div>
