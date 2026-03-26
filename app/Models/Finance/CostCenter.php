<?php

namespace App\Models\Finance;

use App\Models\Donor;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class CostCenter extends Model
{
    use SoftDeletes;

    protected $table = 'finance_cost_centers';

    protected $fillable = [
        'code',
        'name',
        'type',
        'parent_id',
        'hr_project_id',
        'donor_id',
        'description',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
        ];
    }

    // ── Type helpers ──────────────────────────────────────────────────

    public static function types(): array
    {
        return [
            'head_office'     => 'Head Office',
            'regional_office' => 'Regional Office',
            'project'         => 'Project',
            'donor_restricted'=> 'Donor-Restricted',
            'shared_services' => 'Shared Services',
        ];
    }

    public static function activeOptions(): array
    {
        return static::where('is_active', true)
            ->orderBy('code')
            ->get()
            ->mapWithKeys(fn ($cc) => [$cc->id => "[{$cc->code}] {$cc->name}"])
            ->toArray();
    }

    // ── Relationships ─────────────────────────────────────────────────

    public function parent(): BelongsTo
    {
        return $this->belongsTo(CostCenter::class, 'parent_id');
    }

    public function children(): HasMany
    {
        return $this->hasMany(CostCenter::class, 'parent_id');
    }

    public function hrProject(): BelongsTo
    {
        return $this->belongsTo(\App\Models\HrBranch::class, 'hr_project_id');
    }

    public function donor(): BelongsTo
    {
        return $this->belongsTo(Donor::class);
    }
}
