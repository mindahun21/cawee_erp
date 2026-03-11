<?php

namespace App\Models\Procurement;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class ProcurementApprovalRecord extends Model
{
    protected $fillable = [
        'approvable_type',
        'approvable_id',
        'stage_id',
        'stage_order',
        'stage_name',
        'required_role',
        'status',
        'decided_by',
        'decided_at',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'decided_at' => 'datetime',
            'stage_order' => 'integer',
        ];
    }

    public function approvable(): MorphTo
    {
        return $this->morphTo();
    }

    public function stage(): BelongsTo
    {
        return $this->belongsTo(ProcurementApprovalStage::class, 'stage_id');
    }

    public function decidedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'decided_by');
    }
}
