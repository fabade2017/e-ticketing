<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SystemSetting extends Model
{
    protected $fillable = ['key', 'value', 'description'];

    public static function getValue(string $key, ?string $default = null): ?string
    {
        try {
            $record = static::where('key', $key)->first();
            return $record ? $record->value : $default;
        } catch (\Throwable $e) {
            return $default;
        }
    }

    public static function setValue(string $key, string $value): static
    {
        return static::updateOrCreate(['key' => $key], ['value' => $value]);
    }
}
