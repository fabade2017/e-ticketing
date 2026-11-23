<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Once;

class SystemSetting extends Model
{
    protected $fillable = ['key', 'value', 'description'];

    public static function getValue(string $key, ?string $default = null): ?string
    {
        return static::values([$key], [$key => $default])[$key];
    }

    /**
     * @param  array<int, string>  $keys
     * @param  array<string, string|null>  $defaults
     * @return array<string, string|null>
     */
    public static function values(array $keys, array $defaults = []): array
    {
        $keys = array_values(array_unique($keys));

        return once(function () use ($keys, $defaults): array {
            try {
                $records = static::whereIn('key', $keys)->pluck('value', 'key');
            } catch (\Throwable $e) {
                $records = collect();
            }

            return collect($keys)
                ->mapWithKeys(fn (string $key): array => [$key => $records[$key] ?? ($defaults[$key] ?? null)])
                ->all();
        });
    }

    public static function setValue(string $key, string $value): static
    {
        $setting = static::updateOrCreate(['key' => $key], ['value' => $value]);
        Once::flush();

        return $setting;
    }
}
