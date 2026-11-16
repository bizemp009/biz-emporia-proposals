<?php

namespace App\Livewire\Admin;

use App\Models\Service;
use App\Models\Setting;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Layout('layouts.admin')]
#[Title('Services')]
class Services extends Component
{
    public bool $editing = false;
    public ?int $editId = null;
    public array $form = [];

    /** "+ New" in the top bar links here with ?new=1 to open the form straight away. */
    public function mount(): void
    {
        if (request()->boolean('new')) {
            $this->create();
        }
    }

    public function create(): void
    {
        $this->resetErrorBag();
        $this->editId = null;
        $this->form = [
            'name' => '', 'category' => '', 'description' => '', 'deliverables' => '',
            'default_price' => 0, 'billing_type' => 'monthly', 'is_optional' => false, 'is_active' => true,
        ];
        $this->editing = true;
    }

    public function edit(int $id): void
    {
        $this->resetErrorBag();
        $s = Service::findOrFail($id);
        $this->editId = $id;
        $this->form = [
            'name' => $s->name,
            'category' => (string) $s->category,
            'description' => (string) $s->description,
            'deliverables' => implode("\n", $s->deliverables ?? []),
            'default_price' => (float) $s->default_price,
            'billing_type' => $s->billing_type,
            'is_optional' => $s->is_optional,
            'is_active' => $s->is_active,
        ];
        $this->editing = true;
    }

    public function save(): void
    {
        $data = $this->validate([
            'form.name' => 'required|string|max:255',
            'form.category' => 'nullable|string|max:100',
            'form.description' => 'nullable|string|max:5000',
            'form.deliverables' => 'nullable|string|max:5000',
            'form.default_price' => 'required|numeric|min:0|max:99999999',
            'form.billing_type' => 'required|in:one_time,monthly,quarterly',
            'form.is_optional' => 'boolean',
            'form.is_active' => 'boolean',
        ])['form'];

        $data['deliverables'] = collect(preg_split('/\r\n|\r|\n/', (string) $data['deliverables']))
            ->map(fn ($l) => trim(ltrim(trim($l), '-•*')))->filter()->values()->all();
        $data['category'] = $data['category'] ?: null;

        $this->editId ? Service::findOrFail($this->editId)->update($data) : Service::create($data);
        $this->editing = false;
        $this->dispatch('notify', message: 'Service saved.');
    }

    public function toggleActive(int $id): void
    {
        $s = Service::findOrFail($id);
        $s->update(['is_active' => ! $s->is_active]);
    }

    public function delete(int $id): void
    {
        Service::findOrFail($id)->delete(); // proposal lines keep their own copy
        $this->dispatch('notify', message: 'Service removed from catalog.');
    }

    public function render()
    {
        return view('livewire.admin.services', [
            'groups' => Service::orderBy('category')->orderBy('name')->get()->groupBy(fn ($s) => $s->category ?: 'Other'),
            'currency' => Setting::get('default_currency'),
        ]);
    }
}
