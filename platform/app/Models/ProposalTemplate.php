<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class ProposalTemplate extends Model
{
    public const DEFAULT_TITLE = 'Growth Proposal for {client}';

    protected $fillable = [
        'name', 'description', 'title_pattern', 'collaborator_name',
        'executive_summary', 'audit_report', 'audit_screenshots', 'roadmap_milestones',
        'service_ids', 'service_lines', 'case_study_ids', 'roi_defaults', 'tenure_months',
        'currency', 'booking_url', 'validity_days',
    ];

    protected function casts(): array
    {
        return [
            'roadmap_milestones' => 'array',
            'audit_screenshots' => 'array',
            'service_ids' => 'array',
            'service_lines' => 'array',
            'case_study_ids' => 'array',
            'roi_defaults' => 'array',
            'tenure_months' => 'integer',
            'validity_days' => 'integer',
        ];
    }

    protected static function booted(): void
    {
        static::deleted(fn (self $t) => Storage::disk('public')->deleteDirectory("templates/{$t->id}"));
    }

    /**
     * Service lines in the stored shape. Older templates only kept catalog ids,
     * so those are expanded from the catalog's current defaults.
     *
     * @return array<int, array{service_id: ?int, name: string, description: ?string, deliverables: array, price: float, billing_type: string, is_optional: bool, is_selected: bool}>
     */
    public function lines(): array
    {
        if (is_array($this->service_lines)) {
            return $this->service_lines;
        }

        $ids = $this->service_ids ?? [];

        return Service::whereIn('id', $ids)->get()
            ->sortBy(fn ($s) => array_search($s->id, $ids))
            ->map(fn (Service $s) => [
                'service_id' => $s->id,
                'name' => $s->name,
                'description' => $s->description,
                'deliverables' => $s->deliverables ?? [],
                'price' => (float) $s->default_price,
                'billing_type' => $s->billing_type,
                'is_optional' => (bool) $s->is_optional,
                'is_selected' => ! $s->is_optional,
            ])->values()->all();
    }

    /**
     * Copy screenshot files into $dir so the copy doesn't depend on the source
     * (deleting a proposal or template must not break another one).
     *
     * @param  array<int, array{path: string, caption?: string}>  $shots
     */
    public static function copyScreenshots(array $shots, string $dir): array
    {
        $disk = Storage::disk('public');

        return collect($shots)->map(function ($shot) use ($disk, $dir) {
            $path = $shot['path'] ?? '';
            if ($path === '' || ! $disk->exists($path)) {
                return null;
            }
            if (! str_starts_with($path, rtrim($dir, '/').'/')) {
                $new = rtrim($dir, '/').'/'.Str::random(8).'-'.basename($path);
                $disk->copy($path, $new);
                $path = $new;
            }

            return ['path' => $path, 'caption' => (string) ($shot['caption'] ?? '')];
        })->filter()->values()->all();
    }
}
