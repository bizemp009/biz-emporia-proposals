<section id="section-{{ $n }}" class="card scroll-mt-32 p-4 sm:p-5">
    <h2 class="mb-4 flex items-center gap-2 font-semibold text-slate-900"><span class="flex size-6 items-center justify-center rounded-full bg-brand-accent/10 text-xs text-brand-accent">{{ $n }}</span> Executive summary &amp; audit</h2>
    <div class="space-y-5">
        <div>
            <label class="label">Executive summary</label>
            <div wire:ignore x-data="richEditor('executiveSummary')">
                <input id="exec-summary" type="hidden" value="{{ $executiveSummary }}">
                <trix-editor x-ref="trix" input="exec-summary" class="prose-content"></trix-editor>
            </div>
            @error('executiveSummary')<p class="error">{{ $message }}</p>@enderror
        </div>
        <div>
            <label class="label">Mini-report / audit findings</label>
            <div wire:ignore x-data="richEditor('auditReport')">
                <input id="audit-report" type="hidden" value="{{ $auditReport }}">
                <trix-editor x-ref="trix" input="audit-report" class="prose-content"></trix-editor>
            </div>
            @error('auditReport')<p class="error">{{ $message }}</p>@enderror
        </div>

        <div>
            <label class="label">Audit screenshots / mockups</label>
            <div class="grid grid-cols-2 gap-3 sm:grid-cols-3">
                @foreach ($screenshots as $i => $shot)
                    <div wire:key="shot-{{ $shot['path'] }}" class="overflow-hidden rounded-lg ring-1 ring-slate-200">
                        <img src="{{ \Illuminate\Support\Facades\Storage::disk('public')->url($shot['path']) }}" class="aspect-video w-full bg-slate-100 object-cover" alt="">
                        <div class="flex gap-1 p-2">
                            <input wire:model="screenshots.{{ $i }}.caption" placeholder="Caption" class="input py-1 text-xs">
                            <button type="button" wire:click="removeScreenshot({{ $i }})" class="rounded px-2 text-rose-600 hover:bg-rose-50" title="Remove">✕</button>
                        </div>
                    </div>
                @endforeach
                @foreach ($newScreenshots as $i => $upload)
                    <div wire:key="upload-{{ $i }}" class="relative overflow-hidden rounded-lg ring-2 ring-brand-accent/40">
                        @if (method_exists($upload, 'isPreviewable') && $upload->isPreviewable())
                            <img src="{{ $upload->temporaryUrl() }}" class="aspect-video w-full object-cover" alt="">
                        @else
                            <div class="flex aspect-video items-center justify-center bg-slate-100 text-xs text-slate-500">Upload ready</div>
                        @endif
                        <span class="absolute top-1 left-1 rounded bg-brand-accent px-1.5 text-[10px] font-semibold text-white">New</span>
                        <button type="button" wire:click="removeUpload({{ $i }})" class="absolute top-1 right-1 rounded bg-white/90 px-1.5 text-xs text-rose-600">✕</button>
                    </div>
                @endforeach
                <label class="flex aspect-video cursor-pointer flex-col items-center justify-center rounded-lg border-2 border-dashed border-slate-300 text-center text-xs text-slate-500 hover:border-brand-accent hover:text-brand-accent">
                    <svg class="mb-1 size-6" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24"><path d="M12 16V4m0 0-4 4m4-4 4 4M4 16v3a1 1 0 0 0 1 1h14a1 1 0 0 0 1-1v-3"/></svg>
                    <span wire:loading.remove wire:target="newScreenshots">Add images (max 5 MB)</span>
                    <span wire:loading wire:target="newScreenshots">Uploading…</span>
                    <input type="file" wire:model="newScreenshots" multiple accept="image/*" class="hidden">
                </label>
            </div>
            @error('newScreenshots.*')<p class="error">{{ $message }}</p>@enderror
        </div>
    </div>
</section>
