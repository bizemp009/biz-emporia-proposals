{{-- Global styled confirm dialog driven by the Alpine `confirm` store / `$confirm()` magic. --}}
<div x-data x-show="$store.confirm.open" x-cloak
     @keydown.escape.window="$store.confirm.open && $store.confirm.answer(false)"
     class="fixed inset-0 z-[60] flex items-center justify-center bg-black/50 p-4"
     x-transition.opacity>
    <div x-show="$store.confirm.open" x-trap.noscroll="$store.confirm.open"
         @click.outside="$store.confirm.answer(false)"
         role="alertdialog" aria-modal="true" aria-labelledby="confirm-title" aria-describedby="confirm-message"
         class="card w-full max-w-sm p-6" x-transition>
        <div class="flex gap-4">
            <div class="flex size-10 shrink-0 items-center justify-center rounded-full"
                 :class="$store.confirm.danger ? 'bg-rose-50 text-rose-600' : 'bg-brand-accent/10 text-brand-accent'">
                <svg class="size-5" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" viewBox="0 0 24 24" aria-hidden="true"><path d="M12 9v4m0 4h.01M10.3 3.9 1.8 18a2 2 0 0 0 1.7 3h17a2 2 0 0 0 1.7-3L13.7 3.9a2 2 0 0 0-3.4 0Z"/></svg>
            </div>
            <div class="min-w-0">
                <h2 id="confirm-title" class="font-semibold text-slate-900" x-text="$store.confirm.title"></h2>
                <p id="confirm-message" class="mt-1 text-sm text-slate-600" x-text="$store.confirm.message"></p>
            </div>
        </div>
        <div class="mt-6 flex flex-col-reverse gap-2 sm:flex-row sm:justify-end">
            <button type="button" class="btn-ghost" @click="$store.confirm.answer(false)">Cancel</button>
            <button type="button" :class="$store.confirm.danger ? 'btn-danger' : 'btn-primary'"
                    @click="$store.confirm.answer(true)" x-text="$store.confirm.confirmText"></button>
        </div>
    </div>
</div>
