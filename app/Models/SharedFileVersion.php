<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SharedFileVersion extends Model
{
    use HasFactory;

    protected $fillable = [
        'shared_file_id',
        'version_no',
        'disk',
        'path',
        'original_name',
        'extension',
        'mime_type',
        'size_bytes',
        'checksum',
        'change_note',
        'uploaded_by',
    ];

    public function file(): BelongsTo
    {
        return $this->belongsTo(SharedFile::class, 'shared_file_id');
    }

    public function uploader(): BelongsTo
    {
        return $this->belongsTo(User::class, 'uploaded_by');
    }
}
