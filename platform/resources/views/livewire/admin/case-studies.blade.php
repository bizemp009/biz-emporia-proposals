<div>
    <div class="mb-6 flex flex-wrap items-end justify-between gap-4">
        <div>
            <h1 class="text-2xl font-bold text-slate-900">Case studies</h1>
            <p class="text-sm text-slate-500">Proof points and testimonials you can attach to proposals.</p>
        </div>
        <button wire:click="create" class="btn-primary"><svg class="size-4" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24" aria-hidden="true"><path d="M12 5v14M5 12h14"/></svg>New case study</button>
    </div>

    <div class="grid gap-4 md:grid-cols-2 xl:grid-cols-3">
        @forelse ($caseStudies as $cs)
            <div wire:key="cs-{{ $cs->id }}" class="card flex flex-col overflow-hidden {{ $cs->is_published ? '' : 'opacity-60' }}">
                @if ($cs->image_url)
                    <img src="{{ $cs->image_url }}" alt="" class="aspect-[16/7] w-full object-cover">
                @else
                    <div class="aspect-[16/7] w-full bg-gradient-to-br from-brand to-brand-accent"></div>
                @endif
                <div class="flex flex-1 flex-col p-4">
                    <div class="text-xs font-semibold text-brand-accent">{{ $cs->industry }}</div>
                    <div class="font-semibold text-slate-900">{{ $cs->title }}</div>
                    <div class="mt-3 flex flex-wrap gap-3">
                        @foreach ($cs->metrics ?? [] as $m)
                            <div><div class="text-lg font-bold text-slate-900">{{ $m['value'] }}</div><div class="text-[11px] text-slate-500">{{ $m['label'] }}</div></div>
                        @endforeach
                    </div>
                    <div class="mt-auto flex items-center gap-1 pt-4">
                        <button wire:click="edit({{ $cs->id }})" class="btn-xs">Edit</button>
                        <span class="ml-auto text-xs text-slate-400">Used in {{ $cs->proposals_count }}</span>
                        <x-dropdown label="More actions for {{ $cs->title }}">
                            <button type="button" class="menu-item-danger" role="menuitem"
                                    @click="$confirm(@js(['title' => 'Delete case study?', 'message' => $cs->proposals_count ? 'It will also be removed from '.$cs->proposals_count.' '.str('proposal')->plural($cs->proposals_count).'.' : 'It is not used in any proposal.', 'confirmText' => 'Delete'])).then(ok => ok && $wire.delete({{ $cs->id }}))">Delete</button>
                        </x-dropdown>
                    </div>
                </div>
            </div>
        @empty
            <div class="card md:col-span-2 xl:col-span-3">
                <x-empty-state title="No case studies yet" icon="M3 17l6-6 4 4 8-8M14 7h7v7">
                    Proof points and testimonials make proposals far more convincing.
                    <x-slot:actions><button wire:click="create" class="btn-primary">New case study</button></x-slot:actions>
                </x-empty-state>
            </div>
        @endforelse
    </div>

    <x-modal show="editing" :title="$editId ? 'Edit case study' : 'New case study'" wide>
        <form wire:submit="save" class="grid gap-4 sm:grid-cols-2">
            <div class="sm:col-span-2"><label class="label">Title *</label><input wire:model="form.title" class="input" placeholder="3.2× ROAS for a D2C skincare brand">@error('form.title')<p class="error">{{ $message }}</p>@enderror</div>
            <div><label class="label">Client name</label><input wire:model="form.client_name" class="input"></div>
            <div><label class="label">Industry / niche</label><input wire:model="form.industry" class="input"></div>
            <div class="sm:col-span-2"><label class="label">Outcome summary</label><textarea wire:model="form.summary" rows="3" class="input"></textarea></div>

            <div class="sm:col-span-2">
                <label class="label">Headline metrics</label>
                <div class="space-y-2">
                    @foreach ($form['metrics'] ?? [] as $i => $m)
                        <div wire:key="m-{{ $i }}" class="flex gap-2">
                            <input wire:model="form.metrics.{{ $i }}.value" class="input w-32" placeholder="+186%">
                            <input wire:model="form.metrics.{{ $i }}.label" class="input" placeholder="Organic traffic">
                            <button type="button" wire:click="removeMetric({{ $i }})" class="rounded px-2 text-rose-600 hover:bg-rose-50">✕</button>
                        </div>
                    @endforeach
                    <button type="button" wire:click="addMetric" class="btn-ghost py-1 text-xs">+ Metric</button>
                </div>
            </div>

            <div class="sm:col-span-2"><label class="label">Testimonial quote</label><textarea wire:model="form.testimonial_quote" rows="2" class="input"></textarea></div>
            <div><label class="label">Testimonial author</label><input wire:model="form.testimonial_author" class="input" placeholder="Name, Title"></div>
            <div>
                <label class="label">Cover image</label>
                <input type="file" wire:model="image" accept="image/*" class="block w-full text-sm text-slate-600 file:mr-3 file:rounded-md file:border-0 file:bg-slate-100 file:px-3 file:py-1.5 file:text-sm file:font-semibold">
                @error('image')<p class="error">{{ $message }}</p>@enderror
            </div>
            <label class="flex items-center gap-2 text-sm sm:col-span-2"><input type="checkbox" wire:model="form.is_published" class="rounded border-slate-300"> Published (available to attach to proposals)</label>
            <div class="flex justify-end gap-2 sm:col-span-2">
                <button type="button" wire:click="$set('editing', false)" class="btn-ghost">Cancel</button>
                <button class="btn-primary" wire:loading.attr="disabled">Save</button>
            </div>
        </form>
    </x-modal>
</div>
