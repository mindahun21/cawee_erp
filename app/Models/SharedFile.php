<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class SharedFile extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'uuid',
        'folder_id',
        'display_name',
        'original_name',
        'disk',
        'path',
        'extension',
        'mime_type',
        'size_bytes',
        'checksum',
        'version_no',
        'visibility',
        'is_locked',
        'uploaded_by',
        'expires_at',
        'context_type',
        'context_id',
    ];

    protected function casts(): array
    {
        return [
            'expires_at' => 'datetime',
            'is_locked' => 'boolean',
        ];
    }

    protected static function booted(): void
    {
        static::creating(function (self $file): void {
            $file->uuid ??= (string) Str::uuid();
            $file->version_no = max(1, (int) ($file->version_no ?: 1));
            $file->populateDerivedAttributes();
        });

        static::updating(function (self $file): void {
            if ($file->isDirty(['path', 'disk'])) {
                $file->version_no = ((int) $file->getOriginal('version_no')) + 1;
            }

            $file->populateDerivedAttributes();
        });

        static::created(function (self $file): void {
            $file->createVersionSnapshot();
        });

        static::updated(function (self $file): void {
            if ($file->wasChanged(['path', 'disk'])) {
                $file->createVersionSnapshot();
            }
        });
    }

    public function folder(): BelongsTo
    {
        return $this->belongsTo(SharedFolder::class, 'folder_id');
    }

    public function uploader(): BelongsTo
    {
        return $this->belongsTo(User::class, 'uploaded_by');
    }

    public function versions(): HasMany
    {
        return $this->hasMany(SharedFileVersion::class, 'shared_file_id');
    }

    public function shares(): HasMany
    {
        return $this->hasMany(FileShare::class, 'shared_file_id');
    }

    public function accessLogs(): HasMany
    {
        return $this->hasMany(FileAccessLog::class, 'shared_file_id');
    }

    public function context(): MorphTo
    {
        return $this->morphTo();
    }

    public function activeShares(): HasMany
    {
        return $this->shares()->where('is_active', true);
    }

    public function getHumanSizeAttribute(): string
    {
        $bytes = (int) $this->size_bytes;
        $units = ['B', 'KB', 'MB', 'GB', 'TB'];
        $index = 0;

        while ($bytes >= 1024 && $index < count($units) - 1) {
            $bytes /= 1024;
            $index++;
        }

        return number_format($bytes, $index === 0 ? 0 : 2).' '.$units[$index];
    }

    protected function populateDerivedAttributes(): void
    {
        $disk = $this->disk ?: config('filesystems.default', 'local');
        $path = $this->path;

        if (! $path || ! Storage::disk($disk)->exists($path)) {
            return;
        }

        $this->mime_type = Storage::disk($disk)->mimeType($path) ?: $this->mime_type;
        $this->size_bytes = (int) Storage::disk($disk)->size($path);
        $this->checksum = hash_file('sha256', Storage::disk($disk)->path($path)) ?: $this->checksum;
        $this->extension = pathinfo($this->original_name ?: $path, PATHINFO_EXTENSION) ?: $this->extension;
        $this->display_name = $this->display_name ?: pathinfo($this->original_name ?: $path, PATHINFO_FILENAME);
    }

    protected function createVersionSnapshot(): void
    {
        if (! $this->path) {
            return;
        }

        $this->versions()->create([
            'version_no' => $this->version_no,
            'disk' => $this->disk,
            'path' => $this->path,
            'original_name' => $this->original_name,
            'extension' => $this->extension,
            'mime_type' => $this->mime_type,
            'size_bytes' => $this->size_bytes,
            'checksum' => $this->checksum,
            'uploaded_by' => $this->uploaded_by,
        ]);
    }
}
