<div class="mx-auto max-w-3xl">
    <div class="mb-6">
        <h1 class="text-2xl font-bold text-slate-900">Profile</h1>
        <p class="text-sm text-slate-500">Your sign-in details for the admin panel.</p>
    </div>

    <div class="space-y-6">
        <form wire:submit="saveProfile" class="card p-5 sm:p-6">
            <h2 class="font-semibold text-slate-900">Account details</h2>
            <p class="mb-4 text-sm text-slate-500">Used to sign in and shown in the account menu.</p>
            <div class="grid gap-4 sm:grid-cols-2">
                <div>
                    <label class="label" for="p-name">Name</label>
                    <input id="p-name" wire:model="name" class="input" autocomplete="name">
                    @error('name')<p class="error">{{ $message }}</p>@enderror
                </div>
                <div>
                    <label class="label" for="p-email">Email</label>
                    <input id="p-email" type="email" wire:model="email" class="input" autocomplete="email">
                    @error('email')<p class="error">{{ $message }}</p>@enderror
                </div>
            </div>
            <div class="mt-5 flex justify-end">
                <button class="btn-primary" wire:loading.attr="disabled" wire:target="saveProfile">Save details</button>
            </div>
        </form>

        <form wire:submit="updatePassword" class="card p-5 sm:p-6">
            <h2 class="font-semibold text-slate-900">Change password</h2>
            <p class="mb-4 text-sm text-slate-500">At least 8 characters.</p>
            <div class="grid gap-4 sm:grid-cols-2">
                <div class="sm:col-span-2">
                    <label class="label" for="p-current">Current password</label>
                    <input id="p-current" type="password" wire:model="currentPassword" class="input sm:max-w-sm" autocomplete="current-password">
                    @error('currentPassword')<p class="error">{{ $message }}</p>@enderror
                </div>
                <div>
                    <label class="label" for="p-new">New password</label>
                    <input id="p-new" type="password" wire:model="password" class="input" autocomplete="new-password">
                    @error('password')<p class="error">{{ $message }}</p>@enderror
                </div>
                <div>
                    <label class="label" for="p-confirm">Confirm new password</label>
                    <input id="p-confirm" type="password" wire:model="passwordConfirmation" class="input" autocomplete="new-password">
                    @error('passwordConfirmation')<p class="error">{{ $message }}</p>@enderror
                </div>
            </div>
            <div class="mt-5 flex justify-end">
                <button class="btn-primary" wire:loading.attr="disabled" wire:target="updatePassword">Update password</button>
            </div>
        </form>
    </div>
</div>
