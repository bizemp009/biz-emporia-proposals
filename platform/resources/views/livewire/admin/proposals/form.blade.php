<div>
    @php($locked = $proposal?->status === 'accepted')
    {{-- Header --}}
    <div class="mb-6 flex flex-wrap items-start justify-between gap-4">
        <div>
            <a href="{{ route('admin.proposals.index') }}" wire:navigate class="text-sm text-slate-500 hover:text-slate-700">← All proposals</a>
            <h1 class="mt-1 text-2xl font-bold text-slate-900">{{ $proposal ? 'Edit proposal' : 'Create proposal' }}</h1>
            @if ($proposal)
                <div class="mt-1 flex flex-wrap items-center gap-2 text-sm">
                    <x-status-badge :status="$proposal->status" />
                    <a href="{{ $proposal->public_url }}" target="_blank" class="font-mono text-xs text-slate-500 hover:text-brand-accent">{{ $proposal->public_url }}</a>
                </div>
            @endif
        </div>
        @if ($proposal)
            <div class="flex flex-wrap gap-2">
                <button x-data="copyLink(@js($proposal->public_url))" @click="copy()" class="btn-ghost"><span x-text="copied ? 'Copied!' : 'Copy link'">Copy link</span></button>
                <a href="{{ $proposal->public_url }}" target="_blank" class="btn-ghost">Preview</a>
                <a href="{{ route('admin.proposals.pdf', $proposal) }}" target="_blank" class="btn-ghost">View PDF</a>
            </div>
        @endif
    </div>

    @if ($locked)
        <div class="mb-6 rounded-xl border border-emerald-200 bg-emerald-50 p-4 text-sm text-emerald-800">
            <strong>Signed {{ $proposal->signed_at?->format('d M Y, H:i') }}</strong> by {{ $proposal->signer_name }} ({{ $proposal->signer_title }}).
            This proposal is locked to preserve the agreement. Duplicate it from the proposals list to make a revised version.
            @if ($proposal->client_signature)
                <img src="{{ $proposal->client_signature }}" alt="Client signature" class="mt-3 h-16 rounded bg-white p-1 ring-1 ring-emerald-200">
            @endif
        </div>
    @elseif ($proposal?->status === 'rejected')
        <div class="mb-6 rounded-xl border border-rose-200 bg-rose-50 p-4 text-sm text-rose-800">
            Client declined on {{ $proposal->rejected_at?->format('d M Y') }}{{ $proposal->rejection_reason ? ': “'.$proposal->rejection_reason.'”' : '.' }}
        </div>
    @endif

    {{-- Section jump bar (sticky under the top bar) --}}
    @php($sections = [1 => 'Client', 2 => 'Branding & link', 3 => 'Summary & audit', 4 => 'Services', 5 => 'ROI', 6 => 'Roadmap', 7 => 'Proof & validity'])
    <nav aria-label="Form sections" x-data="sectionNav(@js(array_map(fn ($n) => 'section-'.$n, array_keys($sections))))"
         class="no-scrollbar sticky top-14 z-10 -mx-4 mb-4 flex gap-1 overflow-x-auto border-b border-slate-200 bg-slate-50/95 px-4 py-2 backdrop-blur sm:-mx-6 sm:px-6">
        @foreach ($sections as $n => $label)
            <button type="button" data-target="section-{{ $n }}" @click="go('section-{{ $n }}')"
                    :class="active === 'section-{{ $n }}' ? 'chip-on' : 'chip-off'" class="chip">
                <span class="opacity-60">{{ $n }}</span> {{ $label }}
            </button>
        @endforeach
    </nav>

    <form wire:submit="save" class="grid gap-6 lg:grid-cols-[minmax(0,1fr)_320px] {{ $locked ? '' : 'pb-24 lg:pb-0' }}">
        <fieldset @disabled($locked) class="min-w-0 space-y-6">

            {{-- Template loader --}}
            @if (! $proposal && $templates->isNotEmpty())
                <div class="card flex flex-wrap items-end gap-3 border-dashed p-4">
                    <div class="min-w-48 flex-1">
                        <label class="label">Start from a template</label>
                        <select wire:model="templateId" class="input">
                            <option value="">— Blank proposal —</option>
                            @foreach ($templates as $t)
                                <option value="{{ $t->id }}">{{ $t->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <button type="button" wire:click="applyTemplate" class="btn-ghost">Apply template</button>
                </div>
            @endif

            {{-- 1. Client --}}
            <section id="section-1" class="card scroll-mt-32 p-4 sm:p-5">
                <h2 class="mb-4 flex items-center gap-2 font-semibold text-slate-900"><span class="flex size-6 items-center justify-center rounded-full bg-brand-accent/10 text-xs text-brand-accent">1</span> Client metadata</h2>
                <div class="mb-4">
                    <label class="label">Existing client</label>
                    <select wire:model.live="clientId" class="input">
                        <option value="">+ New client</option>
                        @foreach ($clients as $c)
                            <option value="{{ $c->id }}">{{ $c->company_name }}{{ $c->brand_name ? ' · '.$c->brand_name : '' }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="grid gap-4 sm:grid-cols-2">
                    <div><label class="label">Company name *</label><input wire:model.blur="companyName" class="input" placeholder="Acme Retail Pvt Ltd">@error('companyName')<p class="error">{{ $message }}</p>@enderror</div>
                    <div><label class="label">Brand name</label><input wire:model.blur="brandName" class="input" placeholder="Acme Living">@error('brandName')<p class="error">{{ $message }}</p>@enderror</div>
                    <div><label class="label">Contact person *</label><input wire:model="contactPerson" class="input">@error('contactPerson')<p class="error">{{ $message }}</p>@enderror</div>
                    <div><label class="label">Email *</label><input type="email" wire:model="email" class="input">@error('email')<p class="error">{{ $message }}</p>@enderror</div>
                    <div><label class="label">Website</label><input wire:model="website" class="input" placeholder="https://">@error('website')<p class="error">{{ $message }}</p>@enderror</div>
                    <div><label class="label">Industry</label><input wire:model="industry" class="input" placeholder="E-commerce, SaaS, Real estate…">@error('industry')<p class="error">{{ $message }}</p>@enderror</div>
                </div>
            </section>

            {{-- 2 & 3. URL + collaboration --}}
            <section id="section-2" class="card scroll-mt-32 p-4 sm:p-5">
                <h2 class="mb-4 flex items-center gap-2 font-semibold text-slate-900"><span class="flex size-6 items-center justify-center rounded-full bg-brand-accent/10 text-xs text-brand-accent">2</span> Branding, URL &amp; collaboration</h2>
                <div class="grid gap-4 sm:grid-cols-2">
                    <div class="sm:col-span-2"><label class="label">Proposal title *</label><input wire:model="title" class="input">@error('title')<p class="error">{{ $message }}</p>@enderror</div>
                    <div class="sm:col-span-2">
                        <label class="label">URL slug *</label>
                        <div class="flex rounded-lg shadow-sm">
                            <span class="inline-flex items-center rounded-l-lg border border-r-0 border-slate-300 bg-slate-50 px-3 text-xs text-slate-500">{{ preg_replace('#^https?://#', '', url('/proposal')) }}/</span>
                            <input wire:model.blur="slug" class="input rounded-none font-mono">
                            <button type="button" wire:click="regenerateSlug" class="rounded-r-lg border border-l-0 border-slate-300 bg-white px-3 text-xs font-semibold text-slate-600 hover:bg-slate-50">Regenerate</button>
                        </div>
                        @error('slug')<p class="error">{{ $message }}</p>@enderror
                    </div>
                    <div><label class="label">Assigned strategist</label><input wire:model="collaboratorName" class="input" placeholder="e.g. Srishti">@error('collaboratorName')<p class="error">{{ $message }}</p>@enderror</div>
                    <div>
                        <label class="label">Currency</label>
                        <select wire:model.live="currency" class="input">
                            @foreach (['INR', 'USD', 'EUR', 'GBP', 'AUD', 'CAD', 'AED'] as $cur)
                                <option>{{ $cur }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>
            </section>

            @include('livewire.admin.partials.content.summary', ['n' => 3])
            @include('livewire.admin.partials.content.services', ['n' => 4])
            @include('livewire.admin.partials.content.roi', ['n' => 5])
            @include('livewire.admin.partials.content.roadmap', ['n' => 6])
            @include('livewire.admin.partials.content.proof', ['n' => 7, 'validity' => 'date'])
        </fieldset>

        {{-- Sticky sidebar --}}
        <aside class="space-y-4 lg:sticky lg:top-20 lg:self-start">
            <div class="card p-5">
                <h3 class="mb-3 text-sm font-semibold text-slate-900">Contract value (default selection)</h3>
                <dl class="space-y-1.5 text-sm">
                    <div class="flex justify-between"><dt class="text-slate-500">One-time</dt><dd>{{ $preview->money($pricing['one_time']) }}</dd></div>
                    <div class="flex justify-between"><dt class="text-slate-500">Monthly</dt><dd>{{ $preview->money($pricing['monthly']) }} × {{ $pricing['tenure'] }}</dd></div>
                    @if ($pricing['quarterly'] > 0)
                        <div class="flex justify-between"><dt class="text-slate-500">Quarterly</dt><dd>{{ $preview->money($pricing['quarterly']) }} × {{ (int) ceil($pricing['tenure'] / 3) }}</dd></div>
                    @endif
                    <div class="flex justify-between border-t border-slate-100 pt-2 text-base font-bold text-slate-900"><dt>Total</dt><dd>{{ $preview->money($pricing['contract_value']) }}</dd></div>
                </dl>

                @unless ($locked)
                    <div class="mt-5 hidden space-y-2 lg:block">
                        <button type="button" wire:click="save(true)" wire:loading.attr="disabled" class="btn-primary w-full">
                            <span wire:loading.remove wire:target="save">{{ $proposal && $proposal->status !== 'draft' ? 'Save & keep live' : 'Generate Proposal' }}</span>
                            <span wire:loading wire:target="save">Saving…</span>
                        </button>
                        @if (! $proposal || $proposal->status === 'draft')
                            <button type="submit" wire:loading.attr="disabled" class="btn-ghost w-full">Save as draft</button>
                        @endif
                    </div>
                    @if ($errors->any())
                        <p class="mt-3 rounded-lg bg-rose-50 p-2 text-xs text-rose-700">Please fix {{ $errors->count() }} {{ \Illuminate\Support\Str::plural('field', $errors->count()) }} highlighted in the form.</p>
                    @endif
                @endunless
            </div>

            @unless ($locked)
                <div class="card p-5" x-data="{ open: false }" @template-saved.window="open = false">
                    <button type="button" @click="open = !open" class="flex w-full items-center justify-between text-sm font-semibold text-slate-900">
                        Save as reusable template <span x-text="open ? '−' : '+'"></span>
                    </button>
                    <div x-show="open" x-cloak class="mt-3 space-y-2">
                        <input wire:model="templateName" class="input" placeholder="e.g. E-commerce SEO + CRO">
                        @error('templateName')<p class="error">{{ $message }}</p>@enderror
                        <button type="button" wire:click="saveAsTemplate" class="btn-ghost w-full text-xs">Save template</button>
                        <p class="text-xs text-slate-400">Stores everything on this form — title, strategist, currency, summary, audit &amp; screenshots, services with your prices and copy, ROI, roadmap, case studies, booking link and validity — except the client details.</p>
                    </div>
                </div>
            @endunless

            @if ($proposal)
                <div class="card p-5">
                    <h3 class="mb-3 text-sm font-semibold text-slate-900">Activity</h3>
                    <ol class="space-y-3 text-xs">
                        @forelse ($activities as $a)
                            <li class="flex gap-2">
                                <span class="mt-1 size-1.5 shrink-0 rounded-full bg-brand-accent"></span>
                                <span>
                                    <span class="text-slate-700">{{ $a->description }}</span>
                                    <span class="block text-slate-400">{{ $a->created_at->format('d M, H:i') }}{{ $a->ip_address ? ' · '.$a->ip_address : '' }}</span>
                                </span>
                            </li>
                        @empty
                            <li class="text-slate-400">No activity yet.</li>
                        @endforelse
                    </ol>
                </div>
            @endif
        </aside>
        {{-- Mobile action bar --}}
        @unless ($locked)
            <div class="fixed inset-x-0 bottom-0 z-30 border-t border-slate-200 bg-white/95 px-4 py-3 shadow-[0_-4px_16px_rgba(0,0,0,0.06)] backdrop-blur lg:hidden">
                <div class="mx-auto flex max-w-3xl items-center gap-2">
                    <div class="min-w-0 flex-1">
                        <div class="text-[11px] text-slate-500">Contract value</div>
                        <div class="truncate font-bold text-slate-900">{{ $preview->money($pricing['contract_value']) }}</div>
                        @if ($errors->any())<div class="text-[11px] font-semibold text-rose-600">{{ $errors->count() }} {{ \Illuminate\Support\Str::plural('field', $errors->count()) }} to fix</div>@endif
                    </div>
                    @if (! $proposal || $proposal->status === 'draft')
                        <button type="submit" wire:loading.attr="disabled" class="btn-ghost px-3">Save draft</button>
                    @endif
                    <button type="button" wire:click="save(true)" wire:loading.attr="disabled" class="btn-primary px-3">
                        <span wire:loading.remove wire:target="save">{{ $proposal && $proposal->status !== 'draft' ? 'Save' : 'Generate' }}</span>
                        <span wire:loading wire:target="save">Saving…</span>
                    </button>
                </div>
            </div>
        @endunless
    </form>
</div>
