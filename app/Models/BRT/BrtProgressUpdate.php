<?php

declare(strict_types=1);

namespace App\Models\BRT;

use App\Models\ME\MeBeneficiary;
use App\Models\ME\MeProject;
use App\Models\User;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class BrtProgressUpdate extends Model
{
    use HasFactory;

    protected $table = 'brt_progress_updates';

    protected $fillable = [
        'beneficiary_id',
        'project_id',
        'authored_by',
        'update_date',
        'update_type',
        'overall_progress',
        'summary',
        'challenges',
        'recommendations',
        'next_update_due',
        'high_risk_flag',
        'alert_status',
        'assigned_to',
        'resolved_at',
        'resolution_note',
    ];

    protected $casts = [
        'update_date'     => 'date',
        'next_update_due' => 'date',
        'resolved_at'     => 'datetime',
        'high_risk_flag'  => 'boolean',
        'beneficiary_id'  => 'integer',
        'project_id'      => 'integer',
        'authored_by'     => 'integer',
        'assigned_to'     => 'integer',
    ];

    protected static function booted(): void
    {
        static::saving(function (BrtProgressUpdate $progressUpdate): void {
            if ($progressUpdate->high_risk_flag && blank($progressUpdate->alert_status)) {
                $progressUpdate->alert_status = 'open';
            }

            if (! $progressUpdate->high_risk_flag) {
                $progressUpdate->alert_status = null;
                $progressUpdate->assigned_to = null;
                $progressUpdate->resolved_at = null;
                $progressUpdate->resolution_note = null;
            }
        });
    }

    // ── Relationships ──────────────────────────────────────────────────────────

    public function beneficiary(): BelongsTo
    {
        return $this->belongsTo(MeBeneficiary::class, 'beneficiary_id');
    }

    public function project(): BelongsTo
    {
        return $this->belongsTo(MeProject::class, 'project_id');
    }

    public function author(): BelongsTo
    {
        return $this->belongsTo(User::class, 'authored_by');
    }

    public function assignedTo(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_to');
    }

    // ── Helpers ───────────────────────────────────────────────────────────────

    public function getProgressColorAttribute(): string
    {
        return match ($this->overall_progress) {
            'improving' => 'success',
            'stable'    => 'info',
            'declining' => 'danger',
            default     => 'gray',
        };
    }

    public function isOverdue(): bool
    {
        return $this->next_update_due !== null && $this->next_update_due->isPast();
    }

    public function getAlertStatusColorAttribute(): string
    {
        return match ($this->alert_status) {
            'open'      => 'danger',
            'in_review' => 'warning',
            'escalated' => 'primary',
            'resolved'  => 'success',
            default     => 'gray',
        };
    }
}
