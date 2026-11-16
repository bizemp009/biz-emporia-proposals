<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="utf-8">
<title>{{ $proposal->title }}</title>
@php
    $hex = fn ($v, $d) => preg_match('/^#[0-9a-fA-F]{6}$/', (string) $v) ? $v : $d;
    $primary = $hex($settings['brand_primary'], '#1a1a2e');
    $accent = $hex($settings['brand_accent'], '#7c5cff');
    $highlight = $hex($settings['brand_highlight'], '#e6499b');
    $client = $proposal->client;
    $selected = $proposal->services->where('is_selected', true);
@endphp
<style>
    @page { margin: 28mm 16mm 20mm 16mm; }
    body { font-family: 'DejaVu Sans', sans-serif; font-size: 10pt; color: #1e293b; line-height: 1.5; }
    header.page { position: fixed; top: -20mm; left: 0; right: 0; height: 12mm; border-bottom: 2px solid {{ $accent }}; font-size: 8.5pt; color: #64748b; }
    footer.page { position: fixed; bottom: -14mm; left: 0; right: 0; height: 8mm; font-size: 7.5pt; color: #94a3b8; text-align: center; }
    h1 { font-size: 22pt; margin: 0 0 4pt; color: {{ $primary }}; line-height: 1.2; }
    h2 { font-size: 13pt; color: {{ $primary }}; margin: 22pt 0 8pt; padding-bottom: 4pt; border-bottom: 1px solid #e2e8f0; }
    h3 { font-size: 11pt; margin: 10pt 0 4pt; color: #0f172a; }
    .kicker { color: {{ $highlight }}; font-size: 8.5pt; font-weight: bold; text-transform: uppercase; letter-spacing: 1px; }
    .muted { color: #64748b; }
    .small { font-size: 8.5pt; }
    table { width: 100%; border-collapse: collapse; }
    .meta td { padding: 2pt 12pt 2pt 0; vertical-align: top; font-size: 9pt; }
    .meta .k { color: #64748b; width: 90pt; }
    .items th { text-align: left; font-size: 8pt; text-transform: uppercase; color: #64748b; border-bottom: 1px solid #cbd5e1; padding: 5pt 4pt; }
    .items td { padding: 6pt 4pt; border-bottom: 1px solid #f1f5f9; vertical-align: top; }
    .items .num { text-align: right; white-space: nowrap; }
    .tag { display: inline-block; font-size: 7pt; padding: 1pt 4pt; border-radius: 3pt; background: #fef3c7; color: #92400e; }
    .totals td { padding: 3pt 4pt; }
    .totals .grand td { font-size: 12pt; font-weight: bold; color: {{ $primary }}; border-top: 2px solid {{ $primary }}; padding-top: 6pt; }
    ul { margin: 2pt 0 6pt 14pt; padding: 0; }
    li { margin-bottom: 2pt; }
    .box { border: 1px solid #e2e8f0; border-radius: 6pt; padding: 8pt 10pt; margin-bottom: 8pt; }
    .shots td { width: 50%; padding: 4pt; vertical-align: top; }
    .shots img { width: 100%; border: 1px solid #e2e8f0; }
    .metric { display: inline-block; margin-right: 14pt; }
    .metric b { font-size: 12pt; color: {{ $accent }}; }
    .sig img { height: 60pt; border-bottom: 1px solid #94a3b8; }
    .avoid { page-break-inside: avoid; }
    .rich p, .rich div { margin: 0 0 5pt; }
</style>
</head>
<body>
<header class="page">
    <table><tr>
        <td><b style="color: {{ $primary }}">{{ $settings['agency_name'] }}</b> · {{ $settings['agency_tagline'] }}</td>
        <td style="text-align:right">Proposal for {{ $client->company_name }}</td>
    </tr></table>
</header>
<footer class="page">
    Confidential — prepared for {{ $client->company_name }} · {{ $settings['agency_email'] }} · {{ $proposal->public_url }}
</footer>

<div class="kicker">Prepared for {{ $client->brand_name ?: $client->company_name }}</div>
<h1>{{ $proposal->title }}</h1>
<table class="meta" style="width:auto; margin-top: 6pt;">
    <tr><td class="k">Client</td><td>{{ $client->company_name }}{{ $client->website ? ' · '.$client->website : '' }}</td></tr>
    <tr><td class="k">Attention</td><td>{{ $client->contact_person }} &lt;{{ $client->email }}&gt;</td></tr>
    @if ($proposal->collaborator_name)<tr><td class="k">Strategist</td><td>{{ $proposal->collaborator_name }}</td></tr>@endif
    <tr><td class="k">Issued</td><td>{{ ($proposal->sent_at ?? $proposal->created_at)->format('d M Y') }}</td></tr>
    @if ($proposal->expires_at)<tr><td class="k">Valid until</td><td>{{ $proposal->expires_at->format('d M Y') }}</td></tr>@endif
    <tr><td class="k">Status</td><td>{{ ucfirst($proposal->status) }}</td></tr>
</table>

@if ($summary !== '')
    <h2>1. Executive summary</h2>
    <div class="rich">{!! $summary !!}</div>
@endif

@if ($audit !== '')
    <h2>{{ $summary !== '' ? '' : '1. ' }}Mini-audit findings</h2>
    <div class="rich">{!! $audit !!}</div>
@endif

@if ($screenshots->isNotEmpty())
    <table class="shots">
        @foreach ($screenshots->chunk(2) as $row)
            <tr class="avoid">
                @foreach ($row as $shot)
                    <td><img src="{{ $shot['src'] }}" alt=""><div class="small muted">{{ $shot['caption'] }}</div></td>
                @endforeach
            </tr>
        @endforeach
    </table>
@endif

@if ($proposal->services->isNotEmpty())
    <h2>2. Recommended services &amp; scope</h2>
    @foreach ($selected as $s)
        <div class="avoid">
            <h3>{{ $s->name }}</h3>
            @if ($s->description)<div class="muted">{{ $s->description }}</div>@endif
            @if ($s->deliverables)
                <ul>@foreach ($s->deliverables as $d)<li>{{ $d }}</li>@endforeach</ul>
            @endif
        </div>
    @endforeach
@endif

@if ($proposal->roiMetrics)
    @php
        $m = $proposal->roiMetrics;
        $traffic = $m->baseline_monthly_traffic * $m->projected_lift_multiplier + ($m->cost_per_click > 0 ? $m->default_budget_or_spend / $m->cost_per_click : 0);
        $conv = $traffic * $m->target_conversion_rate / 100;
        $rev = $conv * $m->average_order_value;
    @endphp
    <h2>3. Projected KPIs (default scenario)</h2>
    <table class="items">
        <tr><th>Assumption</th><th class="num">Value</th><th>Projection (monthly, at full ramp)</th><th class="num">Value</th></tr>
        <tr><td>Ad spend</td><td class="num">{{ $proposal->money($m->default_budget_or_spend) }}</td><td>Traffic</td><td class="num">{{ number_format($traffic) }}</td></tr>
        <tr><td>Cost per click</td><td class="num">{{ $proposal->money($m->cost_per_click, true) }}</td><td>Conversions</td><td class="num">{{ number_format($conv) }}</td></tr>
        <tr><td>Conversion rate</td><td class="num">{{ $m->target_conversion_rate }}%</td><td>Revenue</td><td class="num">{{ $proposal->money($rev) }}</td></tr>
        <tr><td>Organic lift</td><td class="num">× {{ $m->projected_lift_multiplier }}</td><td>Team effort</td><td class="num">{{ number_format($m->default_working_hours) }} hrs</td></tr>
    </table>
    <p class="small muted">Illustrative estimate. Use the live simulator at {{ $proposal->public_url }} to model other scenarios.</p>
@endif

@if ($proposal->caseStudies->isNotEmpty())
    <h2>4. Proven results</h2>
    @foreach ($proposal->caseStudies as $cs)
        <div class="box avoid">
            <b>{{ $cs->title }}</b> <span class="muted small">{{ $cs->industry }}</span><br>
            @foreach ($cs->metrics ?? [] as $metric)
                <span class="metric"><b>{{ $metric['value'] }}</b> <span class="small muted">{{ $metric['label'] }}</span></span>
            @endforeach
            @if ($cs->summary)<div class="small" style="margin-top:3pt">{{ $cs->summary }}</div>@endif
            @if ($cs->testimonial_quote)<div class="small muted" style="margin-top:3pt"><i>“{{ $cs->testimonial_quote }}”</i> — {{ $cs->testimonial_author }}</div>@endif
        </div>
    @endforeach
@endif

@if ($proposal->services->isNotEmpty())
    <h2>5. Investment</h2>
    <table class="items avoid">
        <tr><th>Service</th><th>Billing</th><th class="num">Price</th><th class="num">Included</th></tr>
        @foreach ($proposal->services as $s)
            <tr style="{{ $s->is_selected ? '' : 'color:#94a3b8' }}">
                <td>{{ $s->name }} @if ($s->is_optional)<span class="tag">Add-on</span>@endif</td>
                <td>{{ str_replace('_', '-', ucfirst($s->billing_type)) }}</td>
                <td class="num">{{ $proposal->money($s->price) }}</td>
                <td class="num">{{ $s->is_selected ? '✔' : '—' }}</td>
            </tr>
        @endforeach
    </table>
    <table class="totals" style="width: 55%; margin-left: 45%; margin-top: 8pt;">
        <tr><td class="muted">One-time</td><td class="num" style="text-align:right">{{ $proposal->money($pricing['one_time']) }}</td></tr>
        <tr><td class="muted">Monthly × {{ $pricing['tenure'] }}</td><td style="text-align:right">{{ $proposal->money($pricing['monthly'] * $pricing['tenure']) }}</td></tr>
        @if ($pricing['quarterly'] > 0)
            <tr><td class="muted">Quarterly × {{ (int) ceil($pricing['tenure'] / 3) }}</td><td style="text-align:right">{{ $proposal->money($pricing['quarterly'] * ceil($pricing['tenure'] / 3)) }}</td></tr>
        @endif
        <tr class="grand"><td>Total contract value</td><td style="text-align:right">{{ $proposal->money($pricing['contract_value']) }}</td></tr>
    </table>
@endif

@if (! empty($proposal->roadmap_milestones))
    <h2>6. Execution roadmap ({{ $proposal->tenure_months }} months)</h2>
    @foreach ($proposal->roadmap_milestones as $i => $ms)
        <div class="box avoid">
            <b style="color: {{ $accent }}">{{ $i + 1 }}. {{ $ms['title'] }}</b>
            @if (! empty($ms['items']))<ul>@foreach ($ms['items'] as $item)<li>{{ $item }}</li>@endforeach</ul>@endif
        </div>
    @endforeach
@endif

<h2>7. Authorization</h2>
@if ($settings['terms'])<p class="small muted" style="white-space: pre-line">{{ $settings['terms'] }}</p>@endif
<table class="avoid" style="margin-top: 14pt;">
    <tr>
        <td style="width:50%; vertical-align: bottom;" class="sig">
            @if ($proposal->client_signature)
                <img src="{{ $proposal->client_signature }}" alt="Signature"><br>
                <b>{{ $proposal->signer_name }}</b>, {{ $proposal->signer_title }}<br>
                <span class="small muted">Signed electronically {{ $proposal->signed_at?->format('d M Y H:i T') }}{{ $proposal->signer_ip ? ' · IP '.$proposal->signer_ip : '' }}</span>
            @else
                <div style="height: 50pt; border-bottom: 1px solid #94a3b8;"></div>
                <span class="small muted">Client signature · Name · Title · Date</span>
            @endif
        </td>
        <td style="width:50%; vertical-align: bottom; padding-left: 20pt;">
            <div style="height: 50pt; border-bottom: 1px solid #94a3b8;"></div>
            <span class="small muted">For {{ $settings['agency_name'] }}{{ $proposal->collaborator_name ? ' · '.$proposal->collaborator_name : '' }}</span>
        </td>
    </tr>
</table>
</body>
</html>
