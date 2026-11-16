<?php

namespace App\Http\Controllers;

use App\Models\Proposal;
use App\Models\Setting;
use App\Support\RichText;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\Response;

class ProposalPdfController extends Controller
{
    public function admin(Proposal $proposal): Response
    {
        return $this->render($proposal, inline: true);
    }

    public function client(string $slug): Response
    {
        $proposal = Proposal::where('slug', $slug)->firstOrFail();
        abort_if($proposal->status === 'draft' && ! Auth::check(), 404);

        if (! Auth::check()) {
            $proposal->log('pdf_downloaded', [], withRequest: true);
        }

        return $this->render($proposal, inline: false);
    }

    private function render(Proposal $proposal, bool $inline): Response
    {
        $proposal->load(['client', 'services', 'roiMetrics', 'caseStudies']);

        // Dompdf can't fetch URLs reliably on all hosts; embed images as data URIs.
        $screenshots = collect($proposal->audit_screenshots ?? [])->map(function ($s) {
            $disk = Storage::disk('public');
            if (! $disk->exists($s['path'])) {
                return null;
            }

            return [
                'src' => 'data:'.$disk->mimeType($s['path']).';base64,'.base64_encode($disk->get($s['path'])),
                'caption' => $s['caption'] ?? '',
            ];
        })->filter()->values();

        $pdf = Pdf::loadView('pdf.proposal', [
            'proposal' => $proposal,
            'settings' => Setting::allValues(),
            'pricing' => $proposal->pricing(),
            'screenshots' => $screenshots,
            'summary' => RichText::clean($proposal->executive_summary),
            'audit' => RichText::clean($proposal->audit_report),
        ])->setPaper('a4');

        $filename = 'proposal-'.$proposal->slug.'.pdf';

        return $inline ? $pdf->stream($filename) : $pdf->download($filename);
    }
}
