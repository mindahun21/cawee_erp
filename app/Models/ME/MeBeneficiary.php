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

class MeBeneficiary extends Model
{
    use HasFactory;
    use LogsMeAudit;
    use SoftDeletes;

    protected $table = 'me_beneficiaries';

    protected $fillable = [
        'beneficiary_code',
        'household_id',
        'full_name',
        'full_name_local',
        'date_of_birth',
        'age',
        'gender',
        'national_id',
        'phone',
        'address',
        'kebele',
        'woreda',
        'zone',
        'region',
        'photo_path',
        'disability_status',
        'status',
        'registered_at',
        'registered_by',
        'notes',
    ];

    protected $casts = [
        'date_of_birth' => 'date',
        'registered_at' => 'date',
        'age'           => 'integer',
        'household_id'  => 'integer',
        'registered_by' => 'integer',
    ];

    // ── Boot: auto-generate beneficiary code ──────────────────────────────────

    protected static function booted(): void
    {
        static::creating(function (MeBeneficiary $ben): void {
            if (empty($ben->beneficiary_code)) {
                $year = now()->format('Y');
                $last = static::withTrashed()
                    ->where('beneficiary_code', 'like', "BNF-{$year}-%")
                    ->orderByDesc('id')
                    ->value('beneficiary_code');

                $next = $last ? ((int) substr($last, -4)) + 1 : 1;
                $ben->beneficiary_code = 'BNF-' . $year . '-' . str_pad((string) $next, 4, '0', STR_PAD_LEFT);
            }

            // Auto-compute age from DOB if not set
            if (empty($ben->age) && $ben->date_of_birth) {
                $ben->age = (int) $ben->date_of_birth->age;
            }
        });
    }

    // ── Relationships ──────────────────────────────────────────────────────────

    public function household(): BelongsTo
    {
        return $this->belongsTo(MeHousehold::class, 'household_id');
    }

    public function registeredBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'registered_by');
    }

    public function enrollments(): HasMany
    {
        return $this->hasMany(MeBeneficiaryEnrollment::class, 'beneficiary_id');
    }

    public function baselineAssessments(): HasMany
    {
        return $this->hasMany(MeBaselineAssessment::class, 'beneficiary_id');
    }

    public function latestBaseline(): HasOne
    {
        return $this->hasOne(MeBaselineAssessment::class, 'beneficiary_id')->latestOfMany('assessment_date');
    }

    public function caseNotes(): HasMany
    {
        return $this->hasMany(MeCaseNote::class, 'beneficiary_id');
    }

    public function referrals(): HasMany
    {
        return $this->hasMany(MeReferral::class, 'beneficiary_id');
    }

    // ── Helpers ────────────────────────────────────────────────────────────────

    public function getStatusColorAttribute(): string
    {
        return match ($this->status) {
            'active'    => 'success',
            'graduated' => 'info',
            'inactive'  => 'warning',
            'suspended' => 'danger',
            'deceased'  => 'gray',
            default     => 'gray',
        };
    }

    public function getGenderColorAttribute(): string
    {
        return match ($this->gender) {
            'female' => 'pink',
            'male'   => 'blue',
            default  => 'gray',
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
