<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Once;

class SystemSetting extends Model
{
    public const OPTION_LISTS = [
        'ticket_categories' => ['welfare', 'logistics', 'medical', 'equipment', 'operations', 'other'],
        'care_registration_categories' => ['military', 'civilian', 'dependant'],
        'care_registration_relationships' => ['sponsor', 'spouse', 'child'],
        'kb_categories' => ['general', 'logistics', 'medical', 'maintenance', 'hr'],
        'supply_units' => ['pcs', 'each', 'box', 'boxes', 'kg', 'litres', 'carton', 'cartons'],
        'user_ranks' => ['Pte', 'Lance Cpl', 'Cpl', 'Sgt', 'Sgt Maj', 'WO1', 'WO2', '2nd Lt', 'Lt', 'Capt', 'Maj', 'Lt Col', 'Col'],
    ];

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

    /**
     * @return array<string, array<int, string>>
     */
    public static function optionLists(): array
    {
        $keys = array_keys(static::OPTION_LISTS);
        $stored = static::values($keys);

        return collect(static::OPTION_LISTS)
            ->mapWithKeys(fn (array $defaults, string $key): array => [
                $key => static::parseOptions($stored[$key] ?? null) ?: $defaults,
            ])
            ->all();
    }

    /**
     * @return array<int, string>
     */
    public static function options(string $key): array
    {
        $defaults = static::OPTION_LISTS[$key] ?? [];

        return static::parseOptions(static::getValue($key)) ?: $defaults;
    }

    /**
     * @return array<int, string>
     */
    public static function parseOptions(?string $raw): array
    {
        if ($raw === null || trim($raw) === '') {
            return [];
        }

        return collect(preg_split('/[\r\n,]+/', $raw) ?: [])
            ->map(fn (string $value): string => trim($value))
            ->filter()
            ->unique()
            ->values()
            ->all();
    }

    public static function setValue(string $key, string $value): static
    {
        $setting = static::updateOrCreate(['key' => $key], ['value' => $value]);
        Once::flush();

        return $setting;
    }
}
