<?php

declare(strict_types=1);

namespace App\Models\BRT;

use App\Models\ME\MeBeneficiary;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class BrtAttendance extends Model
{
    use HasFactory;

    protected $table = 'brt_attendance';

    protected $fillable = [
        'event_id',
        'beneficiary_id',
        'attendance_status',
        'remarks',
    ];

    protected $casts = [
        'event_id'       => 'integer',
        'beneficiary_id' => 'integer',
    ];

    // ── Relationships ──────────────────────────────────────────────────────────

    public function event(): BelongsTo
    {
        return $this->belongsTo(BrtTrainingEvent::class, 'event_id');
    }

    public function beneficiary(): BelongsTo
    {
        return $this->belongsTo(MeBeneficiary::class, 'beneficiary_id');
    }

    // ── Helpers ───────────────────────────────────────────────────────────────

    public function getStatusColorAttribute(): string
    {
        return match ($this->attendance_status) {
            'present' => 'success',
            'late'    => 'warning',
            'excused' => 'info',
            'absent'  => 'danger',
            default   => 'gray',
        };
    }
}
