<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AssetAssignment extends Model
{
    protected $fillable = [
        'assignment_no',
        'asset_id',
        'employee_id',
        'department_id',
        'project_id',
        'location_id',
        'purpose',
        'quantity',
        'assigned_date',
        'due_date',
        'returned_date',
        'condition_on_assignment',
        'condition_on_return',
        'remarks',
        'attachments',
        'status',
        'expected_return_date',
    ];

    protected $casts = [
        'assigned_date' => 'date',
        'due_date' => 'date',
        'returned_date' => 'date',
        'expected_return_date' => 'date',
        'attachments' => 'json',
        'quantity' => 'integer',
    ];

    public static function generateAssignmentNo(): string
    {
        return \App\Models\PrefixSetting::generateNextCode('asset_assignment_no');
    }

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($model) {
            if (!$model->assignment_no) {
                $model->assignment_no = self::generateAssignmentNo();
            }
        });

        static::created(function ($model) {
            \App\Models\PrefixSetting::updateNextNumberFromCode('asset_assignment_no', $model->assignment_no);
        });
    }

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
