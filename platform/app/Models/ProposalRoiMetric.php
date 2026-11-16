<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ProposalRoiMetric extends Model
{
    protected $fillable = [
        'proposal_id', 'default_budget_or_spend', 'default_working_hours', 'target_conversion_rate',
        'projected_lift_multiplier', 'baseline_monthly_traffic', 'cost_per_click',
        'average_order_value', 'hourly_rate',
    ];

    protected function casts(): array
    {
        return [
            'default_budget_or_spend' => 'float',
            'default_working_hours' => 'float',
            'target_conversion_rate' => 'float',
            'projected_lift_multiplier' => 'float',
            'baseline_monthly_traffic' => 'integer',
            'cost_per_click' => 'float',
            'average_order_value' => 'float',
            'hourly_rate' => 'float',
        ];
    }

    public const DEFAULTS = [
        'default_budget_or_spend' => 50000,
        'default_working_hours' => 40,
        'target_conversion_rate' => 2.5,
        'projected_lift_multiplier' => 1.4,
        'baseline_monthly_traffic' => 5000,
        'cost_per_click' => 25,
        'average_order_value' => 5000,
        'hourly_rate' => 1500,
    ];

    public function proposal(): BelongsTo
    {
        return $this->belongsTo(Proposal::class);
    }
}
