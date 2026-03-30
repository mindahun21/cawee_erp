<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class FileAccessLog extends Model
{
    use HasFactory;

    protected $fillable = [
        'shared_file_id',
        'file_share_id',
        'user_id',
        'action',
        'ip_address',
        'user_agent',
        'notes',
        'context_type',
        'context_id',
        'accessed_at',
    ];

    protected function casts(): array
    {
        return [
            'accessed_at' => 'datetime',
        ];
    }

    public function file(): BelongsTo
    {
        return $this->belongsTo(SharedFile::class, 'shared_file_id');
    }

    public function share(): BelongsTo
    {
        return $this->belongsTo(FileShare::class, 'file_share_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function context(): MorphTo
    {
        return $this->morphTo();
    }
}
