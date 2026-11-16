<section id="section-{{ $n }}" class="card scroll-mt-32 p-4 sm:p-5">
    <h2 class="mb-4 flex items-center gap-2 font-semibold text-slate-900"><span class="flex size-6 items-center justify-center rounded-full bg-brand-accent/10 text-xs text-brand-accent">{{ $n }}</span> Proof, booking &amp; validity</h2>
    <div class="mb-5">
        <label class="label">Case studies to show</label>
        @if ($caseStudies->isEmpty())
            <p class="text-sm text-slate-500">No published case studies. <a href="{{ route('admin.case-studies') }}" class="text-brand-accent">Add some →</a></p>
        @else
            <div class="grid gap-2 sm:grid-cols-2">
                @foreach ($caseStudies as $cs)
                    <label wire:key="cs-{{ $cs->id }}" class="flex items-start gap-2 rounded-lg border border-slate-200 p-3 text-sm hover:bg-slate-50 has-[:checked]:border-brand-accent has-[:checked]:bg-brand-accent/5">
                        <input type="checkbox" value="{{ $cs->id }}" wire:model="caseStudyIds" class="mt-0.5 rounded border-slate-300">
                        <span><span class="font-medium">{{ $cs->title }}</span><span class="block text-xs text-slate-500">{{ $cs->industry }}</span></span>
                    </label>
                @endforeach
            </div>
        @endif
    </div>
    <div class="grid gap-4 sm:grid-cols-2">
        <div>
            <label class="label">Booking link (Calendly, Cal.com…)</label>
            <input wire:model="bookingUrl" class="input" placeholder="{{ \App\Models\Setting::get('default_booking_url') ?: 'https://calendly.com/…' }}">
            <p class="mt-1 text-xs text-slate-400">Leave blank to use the default from Branding.</p>
            @error('bookingUrl')<p class="error">{{ $message }}</p>@enderror
        </div>
        @if (($validity ?? 'date') === 'days')
            <div>
                <label class="label">Link valid for (days)</label>
                <input type="number" min="1" max="365" wire:model="validityDays" class="input" placeholder="{{ \App\Models\Setting::get('default_validity_days', 30) }}">
                <p class="mt-1 text-xs text-slate-400">Counted from the day a proposal is created. Blank = default from Branding.</p>
                @error('validityDays')<p class="error">{{ $message }}</p>@enderror
            </div>
        @else
            <div>
                <label class="label">Link expires on</label>
                <input type="date" wire:model="expiresAt" class="input">
                @error('expiresAt')<p class="error">{{ $message }}</p>@enderror
            </div>
        @endif
    </div>
</section>
