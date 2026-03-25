<?php

declare(strict_types=1);

namespace App\Models\ME;

use App\Models\ME\Concerns\LogsMeAudit;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MeBaselineAssessment extends Model
{
    use HasFactory;
    use LogsMeAudit;

    protected $table = 'me_baseline_assessments';

    protected $fillable = [
        'beneficiary_id',
        'project_id',
        'education_level',
        'health_status',
        'nutrition_status',
        'livelihood_info',
        'monthly_income',
        'assets',
        'shelter_condition',
        'water_sanitation',
        'assessment_date',
        'assessed_by',
        'notes',
    ];

    protected $casts = [
        'assessment_date' => 'date',
        'monthly_income'  => 'decimal:2',
        'beneficiary_id'  => 'integer',
        'project_id'      => 'integer',
        'assessed_by'     => 'integer',
    ];

    public function beneficiary(): BelongsTo
    {
        return $this->belongsTo(MeBeneficiary::class, 'beneficiary_id');
    }

    public function project(): BelongsTo
    {
        return $this->belongsTo(MeProject::class, 'project_id');
    }

    public function assessedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assessed_by');
    }

    public function getNutritionColorAttribute(): string
    {
        return match ($this->nutrition_status) {
            'severe_malnutrition'   => 'danger',
            'moderate_malnutrition' => 'warning',
            default                 => 'success',
        };
    }
}
