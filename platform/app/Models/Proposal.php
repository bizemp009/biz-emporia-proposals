<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class Proposal extends Model
{
    public const STATUSES = ['draft', 'sent', 'viewed', 'accepted', 'rejected', 'expired'];

    protected $fillable = [
        'client_id', 'title', 'slug', 'collaborator_name', 'status',
        'executive_summary', 'audit_report', 'audit_screenshots', 'roadmap_milestones',
        'tenure_months', 'currency', 'booking_url', 'expires_at', 'total_amount',
        'sent_at', 'viewed_at', 'signed_at', 'rejected_at', 'rejection_reason',
        'client_signature', 'signer_name', 'signer_title', 'signer_ip',
    ];

    protected function casts(): array
    {
        return [
            'audit_screenshots' => 'array',
            'roadmap_milestones' => 'array',
            'total_amount' => 'decimal:2',
            'tenure_months' => 'integer',
            'expires_at' => 'datetime',
            'sent_at' => 'datetime',
            'viewed_at' => 'datetime',
            'signed_at' => 'datetime',
            'rejected_at' => 'datetime',
        ];
    }

    // ---------------------------------------------------------------- relations

    public function client(): BelongsTo
    {
        return $this->belongsTo(Client::class);
    }

    public function services(): HasMany
    {
        return $this->hasMany(ProposalService::class)->orderBy('sort_order')->orderBy('id');
    }

    public function roiMetrics(): HasOne
    {
        return $this->hasOne(ProposalRoiMetric::class);
    }

    public function caseStudies(): BelongsToMany
    {
        return $this->belongsToMany(CaseStudy::class);
    }

    public function activities(): HasMany
    {
        return $this->hasMany(ProposalActivity::class)->latest('created_at')->latest('id');
    }

    // ---------------------------------------------------------------- state

    public function isLocked(): bool
    {
        return in_array($this->status, ['accepted', 'rejected', 'expired'], true);
    }

    public function isPastExpiry(): bool
    {
        return $this->expires_at !== null && $this->expires_at->isPast();
    }

    /** Flip open proposals to "expired" once their validity date passes. */
    public function expireIfNeeded(): bool
    {
        if ($this->isPastExpiry() && in_array($this->status, ['draft', 'sent', 'viewed'], true)) {
            $this->update(['status' => 'expired']);
            $this->log('expired');

            return true;
        }

        return false;
    }

    /** Can the validity date still be moved? (Signed / declined proposals are final.) */
    public function canExtend(): bool
    {
        return ! in_array($this->status, ['accepted', 'rejected'], true);
    }

    /**
     * Push the expiry date out by $days (from today if it already lapsed) and re-open
     * an expired link to the state it was in before.
     */
    public function extendExpiry(int $days): bool
    {
        if (! $this->canExtend() || $days < 1) {
            return false;
        }

        $base = $this->expires_at && $this->expires_at->isFuture() ? $this->expires_at->copy() : now();
        $this->expires_at = $base->addDays($days)->endOfDay();

        if ($this->status === 'expired') {
            $this->status = $this->viewed_at ? 'viewed' : ($this->sent_at ? 'sent' : 'draft');
        }
        $this->save();
        $this->log('extended', ['days' => $days, 'until' => $this->expires_at->toDateString()]);

        return true;
    }

    public function markSent(): bool
    {
        if ($this->status !== 'draft') {
            return false;
        }
        $this->update(['status' => 'sent', 'sent_at' => now()]);
        $this->log('sent');

        return true;
    }

    public function log(string $event, array $meta = [], bool $withRequest = false): void
    {
        $this->activities()->create([
            'event' => $event,
            'meta' => $meta ?: null,
            'ip_address' => $withRequest ? request()->ip() : null,
            'user_agent' => $withRequest ? Str::limit((string) request()->userAgent(), 250, '') : null,
        ]);
    }

    public function getPublicUrlAttribute(): string
    {
        return route('proposal.show', $this->slug);
    }

    public function screenshotUrls(): array
    {
        return collect($this->audit_screenshots ?? [])
            ->map(fn ($s) => [
                'url' => Storage::disk('public')->url($s['path']),
                'path' => $s['path'],
                'caption' => $s['caption'] ?? '',
            ])->all();
    }

    // ---------------------------------------------------------------- pricing

    /**
     * Price breakdown for a set of selected line items.
     * Contract value = one-time + monthly x tenure + quarterly x ceil(tenure / 3).
     *
     * @param  Collection<int, ProposalService>|null  $services
     */
    public function pricing(?Collection $services = null): array
    {
        $services ??= $this->services->where('is_selected', true);
        $tenure = max(1, (int) $this->tenure_months);

        $oneTime = (float) $services->where('billing_type', 'one_time')->sum('price');
        $monthly = (float) $services->where('billing_type', 'monthly')->sum('price');
        $quarterly = (float) $services->where('billing_type', 'quarterly')->sum('price');

        return [
            'one_time' => $oneTime,
            'monthly' => $monthly,
            'quarterly' => $quarterly,
            'tenure' => $tenure,
            'contract_value' => round($oneTime + $monthly * $tenure + $quarterly * (int) ceil($tenure / 3), 2),
        ];
    }

    public function money(float|int|string|null $amount, bool $decimals = false): string
    {
        return self::formatMoney((float) $amount, $this->currency, $decimals);
    }

    public static function formatMoney(float $amount, ?string $currency, bool $decimals = false): string
    {
        $symbol = match (strtoupper((string) $currency)) {
            'INR' => '₹', 'USD' => '$', 'EUR' => '€', 'GBP' => '£', 'AUD' => 'A$', 'CAD' => 'C$', 'AED' => 'AED ',
            default => strtoupper((string) $currency).' ',
        };

        if (strtoupper((string) $currency) === 'INR') {
            return $symbol.self::indianFormat($amount, $decimals);
        }

        return $symbol.number_format($amount, $decimals ? 2 : 0);
    }

    /** 12,34,567 style grouping for INR. */
    private static function indianFormat(float $amount, bool $decimals): string
    {
        $negative = $amount < 0;
        $amount = abs($amount);
        $whole = (string) (int) floor($amount);
        $fraction = $decimals ? '.'.str_pad((string) round(($amount - floor($amount)) * 100), 2, '0', STR_PAD_LEFT) : '';

        if (strlen($whole) > 3) {
            $last3 = substr($whole, -3);
            $rest = substr($whole, 0, -3);
            $rest = preg_replace('/\B(?=(\d{2})+(?!\d))/', ',', $rest);
            $whole = $rest.','.$last3;
        }

        return ($negative ? '-' : '').$whole.$fraction;
    }

    // ---------------------------------------------------------------- slug & duplication

    public static function uniqueSlug(string $base, ?int $ignoreId = null): string
    {
        $base = Str::slug($base) ?: 'proposal';
        $slug = $base;
        $i = 2;
        while (static::where('slug', $slug)->when($ignoreId, fn ($q) => $q->where('id', '!=', $ignoreId))->exists()) {
            $slug = "{$base}-{$i}";
            $i++;
        }

        return $slug;
    }

    public function duplicate(): self
    {
        return DB::transaction(function () {
            $this->loadMissing(['services', 'roiMetrics', 'caseStudies']);

            $copy = $this->replicate([
                'status', 'sent_at', 'viewed_at', 'signed_at', 'rejected_at', 'rejection_reason',
                'client_signature', 'signer_name', 'signer_title', 'signer_ip',
            ]);
            $copy->title = $this->title.' (Copy)';
            $copy->slug = self::uniqueSlug($this->slug.'-copy');
            $copy->status = 'draft';
            $copy->expires_at = $this->expires_at?->isPast() ? now()->addDays(30) : $this->expires_at;
            $copy->save();

            foreach ($this->services as $service) {
                $copy->services()->create($service->replicate(['proposal_id'])->toArray());
            }
            if ($this->roiMetrics) {
                $copy->roiMetrics()->create($this->roiMetrics->replicate(['proposal_id'])->toArray());
            }
            $copy->caseStudies()->sync($this->caseStudies->pluck('id'));
            $copy->log('duplicated', ['source_id' => $this->id]);

            return $copy;
        });
    }
}
