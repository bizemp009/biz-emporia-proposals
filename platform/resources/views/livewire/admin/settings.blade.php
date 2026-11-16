<div>
    <div class="mb-6">
        <h1 class="text-2xl font-bold text-slate-900">Branding &amp; defaults</h1>
        <p class="text-sm text-slate-500">Applied to the admin panel, every client proposal page and PDF exports.</p>
    </div>

    <form wire:submit="save" class="grid gap-6 lg:grid-cols-2">
        <section class="card space-y-4 p-5">
            <h2 class="font-semibold text-slate-900">Agency</h2>
            <div><label class="label">Agency name *</label><input wire:model="form.agency_name" class="input">@error('form.agency_name')<p class="error">{{ $message }}</p>@enderror</div>
            <div><label class="label">Tagline</label><input wire:model="form.agency_tagline" class="input"></div>
            <div class="grid gap-4 sm:grid-cols-2">
                <div><label class="label">Contact email *</label><input type="email" wire:model="form.agency_email" class="input">@error('form.agency_email')<p class="error">{{ $message }}</p>@enderror</div>
                <div><label class="label">Website</label><input wire:model="form.agency_website" class="input">@error('form.agency_website')<p class="error">{{ $message }}</p>@enderror</div>
            </div>
        </section>

        <section class="card space-y-4 p-5">
            <h2 class="font-semibold text-slate-900">Brand colours</h2>
            @foreach (['brand_primary' => 'Primary (dark)', 'brand_accent' => 'Accent', 'brand_highlight' => 'Highlight'] as $key => $label)
                <div>
                    <label class="label">{{ $label }}</label>
                    <div class="flex items-center gap-2" x-data="{ v: $wire.entangle('form.{{ $key }}') }">
                        <input type="color" x-model="v" class="h-9 w-12 cursor-pointer rounded border border-slate-300 bg-white p-0.5">
                        <input x-model="v" class="input w-32 font-mono">
                        <span class="h-9 flex-1 rounded-lg" :style="`background:${v}`"></span>
                    </div>
                    @error("form.$key")<p class="error">Use a 6-digit hex colour like #7C5CFF.</p>@enderror
                </div>
            @endforeach
        </section>

        <section class="card space-y-4 p-5">
            <h2 class="font-semibold text-slate-900">Proposal defaults</h2>
            <div class="grid gap-4 sm:grid-cols-3">
                <div><label class="label">Currency</label>
                    <select wire:model="form.default_currency" class="input">@foreach (['INR', 'USD', 'EUR', 'GBP', 'AUD', 'CAD', 'AED'] as $c)<option>{{ $c }}</option>@endforeach</select></div>
                <div><label class="label">Slug prefix</label><input wire:model="form.slug_prefix" class="input font-mono">@error('form.slug_prefix')<p class="error">{{ $message }}</p>@enderror</div>
                <div><label class="label">Validity (days)</label><input type="number" wire:model="form.default_validity_days" class="input">@error('form.default_validity_days')<p class="error">{{ $message }}</p>@enderror</div>
            </div>
            <div><label class="label">Default booking link</label><input wire:model="form.default_booking_url" class="input" placeholder="https://calendly.com/your-team/strategy-call">@error('form.default_booking_url')<p class="error">{{ $message }}</p>@enderror</div>
        </section>

        <section class="card space-y-4 p-5">
            <h2 class="font-semibold text-slate-900">Terms shown above the signature</h2>
            <textarea wire:model="form.terms" rows="7" class="input text-sm"></textarea>
        </section>

        <div class="lg:col-span-2">
            <button class="btn-primary">Save branding</button>
        </div>
    </form>
</div>
