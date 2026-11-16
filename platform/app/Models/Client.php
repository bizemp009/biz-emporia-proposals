<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Client extends Model
{
    protected $fillable = [
        'company_name', 'brand_name', 'contact_person', 'email', 'website', 'industry',
    ];

    public function proposals(): HasMany
    {
        return $this->hasMany(Proposal::class);
    }

    public function getDisplayNameAttribute(): string
    {
        return $this->brand_name && $this->brand_name !== $this->company_name
            ? "{$this->company_name} · {$this->brand_name}"
            : $this->company_name;
    }
}
