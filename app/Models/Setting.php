<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;

/**
 * Key/value store configured from the admin panel.
 *
 * @property string $key
 * @property string|null $value
 * @property string $group
 * @property string $type
 */
#[Fillable(['key', 'value', 'group', 'type'])]
class Setting extends Model
{
    public $incrementing = false;

    protected $primaryKey = 'key';

    protected $keyType = 'string';

    private const CACHE_KEY = 'settings.all';

    protected static function booted(): void
    {
        static::saved(fn () => Cache::forget(self::CACHE_KEY));
        static::deleted(fn () => Cache::forget(self::CACHE_KEY));
    }

    /**
     * All settings as a key => value map (cached).
     *
     * @return array<string, string|null>
     */
    public static function allCached(): array
    {
        return Cache::rememberForever(self::CACHE_KEY, fn () => static::query()->pluck('value', 'key')->all());
    }

    public static function get(string $key, ?string $default = null): ?string
    {
        $all = static::allCached();

        return array_key_exists($key, $all) && $all[$key] !== null ? $all[$key] : $default;
    }

    public static function set(string $key, ?string $value, ?string $group = null, ?string $type = null): void
    {
        $setting = static::query()->find($key) ?? new static(['key' => $key]);
        $setting->value = $value;

        if ($group !== null) {
            $setting->group = $group;
        }

        if ($type !== null) {
            $setting->type = $type;
        }

        $setting->save();
    }
}
