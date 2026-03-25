<?php

declare(strict_types=1);

namespace App\Models\ME;

use App\Models\ME\Concerns\LogsMeAudit;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;

class MeHousehold extends Model
{
    use HasFactory;
    use LogsMeAudit;
    use SoftDeletes;

    protected $table = 'me_households';

    protected $fillable = [
        'household_code',
        'project_id',
        'head_of_household',
        'family_size',
        'vulnerability_status',
        'income_level',
        'address',
        'kebele',
        'woreda',
        'zone',
        'region',
        'notes',
    ];

    protected $casts = [
        'family_size' => 'integer',
        'project_id'  => 'integer',
    ];

    // ── Relationships ──────────────────────────────────────────────────────────

    public function project(): BelongsTo
    {
        return $this->belongsTo(MeProject::class, 'project_id');
    }

    public function beneficiaries(): HasMany
    {
        return $this->hasMany(MeBeneficiary::class, 'household_id');
    }

    // ── Helpers ────────────────────────────────────────────────────────────────

    public function getVulnerabilityColorAttribute(): string
    {
        return match ($this->vulnerability_status) {
            'critical' => 'danger',
            'high'     => 'warning',
            'medium'   => 'info',
            default    => 'success',
        };
    }

    public function getFullLocationAttribute(): string
    {
        return implode(', ', array_filter([
            $this->kebele,
            $this->woreda,
            $this->zone,
            $this->region,
        ]));
    }
}
