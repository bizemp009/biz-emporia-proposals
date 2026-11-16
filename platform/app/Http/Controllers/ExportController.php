<?php

namespace App\Http\Controllers;

use App\Models\Client;
use App\Models\Proposal;
use App\Support\ProposalFilters;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ExportController extends Controller
{
    /** Proposals CSV — honours the same search / filters / sort as the dashboard. */
    public function proposals(Request $request): StreamedResponse
    {
        $query = ProposalFilters::fromArray($request->query())->query();

        return $this->csv('proposals-'.now()->format('Y-m-d').'.csv', [
            'ID', 'Company', 'Brand', 'Contact', 'Email', 'Title', 'Status', 'Strategist',
            'Currency', 'Value', 'Created', 'Sent', 'Viewed', 'Signed', 'Declined', 'Expires', 'Last activity', 'Link',
        ], function ($out) use ($query) {
            $query->chunk(200, function ($proposals) use ($out) {
                foreach ($proposals as $p) {
                    /** @var Proposal $p */
                    $this->row($out, [
                        $p->id, $p->client->company_name, $p->client->brand_name, $p->client->contact_person, $p->client->email,
                        $p->title, ucfirst($p->status), $p->collaborator_name, $p->currency, number_format((float) $p->total_amount, 2, '.', ''),
                        $p->created_at?->format('Y-m-d H:i'), $p->sent_at?->format('Y-m-d H:i'), $p->viewed_at?->format('Y-m-d H:i'),
                        $p->signed_at?->format('Y-m-d H:i'), $p->rejected_at?->format('Y-m-d H:i'), $p->expires_at?->format('Y-m-d'),
                        $p->activities_max_created_at ? substr((string) $p->activities_max_created_at, 0, 16) : null,
                        $p->public_url,
                    ]);
                }
            });
        });
    }

    public function clients(Request $request): StreamedResponse
    {
        $search = mb_substr(trim((string) $request->query('search', '')), 0, 120);
        $query = Client::withCount('proposals')
            ->withSum(['proposals as won_value' => fn ($q) => $q->where('status', 'accepted')], 'total_amount')
            ->when($search !== '', fn ($q) => $q->where(fn ($q) => $q
                ->where('company_name', 'like', "%{$search}%")
                ->orWhere('brand_name', 'like', "%{$search}%")
                ->orWhere('contact_person', 'like', "%{$search}%")
                ->orWhere('email', 'like', "%{$search}%")))
            ->orderBy('company_name');

        return $this->csv('clients-'.now()->format('Y-m-d').'.csv', [
            'ID', 'Company', 'Brand', 'Contact', 'Email', 'Website', 'Industry', 'Proposals', 'Won value', 'Created',
        ], function ($out) use ($query) {
            $query->chunk(200, function ($clients) use ($out) {
                foreach ($clients as $c) {
                    $this->row($out, [
                        $c->id, $c->company_name, $c->brand_name, $c->contact_person, $c->email, $c->website, $c->industry,
                        $c->proposals_count, number_format((float) $c->won_value, 2, '.', ''), $c->created_at?->format('Y-m-d'),
                    ]);
                }
            });
        });
    }

    private function csv(string $filename, array $headings, callable $body): StreamedResponse
    {
        return response()->streamDownload(function () use ($headings, $body) {
            $out = fopen('php://output', 'w');
            fwrite($out, "\xEF\xBB\xBF"); // UTF-8 BOM so Excel shows ₹ and accents correctly
            fputcsv($out, $headings, escape: '');
            $body($out);
            fclose($out);
        }, $filename, ['Content-Type' => 'text/csv; charset=UTF-8']);
    }

    /** Write one row, neutralising spreadsheet formula injection (=, +, -, @ …). */
    private function row($out, array $values): void
    {
        fputcsv($out, array_map(function ($v) {
            $v = (string) ($v ?? '');

            return $v !== '' && in_array($v[0], ['=', '+', '-', '@', "\t", "\r"], true) && ! is_numeric($v) ? "'".$v : $v;
        }, $values), escape: '');
    }
}
