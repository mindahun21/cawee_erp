<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AssetAssignment extends Model
{
    protected $fillable = [
        'asset_id',
        'employee_id',
        'department_id',
        'project_id',
        'location_id',
        'assigned_date',
        'due_date',
        'returned_date',
        'condition_on_assignment',
        'condition_on_return',
        'remarks',
        'status',
        'expected_return_date',
    ];

    protected $casts = [
        'assigned_date' => 'date',
        'due_date' => 'date',
        'returned_date' => 'date',
        'expected_return_date' => 'date',
    ];

    public function asset()
    {
        return $this->belongsTo(Asset::class);
    }

    public function employee()
    {
        return $this->belongsTo(Employee::class);
    }

    public function department()
    {
        return $this->belongsTo(Department::class);
    }

    public function project()
    {
        return $this->belongsTo(Project::class);
    }

    public function location()
    {
        return $this->belongsTo(Location::class);
    }
}
