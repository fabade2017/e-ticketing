<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

class BrandingLogo extends Model
{
    protected $fillable = ['filename', 'mime', 'data'];

    public static function current(): ?self
    {
        try {
            return static::query()->latest('id')->first();
        } catch (\Throwable $e) {
            return null;
        }
    }

    /**
     * Public URL for the logo. Prefers the DB-stored copy (survives redeploys on
     * ephemeral disks such as Render), falling back to a legacy file on the
     * public disk.
     */
    public static function url(): ?string
    {
        if ($logo = static::current()) {
            return url('/branding/logo').'?v='.$logo->cacheKey();
        }

        $path = SystemSetting::getValue('app_logo');
        if ($path) {
            try {
                if (Storage::disk('public')->exists($path)) {
                    return Storage::disk('public')->url($path);
                }
            } catch (\Throwable $e) {
                // ignore
            }
        }

        return null;
    }

    /**
     * Human readable name/path shown in the admin "Current Stored" panel.
     */
    public static function displayName(): ?string
    {
        if ($logo = static::current()) {
            return $logo->filename ?: 'branding/logo';
        }

        return SystemSetting::getValue('app_logo') ?: null;
    }

    public static function store(UploadedFile $file): self
    {
        static::query()->delete();

        return static::create([
            'filename' => $file->getClientOriginalName(),
            'mime' => $file->getMimeType() ?: 'image/png',
            'data' => base64_encode((string) file_get_contents($file->getRealPath())),
        ]);
    }

    public static function forget(): void
    {
        try {
            static::query()->delete();
        } catch (\Throwable $e) {
            // ignore
        }
    }

    public function contents(): string
    {
        return base64_decode((string) $this->data, true) ?: '';
    }

    public function cacheKey(): string
    {
        return (string) ($this->updated_at?->timestamp ?? $this->id ?? 1);
    }
}
