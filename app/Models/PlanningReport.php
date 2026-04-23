<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PlanningReport extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'title', 'type', 'start_date', 'end_date', 'department_id',
        'parameters', 'file_path', 'status', 'generated_by_id'
    ];

    protected $casts = [
        'parameters' => 'array',
        'start_date' => 'date',
        'end_date' => 'date',
    ];

    public function department(): BelongsTo
    {
        return $this->belongsTo(Department::class);
    }

    public function generatedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'generated_by_id');
    }
}
