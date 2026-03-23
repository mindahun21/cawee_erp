<?php

namespace App\Models\Recruitment;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Spatie\Permission\Models\Role;

class RecruitmentApprovalWorkflow extends Model
{
    protected $table = 'recruitment_approval_workflows';

    protected $fillable = [
        'document_type',
        'name',
        'description',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    public function stages(): HasMany
    {
        $relation = $this->hasMany(RecruitmentApprovalStage::class, 'workflow_id');
        $relation->orderBy('stage_order');
        
        return $relation;
    }

    public static function documentTypes(): array
    {
        return [
            'recruitment_plan' => 'Recruitment Plan',
        ];
    }

    public static function availableRoles(): array
    {
        return Role::pluck('name', 'name')->toArray();
    }

    public static function activeFor(string $documentType): ?self
    {
        return self::with('stages')
            ->where('document_type', $documentType)
            ->where('is_active', true)
            ->first();
    }
}
