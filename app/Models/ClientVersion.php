<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;

class ClientVersion extends Model
{
    protected $fillable = [
        'version',
        'platform',
        'filename',
        'file_path',
        'file_size',
        'checksum_sha256',
        'release_notes',
        'min_gpu_memory',
        'min_ram',
        'is_latest',
        'is_active',
        'download_count',
    ];

    protected $casts = [
        'is_latest' => 'boolean',
        'is_active' => 'boolean',
        'file_size' => 'integer',
        'download_count' => 'integer',
    ];

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeLatest($query)
    {
        return $query->where('is_latest', true);
    }

    public function scopePlatform($query, string $platform)
    {
        return $query->where('platform', $platform);
    }

    public static function getLatestForPlatform(string $platform): ?self
    {
        return static::active()
            ->platform($platform)
            ->latest()
            ->first();
    }

    public static function setAsLatest(self $version): void
    {
        // Remove latest flag from other versions of same platform
        static::where('platform', $version->platform)
            ->where('id', '!=', $version->id)
            ->update(['is_latest' => false]);

        $version->update(['is_latest' => true]);
    }

    public function incrementDownloads(): void
    {
        $this->increment('download_count');
    }

    public function getFormattedFileSizeAttribute(): string
    {
        $bytes = $this->file_size;

        if ($bytes >= 1073741824) {
            return number_format($bytes / 1073741824, 2) . ' GB';
        } elseif ($bytes >= 1048576) {
            return number_format($bytes / 1048576, 2) . ' MB';
        } elseif ($bytes >= 1024) {
            return number_format($bytes / 1024, 2) . ' KB';
        }

        return $bytes . ' bytes';
    }

    public function getDownloadUrlAttribute(): string
    {
        return route('client.download.file', [
            'platform' => $this->platform,
            'version' => $this->version,
        ]);
    }

    public function fileExists(): bool
    {
        return Storage::disk('public')->exists($this->file_path);
    }
}
