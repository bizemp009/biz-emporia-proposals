<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;

/** Simple key/value store for global agency branding. */
class Setting extends Model
{
    protected $primaryKey = 'key';
    public $incrementing = false;
    protected $keyType = 'string';
    protected $fillable = ['key', 'value'];

    public const DEFAULTS = [
        'agency_name' => 'Biz Emporia',
        'agency_tagline' => 'Strategic Digital Growth Partner',
        'agency_email' => 'hello@bizemporia.com',
        'agency_website' => 'https://bizemporia.com',
        'brand_primary' => '#1a1a2e',
        'brand_accent' => '#7C5CFF',
        'brand_highlight' => '#E6499B',
        'default_currency' => 'INR',
        'default_booking_url' => '',
        'slug_prefix' => 'biz-emporia',
        'default_validity_days' => '30',
        'terms' => "50% of one-time fees due at kickoff, remainder on delivery. Monthly retainers are invoiced in advance on the 1st of each month. Either party may terminate with 30 days' written notice after the initial term.",
    ];

    public static function allValues(): array
    {
        return Cache::rememberForever('settings.all', function () {
            return array_merge(self::DEFAULTS, static::query()->pluck('value', 'key')->all());
        });
    }

    public static function get(string $key, mixed $default = null): mixed
    {
        return self::allValues()[$key] ?? $default;
    }

    public static function put(array $values): void
    {
        foreach ($values as $key => $value) {
            static::updateOrCreate(['key' => $key], ['value' => $value]);
        }
        Cache::forget('settings.all');
    }
}
