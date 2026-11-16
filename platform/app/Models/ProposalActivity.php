<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ProposalActivity extends Model
{
    public const UPDATED_AT = null;

    protected $fillable = ['proposal_id', 'event', 'meta', 'ip_address', 'user_agent'];

    protected function casts(): array
    {
        return ['meta' => 'array'];
    }

    public function proposal(): BelongsTo
    {
        return $this->belongsTo(Proposal::class);
    }

    /** Events triggered by the client (as opposed to the agency). */
    public const CLIENT_EVENTS = ['viewed', 'service_toggled', 'signed', 'rejected', 'pdf_downloaded'];

    public function isClientEvent(): bool
    {
        return in_array($this->event, self::CLIENT_EVENTS, true);
    }

    public function getDescriptionAttribute(): string
    {
        $m = $this->meta ?? [];

        return match ($this->event) {
            'created' => 'Proposal created',
            'updated' => 'Proposal updated',
            'sent' => 'Marked as sent',
            'viewed' => 'Client opened the proposal',
            'service_toggled' => sprintf('Client %s "%s"', ($m['selected'] ?? false) ? 'added' : 'removed', $m['name'] ?? 'a service'),
            'signed' => sprintf('Signed by %s (%s)', $m['signer_name'] ?? '?', $m['signer_title'] ?? '?'),
            'rejected' => 'Client declined'.(! empty($m['reason']) ? ': '.$m['reason'] : ''),
            'pdf_downloaded' => 'PDF downloaded',
            'expired' => 'Proposal expired',
            'extended' => sprintf('Validity extended by %d days (until %s)', $m['days'] ?? 0, isset($m['until']) ? \Illuminate\Support\Carbon::parse($m['until'])->format('d M Y') : '?'),
            'duplicated' => 'Duplicated from proposal #'.($m['source_id'] ?? '?'),
            default => ucfirst(str_replace('_', ' ', $this->event)),
        };
    }
}
