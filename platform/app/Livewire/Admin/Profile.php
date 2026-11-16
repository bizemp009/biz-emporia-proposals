<?php

namespace App\Livewire\Admin;

use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Layout('layouts.admin')]
#[Title('Profile')]
class Profile extends Component
{
    public string $name = '';
    public string $email = '';

    public string $currentPassword = '';
    public string $password = '';
    public string $passwordConfirmation = '';

    public function mount(): void
    {
        $this->name = Auth::user()->name;
        $this->email = Auth::user()->email;
    }

    public function saveProfile(): void
    {
        $user = Auth::user();
        $data = $this->validate([
            'name' => 'required|string|max:255',
            'email' => ['required', 'email', 'max:255', Rule::unique('users', 'email')->ignore($user->id)],
        ]);

        $user->update($data);
        $this->dispatch('notify', message: 'Profile updated.');
    }

    public function updatePassword(): void
    {
        $this->validate([
            'currentPassword' => 'required|string|current_password',
            'password' => ['required', 'string', Password::min(8), 'different:currentPassword', 'same:passwordConfirmation'],
            'passwordConfirmation' => 'required|string',
        ], [
            'currentPassword.current_password' => 'Your current password is incorrect.',
            'password.same' => 'The new passwords do not match.',
            'password.different' => 'Choose a password different from your current one.',
        ]);

        Auth::user()->update(['password' => $this->password]); // hashed by the model cast

        $this->reset('currentPassword', 'password', 'passwordConfirmation');
        $this->dispatch('notify', message: 'Password changed.');
    }

    public function render()
    {
        return view('livewire.admin.profile', ['lastUpdated' => Auth::user()->updated_at]);
    }
}
