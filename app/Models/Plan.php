<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Models\Procurement\ProcurementBudget;

class Plan extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'title',
        'description',
        'objectives',
        'outcomes',
        'type',
        'parent_id',
        'department_id',
        'project_id',
        'budget_id',
        'start_date',
        'end_date',
        'attachments',
        'progress_percentage',
        'status',
    ];

    protected $casts = [
        'start_date' => 'date',
        'end_date' => 'date',
        'attachments' => 'array',
        'progress_percentage' => 'integer',
    ];

    public function parent(): BelongsTo
    {
        return $this->belongsTo(Plan::class, 'parent_id');
    }

    public function children(): HasMany
    {
        return $this->hasMany(Plan::class, 'parent_id');
    }

    public function department(): BelongsTo
    {
        return $this->belongsTo(Department::class);
    }

    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    public function budget(): BelongsTo
    {
        return $this->belongsTo(ProcurementBudget::class, 'budget_id');
    }

    public function tasks(): HasMany
    {
        return $this->hasMany(Task::class);
    }

    public function kpis(): HasMany
    {
        return $this->hasMany(PlanningKpi::class);
    }

    public function resourceAllocations(): HasMany
    {
        return $this->hasMany(PlanResourceAllocation::class);
    }
}
