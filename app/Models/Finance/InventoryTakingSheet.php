<?php

namespace App\Models\Finance;

use App\Models\Project;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class InventoryTakingSheet extends Model
{
    protected $table = 'finance_inventory_taking_sheets';

    protected $fillable = [
        'reference', 'taking_date', 'cost_center_id', 'project_id',
        'conducted_by', 'verified_by', 'status'
    ];

    protected function casts(): array
    {
        return [
            'taking_date' => 'date',
        ];
    }

    public static function statuses(): array
    {
        return [
            'draft' => 'Draft',
            'verified' => 'Verified',
            'submitted' => 'Submitted'
        ];
    }

    public function costCenter(): BelongsTo { return $this->belongsTo(CostCenter::class); }
    public function project(): BelongsTo { return $this->belongsTo(Project::class); }
    public function conductedBy(): BelongsTo { return $this->belongsTo(User::class, 'conducted_by'); }
    public function verifiedBy(): BelongsTo { return $this->belongsTo(User::class, 'verified_by'); }
    
    public function items(): HasMany { return $this->hasMany(InventoryTakingSheetItem::class); }
}
