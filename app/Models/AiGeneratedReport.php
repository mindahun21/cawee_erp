<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AiGeneratedReport extends Model
{
    protected $fillable = [
        'user_id',
        'conversation_id',
        'title',
        'prompt',
        'report_json',
        'module_context',
        'is_saved',
    ];

    protected $casts = [
        'report_json' => 'array',
        'is_saved' => 'boolean',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
