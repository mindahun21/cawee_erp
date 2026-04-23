<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Carbon\Carbon;

class Task extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'plan_id',
        'title',
        'description',
        'employee_id',
        'deadline',
        'priority',
        'status',
        'progress_percentage',
        'attachments',
    ];

    protected $casts = [
        'deadline' => 'date',
        'attachments' => 'array',
        'progress_percentage' => 'integer',
    ];

    public function plan(): BelongsTo
    {
        return $this->belongsTo(Plan::class);
    }

    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class);
    }

    // ── Status Helpers ──────
    public function getIsOverdueAttribute(): bool
    {
        return $this->status !== 'completed' 
            && $this->deadline 
            && Carbon::parse($this->deadline)->isPast();
    }

    public function getIsNearDeadlineAttribute(): bool
    {
        if (!$this->deadline || $this->status === 'completed') return false;
        
        $daysUntil = Carbon::now()->diffInDays(Carbon::parse($this->deadline), false);
        return $daysUntil >= 0 && $daysUntil <= 3;
    }

    public function getIsOnTrackAttribute(): bool
    {
        return !$this->is_overdue && !$this->is_near_deadline;
    }
}
