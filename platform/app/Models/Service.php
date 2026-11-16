<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/** Catalog service: the reusable source for proposal line items. */
class Service extends Model
{
    protected $fillable = [
        'name', 'category', 'description', 'deliverables', 'default_price',
        'billing_type', 'is_optional', 'is_active',
    ];

    protected function casts(): array
    {
        return [
            'deliverables' => 'array',
            'default_price' => 'decimal:2',
            'is_optional' => 'boolean',
            'is_active' => 'boolean',
        ];
    }
}
