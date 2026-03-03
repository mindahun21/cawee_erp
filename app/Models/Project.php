<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Project extends Model
{
    protected $table = 'hr_projects';

    protected $fillable = [
        'project_name',
        'project_code',
        'location_id',
    ];

    public function location(): BelongsTo
    {
        return $this->belongsTo(Location::class, 'location_id');
    }

    public function employees(): HasMany
    {
        return $this->hasMany(Employee::class, 'project_id');
    }

    public function timeRecords(): HasMany
    {
        return $this->hasMany(TimeRecord::class, 'project_id');
    }

    public function performanceEvaluations(): HasMany
    {
        return $this->hasMany(PerformanceEvaluation::class, 'project_id');
    }
}
