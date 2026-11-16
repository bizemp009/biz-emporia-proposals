<?php

namespace App\Livewire\Admin;

use App\Models\CaseStudy;
use Illuminate\Support\Facades\Storage;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;
use Livewire\WithFileUploads;

#[Layout('layouts.admin')]
#[Title('Case Studies')]
class CaseStudies extends Component
{
    use WithFileUploads;

    public bool $editing = false;
    public ?int $editId = null;
    public array $form = [];
    public $image = null;

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
        $this->image = null;
        $this->form = [
            'title' => '', 'client_name' => '', 'industry' => '', 'summary' => '',
            'metrics' => [['label' => '', 'value' => '']],
            'testimonial_quote' => '', 'testimonial_author' => '', 'is_published' => true, 'image_path' => null,
        ];
        $this->editing = true;
    }

    public function edit(int $id): void
    {
        $this->resetErrorBag();
        $cs = CaseStudy::findOrFail($id);
        $this->editId = $id;
        $this->image = null;
        $this->form = [
            'title' => $cs->title,
            'client_name' => (string) $cs->client_name,
            'industry' => (string) $cs->industry,
            'summary' => (string) $cs->summary,
            'metrics' => $cs->metrics ?: [['label' => '', 'value' => '']],
            'testimonial_quote' => (string) $cs->testimonial_quote,
            'testimonial_author' => (string) $cs->testimonial_author,
            'is_published' => $cs->is_published,
            'image_path' => $cs->image_path,
        ];
        $this->editing = true;
    }

    public function addMetric(): void
    {
        $this->form['metrics'][] = ['label' => '', 'value' => ''];
    }

    public function removeMetric(int $i): void
    {
        unset($this->form['metrics'][$i]);
        $this->form['metrics'] = array_values($this->form['metrics']);
    }

    public function save(): void
    {
        $this->validate([
            'form.title' => 'required|string|max:255',
            'form.client_name' => 'nullable|string|max:255',
            'form.industry' => 'nullable|string|max:255',
            'form.summary' => 'nullable|string|max:5000',
            'form.metrics.*.label' => 'nullable|string|max:80',
            'form.metrics.*.value' => 'nullable|string|max:40',
            'form.testimonial_quote' => 'nullable|string|max:2000',
            'form.testimonial_author' => 'nullable|string|max:255',
            'form.is_published' => 'boolean',
            'image' => 'nullable|image|max:5120',
        ]);

        $data = collect($this->form)->except('image_path')->map(fn ($v) => $v === '' ? null : $v)->all();
        $data['metrics'] = collect($this->form['metrics'])->filter(fn ($m) => trim($m['label'].$m['value']) !== '')->values()->all();

        if ($this->image) {
            if ($this->form['image_path']) {
                Storage::disk('public')->delete($this->form['image_path']);
            }
            $data['image_path'] = $this->image->store('case-studies', 'public');
        }

        $this->editId ? CaseStudy::findOrFail($this->editId)->update($data) : CaseStudy::create($data);
        $this->editing = false;
        $this->image = null;
        $this->dispatch('notify', message: 'Case study saved.');
    }

    public function delete(int $id): void
    {
        $cs = CaseStudy::findOrFail($id);
        if ($cs->image_path) {
            Storage::disk('public')->delete($cs->image_path);
        }
        $cs->delete();
        $this->dispatch('notify', message: 'Case study deleted.');
    }

    public function render()
    {
        return view('livewire.admin.case-studies', [
            'caseStudies' => CaseStudy::withCount('proposals')->latest()->get(),
        ]);
    }
}
