<?php

namespace App\Livewire\Client;

use App\Models\Proposal;
use App\Models\ProposalRoiMetric;
use App\Models\Setting;
use App\Support\RichText;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Locked;
use Livewire\Component;

#[Layout('layouts.guest')]
class ProposalViewer extends Component
{
    #[Locked]
    public Proposal $proposal;

    #[Locked]
    public bool $preview = false;

    /** @var int[] */
    #[Locked]
    public array $selectedServices = [];

    /** Monthly-equivalent agency fee, read live by the Alpine ROI simulator. */
    #[Locked]
    public float $monthlyInvestment = 0;

    public string $signerName = '';
    public string $signerTitle = '';
    public string $signatureData = '';
    public bool $agree = false;

    public string $declineReason = '';

    public function mount(string $slug): void
    {
        $this->proposal = Proposal::with(['client', 'services', 'roiMetrics', 'caseStudies'])
            ->where('slug', $slug)
            ->firstOrFail();

        // Logged-in agency staff get a non-tracking preview (and can see drafts).
        $this->preview = Auth::check();
        abort_if($this->proposal->status === 'draft' && ! $this->preview, 404);

        $this->proposal->expireIfNeeded();

        if (! $this->preview) {
            $this->trackView();
        }

        $this->selectedServices = $this->proposal->services
            ->filter(fn ($s) => $s->is_selected || ! $s->is_optional)
            ->pluck('id')->all();
        $this->signerName = (string) $this->proposal->client->contact_person;
        $this->refreshTotals();
    }

    private function trackView(): void
    {
        if (! $this->proposal->viewed_at) {
            $this->proposal->update([
                'viewed_at' => now(),
                'status' => $this->proposal->status === 'sent' ? 'viewed' : $this->proposal->status,
            ]);
        }

        // One "viewed" log entry per browser session to keep the log readable.
        $key = 'viewed_proposal_'.$this->proposal->id;
        if (! session()->has($key)) {
            session()->put($key, true);
            $this->proposal->log('viewed', [], withRequest: true);
        }
    }

    // ---------------------------------------------------------------- pricing

    #[Computed]
    public function pricing(): array
    {
        return $this->proposal->pricing($this->proposal->services->whereIn('id', $this->selectedServices));
    }

    private function refreshTotals(): void
    {
        unset($this->pricing);
        $p = $this->pricing;
        $this->monthlyInvestment = round($p['contract_value'] / max(1, $p['tenure']), 2);
    }

    public function toggleService(int $serviceId): void
    {
        if ($this->proposal->isLocked()) {
            return;
        }

        $service = $this->proposal->services->firstWhere('id', $serviceId);
        if (! $service || ! $service->is_optional) {
            return; // mandatory items cannot be removed
        }

        $selected = ! in_array($serviceId, $this->selectedServices, true);
        $this->selectedServices = $selected
            ? array_values(array_unique([...$this->selectedServices, $serviceId]))
            : array_values(array_diff($this->selectedServices, [$serviceId]));

        $this->refreshTotals();

        if (! $this->preview) {
            $service->update(['is_selected' => $selected]);
            $this->proposal->update(['total_amount' => $this->pricing['contract_value']]);
            $this->proposal->log('service_toggled', ['name' => $service->name, 'selected' => $selected], withRequest: true);
        }
    }

    // ---------------------------------------------------------------- sign / decline

    public function signProposal(): void
    {
        if ($this->proposal->isLocked()) {
            return;
        }
        if ($this->preview) {
            $this->dispatch('notify', message: 'Preview mode: sign-off is disabled while you are logged in as staff.', type: 'error');

            return;
        }

        $this->validate([
            'signerName' => 'required|string|max:255',
            'signerTitle' => 'required|string|max:255',
            'agree' => 'accepted',
            'signatureData' => ['required', 'string', 'max:700000', 'regex:/^data:image\/png;base64,[A-Za-z0-9+\/=]+$/'],
        ], [
            'signatureData.required' => 'Please draw your signature in the box.',
            'signatureData.*' => 'The signature could not be read. Please clear it and sign again.',
            'agree.accepted' => 'Please confirm you agree to the scope and terms.',
        ]);

        DB::transaction(function () {
            // Persist the final selection that is being signed.
            foreach ($this->proposal->services as $s) {
                $s->update(['is_selected' => in_array($s->id, $this->selectedServices, true)]);
            }

            $this->proposal->update([
                'status' => 'accepted',
                'signer_name' => $this->signerName,
                'signer_title' => $this->signerTitle,
                'client_signature' => $this->signatureData,
                'signer_ip' => request()->ip(),
                'signed_at' => now(),
                'total_amount' => $this->pricing['contract_value'],
            ]);

            $this->proposal->log('signed', [
                'signer_name' => $this->signerName,
                'signer_title' => $this->signerTitle,
                'total' => $this->pricing['contract_value'],
                'services' => $this->proposal->services->whereIn('id', $this->selectedServices)->pluck('name')->all(),
            ], withRequest: true);
        });

        $this->signatureData = '';
        $this->dispatch('notify', message: 'Proposal successfully signed and approved!');
        $this->dispatch('proposal-signed');
    }

    public function decline(): void
    {
        if ($this->proposal->isLocked() || $this->preview) {
            return;
        }
        $this->validate(['declineReason' => 'nullable|string|max:2000']);

        $this->proposal->update([
            'status' => 'rejected',
            'rejected_at' => now(),
            'rejection_reason' => $this->declineReason ?: null,
        ]);
        $this->proposal->log('rejected', ['reason' => $this->declineReason], withRequest: true);
        $this->dispatch('notify', message: 'Thanks for letting us know — we have recorded your response.');
    }

    // ---------------------------------------------------------------- view

    public function render()
    {
        $p = $this->proposal;
        $settings = Setting::allValues();
        $metrics = $p->roiMetrics ?? new ProposalRoiMetric(ProposalRoiMetric::DEFAULTS);

        return view('livewire.client.proposal-viewer', [
            'settings' => $settings,
            'summary' => RichText::clean($p->executive_summary),
            'audit' => RichText::clean($p->audit_report),
            'screenshots' => $p->screenshotUrls(),
            'groupedServices' => $p->services->groupBy(fn ($s) => $s->is_optional ? 'optional' : 'core'),
            'bookingUrl' => $p->booking_url ?: ($settings['default_booking_url'] ?? null),
            'roiConfig' => [
                'spend' => (float) $metrics->default_budget_or_spend,
                'hours' => (float) $metrics->default_working_hours,
                'cr' => (float) $metrics->target_conversion_rate,
                'lift' => (float) $metrics->projected_lift_multiplier,
                'baseline' => (int) $metrics->baseline_monthly_traffic,
                'cpc' => (float) $metrics->cost_per_click,
                'aov' => (float) $metrics->average_order_value,
                'hourlyRate' => (float) $metrics->hourly_rate,
                'currency' => $p->currency,
                'tenure' => (int) $p->tenure_months,
            ],
        ])->title($p->title);
    }
}
