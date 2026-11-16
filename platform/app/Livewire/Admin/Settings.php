<?php

namespace App\Livewire\Admin;

use App\Models\Setting;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Layout('layouts.admin')]
#[Title('Branding')]
class Settings extends Component
{
    public array $form = [];

    public function mount(): void
    {
        $this->form = Setting::allValues();
    }

    public function save(): void
    {
        $hex = ['required', 'regex:/^#[0-9a-fA-F]{6}$/'];
        $data = $this->validate([
            'form.agency_name' => 'required|string|max:100',
            'form.agency_tagline' => 'nullable|string|max:150',
            'form.agency_email' => 'required|email|max:150',
            'form.agency_website' => 'nullable|url|max:255',
            'form.brand_primary' => $hex,
            'form.brand_accent' => $hex,
            'form.brand_highlight' => $hex,
            'form.default_currency' => 'required|string|max:8',
            'form.default_booking_url' => 'nullable|url|max:500',
            'form.slug_prefix' => 'nullable|alpha_dash|max:40',
            'form.default_validity_days' => 'required|integer|min:1|max:365',
            'form.terms' => 'nullable|string|max:10000',
        ])['form'];

        Setting::put(array_intersect_key($data, Setting::DEFAULTS));
        session()->flash('notify', 'Branding saved.');
        $this->redirectRoute('admin.settings'); // full reload so new brand colours apply
    }

    public function render()
    {
        return view('livewire.admin.settings');
    }
}
