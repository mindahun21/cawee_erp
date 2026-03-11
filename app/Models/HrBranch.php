<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class HrBranch extends Model
{
    use SoftDeletes;

    protected $table = 'hr_branches';

    protected $fillable = [
        'branch_name',
        'branch_code',
        'location_id',
        'branch_type_option_id',
        'proposed_office',
        'address',
        'status',
        'notes',
    ];

    public function location(): BelongsTo
    {
        return $this->belongsTo(Location::class, 'location_id');
    }

    public function branchType(): BelongsTo
    {
        return $this->belongsTo(HrSettingOption::class, 'branch_type_option_id');
    }

    public function agreements(): HasMany
    {
        return $this->hasMany(OfficeRentAgreement::class, 'branch_id');
    }

    public function utilities(): HasMany
    {
        return $this->hasMany(BranchUtility::class, 'branch_id');
    }
}

