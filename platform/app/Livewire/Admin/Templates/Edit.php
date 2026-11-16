<?php

namespace App\Livewire\Admin\Templates;

use App\Livewire\Concerns\EditsProposalContent;
use App\Models\ProposalRoiMetric;
use App\Models\ProposalTemplate;
use App\Models\Setting;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Livewire\Attributes\Layout;
use Livewire\Component;
use Livewire\WithFileUploads;

/** Full editor for a template: every proposal section except the client details. */
#[Layout('layouts.admin')]
class Edit extends Component
{
    use EditsProposalContent;
    use WithFileUploads;

    public ?ProposalTemplate $template = null;

    public string $name = '';
    public string $description = '';
    public string $titlePattern = ProposalTemplate::DEFAULT_TITLE;
    public ?int $validityDays = null;

    public function mount(?ProposalTemplate $template = null): void
    {
        $this->roi = ProposalRoiMetric::DEFAULTS;
        $this->currency = Setting::get('default_currency', 'INR');

        if (! $template?->exists) {
            $this->template = null;
            $this->roadmap = $this->defaultRoadmap(3);

            return;
        }

        $this->template = $template;
        $this->name = $template->name;
        $this->description = (string) $template->description;
        $this->titlePattern = $template->title_pattern ?: ProposalTemplate::DEFAULT_TITLE;
        $this->validityDays = $template->validity_days;
        $this->fillContentFromTemplate($template);
    }

    protected function rules(): array
    {
        return [
            'name' => 'required|string|max:120',
            'description' => 'nullable|string|max:255',
            'titlePattern' => 'required|string|max:255',
            'validityDays' => 'nullable|integer|min:1|max:365',
        ] + $this->contentRules();
    }

    protected function messages(): array
    {
        return $this->contentMessages();
    }

    public function save(): void
    {
        $this->validate();

        $isNew = ! $this->template;

        $tpl = DB::transaction(function () {
            $tpl = $this->template ?? new ProposalTemplate;
            $tpl->fill($this->templateContentPayload() + [
                'name' => $this->name,
                'description' => $this->description ?: null,
                'title_pattern' => $this->titlePattern,
                'validity_days' => $this->validityDays ?: null,
            ]);
            $tpl->save();

            // Screenshots: delete files this template owns that were removed, keep / copy the rest, add uploads.
            $dir = "templates/{$tpl->id}";
            $kept = collect($this->screenshots)->pluck('path');
            foreach (collect($tpl->getOriginal('audit_screenshots') ?? [])->pluck('path')->diff($kept) as $removed) {
                if (str_starts_with($removed, $dir.'/')) {
                    Storage::disk('public')->delete($removed);
                }
            }
            $shots = ProposalTemplate::copyScreenshots($this->screenshots, $dir);
            foreach ($this->newScreenshots as $upload) {
                $shots[] = ['path' => $upload->store($dir, 'public'), 'caption' => ''];
            }
            $tpl->update(['audit_screenshots' => $shots]);

            return $tpl;
        });

        session()->flash('notify', $isNew ? 'Template created.' : 'Template saved.');
        $this->redirectRoute('admin.templates.edit', $tpl, navigate: true);
    }

    public function render()
    {
        return view('livewire.admin.templates.edit', $this->contentViewData())
            ->title($this->template ? 'Edit template' : 'New template');
    }
}
