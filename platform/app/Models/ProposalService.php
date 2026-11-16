<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ProposalService extends Model
{
    protected $fillable = [
        'proposal_id', 'service_id', 'name', 'description', 'deliverables', 'price',
        'billing_type', 'is_optional', 'is_selected', 'sort_order',
    ];

    protected function casts(): array
    {
        return [
            'deliverables' => 'array',
            'price' => 'decimal:2',
            'is_optional' => 'boolean',
            'is_selected' => 'boolean',
        ];
    }

    public const BILLING_LABELS = [
        'one_time' => 'one-time',
        'monthly' => '/ month',
        'quarterly' => '/ quarter',
    ];

    public function proposal(): BelongsTo
    {
        return $this->belongsTo(Proposal::class);
    }
}
