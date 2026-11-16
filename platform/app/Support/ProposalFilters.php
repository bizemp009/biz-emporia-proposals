<?php

namespace App\Support;

use App\Models\Client;
use App\Models\Proposal;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Carbon;

/**
 * Shared search / filter / sort rules for the proposals dashboard and its CSV export,
 * so "Export" always returns exactly what the list shows.
 */
class ProposalFilters
{
    public const SORTS = ['updated', 'created', 'client', 'status', 'value', 'activity', 'expiry'];

    public const SORT_LABELS = [
        'updated' => 'Last updated',
        'created' => 'Date created',
        'client' => 'Client name',
        'status' => 'Status',
        'value' => 'Value',
        'activity' => 'Last activity',
        'expiry' => 'Expiry date',
    ];

    /** Keys accepted from the query string / component state. */
    public const KEYS = ['search', 'status', 'strategist', 'from', 'to', 'min', 'max', 'sort', 'dir'];

    public function __construct(
        public string $search = '',
        public string $status = '',
        public string $strategist = '',
        public string $from = '',
        public string $to = '',
        public string $min = '',
        public string $max = '',
        public string $sort = 'updated',
        public string $dir = 'desc',
    ) {
        if (! in_array($this->sort, self::SORTS, true)) {
            $this->sort = 'updated';
        }
        $this->dir = $this->dir === 'asc' ? 'asc' : 'desc';
    }

    public static function fromArray(array $input): self
    {
        $clean = [];
        foreach (self::KEYS as $key) {
            $value = $input[$key] ?? null;
            if (is_scalar($value) && $value !== '') {
                $clean[$key] = mb_substr(trim((string) $value), 0, 120);
            }
        }

        return new self(...$clean);
    }

    /** Number of filters (beyond search / status chips) currently narrowing the list. */
    public function advancedCount(): int
    {
        return collect([$this->strategist, $this->from, $this->to, $this->min, $this->max])->filter(fn ($v) => $v !== '')->count();
    }

    public function query(): Builder
    {
        $q = Proposal::query()->with('client')->withMax('activities', 'created_at');

        if ($this->search !== '') {
            $term = '%'.$this->search.'%';
            $q->where(fn ($q) => $q->where('title', 'like', $term)
                ->orWhere('slug', 'like', $term)
                ->orWhere('collaborator_name', 'like', $term)
                ->orWhereHas('client', fn ($c) => $c->where('company_name', 'like', $term)->orWhere('brand_name', 'like', $term)));
        }

        if (in_array($this->status, Proposal::STATUSES, true)) {
            $q->where('status', $this->status);
        }

        if ($this->strategist === '__none') {
            $q->where(fn ($q) => $q->whereNull('collaborator_name')->orWhere('collaborator_name', ''));
        } elseif ($this->strategist !== '') {
            $q->where('collaborator_name', $this->strategist);
        }

        if ($from = $this->date($this->from)) {
            $q->where('created_at', '>=', $from->startOfDay());
        }
        if ($to = $this->date($this->to)) {
            $q->where('created_at', '<=', $to->endOfDay());
        }
        if (is_numeric($this->min)) {
            $q->where('total_amount', '>=', (float) $this->min);
        }
        if (is_numeric($this->max)) {
            $q->where('total_amount', '<=', (float) $this->max);
        }

        $dir = $this->dir;
        match ($this->sort) {
            'created' => $q->orderBy('created_at', $dir),
            'client' => $q->orderBy(Client::select('company_name')->whereColumn('clients.id', 'proposals.client_id'), $dir),
            'status' => $q->orderByRaw(
                "CASE status WHEN 'draft' THEN 1 WHEN 'sent' THEN 2 WHEN 'viewed' THEN 3 WHEN 'accepted' THEN 4 WHEN 'rejected' THEN 5 ELSE 6 END ".$dir
            ),
            'value' => $q->orderBy('total_amount', $dir),
            'activity' => $q->orderByRaw('activities_max_created_at IS NULL')->orderBy('activities_max_created_at', $dir),
            'expiry' => $q->orderByRaw('expires_at IS NULL')->orderBy('expires_at', $dir),
            default => $q->orderBy('updated_at', $dir),
        };

        return $q->orderBy('id', $dir);
    }

    /** Non-default values only, for building URLs (e.g. the export link). */
    public function toQuery(): array
    {
        return array_filter([
            'search' => $this->search,
            'status' => $this->status,
            'strategist' => $this->strategist,
            'from' => $this->from,
            'to' => $this->to,
            'min' => $this->min,
            'max' => $this->max,
            'sort' => $this->sort === 'updated' ? '' : $this->sort,
            'dir' => $this->dir === 'desc' ? '' : $this->dir,
        ], fn ($v) => $v !== '');
    }

    private function date(string $value): ?Carbon
    {
        if (! preg_match('/^\d{4}-\d{2}-\d{2}$/', $value)) {
            return null;
        }

        try {
            return Carbon::createFromFormat('Y-m-d', $value);
        } catch (\Throwable) {
            return null;
        }
    }
}
