<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class FileShare extends Model
{
    use HasFactory;

    protected $fillable = [
        'shared_file_id',
        'shared_folder_id',
        'share_type',
        'access_level',
        'shared_with_user_id',
        'shared_with_email',
        'share_token',
        'password',
        'max_downloads',
        'download_count',
        'expires_at',
        'is_active',
        'created_by',
    ];

    protected function casts(): array
    {
        return [
            'expires_at' => 'datetime',
            'is_active' => 'boolean',
        ];
    }

    protected static function booted(): void
    {
        static::creating(function (self $share): void {
            if (! $share->share_token) {
                $share->share_token = Str::random(40);
            }

            if ($share->password && ! Str::startsWith($share->password, '$2y$')) {
                $share->password = Hash::make($share->password);
            }
        });

        static::updating(function (self $share): void {
            if ($share->isDirty('password') && filled($share->password) && ! Str::startsWith($share->password, '$2y$')) {
                $share->password = Hash::make($share->password);
            }
        });
    }

    public function file(): BelongsTo
    {
        return $this->belongsTo(SharedFile::class, 'shared_file_id');
    }

    public function folder(): BelongsTo
    {
        return $this->belongsTo(SharedFolder::class, 'shared_folder_id');
    }

    public function recipient(): BelongsTo
    {
        return $this->belongsTo(User::class, 'shared_with_user_id');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function accessLogs(): HasMany
    {
        return $this->hasMany(FileAccessLog::class, 'file_share_id');
    }

    public function getShareUrlAttribute(): ?string
    {
        if (! $this->share_token) {
            return null;
        }

        return route('file-shares.show', $this->share_token);
    }
}
