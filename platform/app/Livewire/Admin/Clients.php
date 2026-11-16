<?php

namespace App\Livewire\Admin;

use App\Models\Client;
use App\Models\Setting;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

#[Layout('layouts.admin')]
#[Title('Clients')]
class Clients extends Component
{
    use WithPagination;

    #[Url(except: '')]
    public string $search = '';
    public bool $editing = false;
    public ?int $editId = null;
    public array $form = [];

    public function updatedSearch(): void
    {
        $this->resetPage();
    }

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
        $this->form = ['company_name' => '', 'brand_name' => '', 'contact_person' => '', 'email' => '', 'website' => '', 'industry' => ''];
        $this->editing = true;
    }

    public function edit(int $id): void
    {
        $this->resetErrorBag();
        $client = Client::findOrFail($id);
        $this->editId = $id;
        $this->form = collect($client->only($client->getFillable()))->map(fn ($v) => (string) $v)->all();
        $this->editing = true;
    }

    public function save(): void
    {
        $data = $this->validate([
            'form.company_name' => 'required|string|max:255',
            'form.brand_name' => 'nullable|string|max:255',
            'form.contact_person' => 'required|string|max:255',
            'form.email' => 'required|email|max:255',
            'form.website' => 'nullable|url|max:255',
            'form.industry' => 'nullable|string|max:255',
        ])['form'];
        $data = array_map(fn ($v) => $v === '' ? null : $v, $data);

        $this->editId ? Client::findOrFail($this->editId)->update($data) : Client::create($data);
        $this->editing = false;
        $this->dispatch('notify', message: 'Client saved.');
    }

    public function delete(int $id): void
    {
        $client = Client::withCount('proposals')->findOrFail($id);
        $client->delete(); // cascades to proposals
        $this->dispatch('notify', message: "Deleted {$client->company_name} and {$client->proposals_count} proposal(s).");
    }

    public function render()
    {
        return view('livewire.admin.clients', [
            'exportUrl' => route('admin.clients.export', array_filter(['search' => $this->search])),
            'currency' => Setting::get('default_currency', 'INR'),
            'clients' => Client::withCount('proposals')
                ->withSum(['proposals as won_value' => fn ($q) => $q->where('status', 'accepted')], 'total_amount')
                ->when($this->search, fn ($q) => $q->where(fn ($q) => $q
                    ->where('company_name', 'like', "%{$this->search}%")
                    ->orWhere('brand_name', 'like', "%{$this->search}%")
                    ->orWhere('contact_person', 'like', "%{$this->search}%")
                    ->orWhere('email', 'like', "%{$this->search}%")))
                ->orderBy('company_name')
                ->paginate(20),
        ]);
    }
}
