<?php

namespace App\Livewire\Admin;

use App\Models\ProposalTemplate;
use Illuminate\Support\Facades\DB;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Layout('layouts.admin')]
#[Title('Templates')]
class Templates extends Component
{
    public function duplicate(int $id): void
    {
        $src = ProposalTemplate::findOrFail($id);

        DB::transaction(function () use ($src) {
            $copy = $src->replicate();
            $copy->name = $src->name.' (Copy)';
            $copy->save();
            $copy->update(['audit_screenshots' => ProposalTemplate::copyScreenshots($src->audit_screenshots ?? [], "templates/{$copy->id}")]);
        });

        $this->dispatch('notify', message: 'Template duplicated.');
    }

    public function delete(int $id): void
    {
        ProposalTemplate::findOrFail($id)->delete();
        $this->dispatch('notify', message: 'Template deleted.');
    }

    public function render()
    {
        return view('livewire.admin.templates', [
            'templates' => ProposalTemplate::orderBy('name')->get(),
        ]);
    }
}
