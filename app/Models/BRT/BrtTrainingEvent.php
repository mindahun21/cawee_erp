<?php

declare(strict_types=1);

namespace App\Models\BRT;

use App\Models\ME\MeProject;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class BrtTrainingEvent extends Model
{
    use HasFactory;
    use SoftDeletes;

    protected $table = 'brt_training_events';

    protected $fillable = [
        'title',
        'event_code',
        'project_id',
        'event_type',
        'event_date',
        'start_time',
        'end_time',
        'venue',
        'facilitator',
        'objectives',
        'topics_covered',
        'notes',
        'created_by',
    ];

    protected $casts = [
        'event_date' => 'date',
    ];

    // ── Boot: auto-generate event_code ────────────────────────────────────────

    protected static function booted(): void
    {
        static::creating(function (BrtTrainingEvent $event): void {
            if (empty($event->event_code)) {
                $year = now()->format('Y');
                $last = static::withTrashed()
                    ->where('event_code', 'like', "EVT-{$year}-%")
                    ->orderByDesc('id')
                    ->value('event_code');

                $next = $last ? ((int) substr($last, -4)) + 1 : 1;
                $event->event_code = 'EVT-' . $year . '-' . str_pad((string) $next, 4, '0', STR_PAD_LEFT);
            }
        });
    }

    // ── Relationships ──────────────────────────────────────────────────────────

    public function project(): BelongsTo
    {
        return $this->belongsTo(MeProject::class, 'project_id');
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function attendances(): HasMany
    {
        return $this->hasMany(BrtAttendance::class, 'event_id');
    }

    // ── Helpers ───────────────────────────────────────────────────────────────

    public function getEventTypeColorAttribute(): string
    {
        return match ($this->event_type) {
            'training'           => 'primary',
            'workshop'           => 'info',
            'community_meeting'  => 'success',
            'awareness_campaign' => 'warning',
            'support_group'      => 'pink',
            'iga_session'        => 'amber',
            default              => 'gray',
        };
    }

    public function getAttendanceRateAttribute(): ?float
    {
        $total = $this->attendances()->count();
        if ($total === 0) {
            return null;
        }
        $present = $this->attendances()->where('attendance_status', 'present')->count();

        return round(($present / $total) * 100, 1);
    }
}
