<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

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
            $share->ensureSingleTarget();
            $share->ensureRecipientRules();
            $share->ensurePolicyRules();

            if (! $share->expires_at) {
                $defaultExpiryDays = FileSharingSetting::defaultLinkExpiryDays();
                if ($defaultExpiryDays > 0) {
                    $share->expires_at = now()->addDays($defaultExpiryDays);
                }
            }

            if (! $share->share_token) {
                $share->share_token = Str::random(40);
            }

            if ($share->password && ! Str::startsWith($share->password, '$2y$')) {
                $share->password = Hash::make($share->password);
            }
        });

        static::updating(function (self $share): void {
            $share->ensureSingleTarget();
            $share->ensureRecipientRules();
            $share->ensurePolicyRules();

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

    protected function ensureSingleTarget(): void
    {
        $hasFile = filled($this->shared_file_id);
        $hasFolder = filled($this->shared_folder_id);

        if ($hasFile === $hasFolder) {
            throw ValidationException::withMessages([
                'shared_file_id' => 'A share must target exactly one item: either a file or a folder.',
                'shared_folder_id' => 'A share must target exactly one item: either a file or a folder.',
            ]);
        }
    }

    protected function ensureRecipientRules(): void
    {
        $type = $this->share_type;
        $hasUser = filled($this->shared_with_user_id);
        $hasEmail = filled($this->shared_with_email);

        if ($type === 'staff' && ! $hasUser) {
            throw ValidationException::withMessages([
                'shared_with_user_id' => 'Staff shares must target an internal user.',
            ]);
        }

        if ($type === 'staff' && $hasEmail) {
            throw ValidationException::withMessages([
                'shared_with_email' => 'Staff shares should not set an external email recipient.',
            ]);
        }

        if ($type === 'client' && ! $hasEmail) {
            throw ValidationException::withMessages([
                'shared_with_email' => 'Client shares must target a client email address.',
            ]);
        }

        if ($type === 'client' && $hasUser) {
            throw ValidationException::withMessages([
                'shared_with_user_id' => 'Client shares should not target an internal user record.',
            ]);
        }

        if ($type === 'public' && ($hasUser || $hasEmail)) {
            throw ValidationException::withMessages([
                'share_type' => 'Public shares should not target a specific user or email recipient.',
            ]);
        }
    }

    protected function ensurePolicyRules(): void
    {
        if ($this->share_type !== 'public') {
            return;
        }

        if (! FileSharingSetting::isPublicSharingEnabled()) {
            throw ValidationException::withMessages([
                'share_type' => 'Public sharing is currently disabled by system policy.',
            ]);
        }

        if (FileSharingSetting::requiresPublicPassword() && blank($this->password)) {
            throw ValidationException::withMessages([
                'password' => 'Password is required for public shares by policy.',
            ]);
        }
    }

    public function canBeAccessedBy(?User $user): bool
    {
        return match ($this->share_type) {
            'public' => true,
            'staff' => $this->canBeAccessedByStaff($user),
            'client' => $user !== null
                && filled($this->shared_with_email)
                && strcasecmp((string) $this->shared_with_email, (string) $user->email) === 0,
            default => false,
        };
    }

    public function canBeAccessedByStaff(?User $user): bool
    {
        return $user !== null && (int) $this->shared_with_user_id === (int) $user->getKey();
    }

    public function canBeAccessedByClientEmail(?string $email): bool
    {
        return filled($email)
            && filled($this->shared_with_email)
            && strcasecmp((string) $this->shared_with_email, (string) $email) === 0;
    }

    public function allowsPreview(): bool
    {
        return in_array($this->access_level, ['view', 'download', 'manage'], true);
    }

    public function allowsDownload(): bool
    {
        return in_array($this->access_level, ['download', 'manage'], true);
    }

    public function passwordSessionKey(): string
    {
        return 'file_share_access.'.$this->getKey();
    }

    public function isExpired(): bool
    {
        return (bool) $this->expires_at?->isPast();
    }
}
