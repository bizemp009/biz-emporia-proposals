<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;

class CaseStudy extends Model
{
    protected $fillable = [
        'title', 'client_name', 'industry', 'summary', 'metrics', 'image_path',
        'testimonial_quote', 'testimonial_author', 'is_published',
    ];

    protected function casts(): array
    {
        return [
            'metrics' => 'array',
            'is_published' => 'boolean',
        ];
    }

    public function proposals(): \Illuminate\Database\Eloquent\Relations\BelongsToMany
    {
        return $this->belongsToMany(Proposal::class);
    }

    public function getImageUrlAttribute(): ?string
    {
        return $this->image_path ? Storage::disk('public')->url($this->image_path) : null;
    }
}
