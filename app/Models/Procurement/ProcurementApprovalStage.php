<?php

namespace App\Models\Procurement;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ProcurementApprovalStage extends Model
{
    protected $fillable = [
        'workflow_id',
        'stage_order',
        'stage_name',
        'required_role',
        'can_reject',
    ];

    protected function casts(): array
    {
        return ['can_reject' => 'boolean'];
    }

    public function workflow(): BelongsTo
    {
        return $this->belongsTo(ProcurementApprovalWorkflow::class, 'workflow_id');
    }

    public function records(): HasMany
    {
        return $this->hasMany(ProcurementApprovalRecord::class, 'stage_id');
    }
}
