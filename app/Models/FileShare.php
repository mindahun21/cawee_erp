<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

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

    public function accessLogs()
    {
        return $this->hasMany(FileAccessLog::class, 'file_share_id');
    }
}
