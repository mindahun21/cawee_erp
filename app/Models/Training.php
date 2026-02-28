<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Training extends Model
{
    protected $table = 'hr_trainings';

    protected $fillable = [
        'employee_id', 'training_type_id', 'title', 'institution',
        'start_date', 'end_date', 'duration_days', 'cost', 'certificate_path', 'notes',
    ];

    protected function casts(): array
    {
        return [
            'start_date' => 'date',
            'end_date'   => 'date',
            'cost'       => 'decimal:2',
        ];
    }

    public function employee(): BelongsTo     { return $this->belongsTo(Employee::class); }
    public function trainingType(): BelongsTo { return $this->belongsTo(TrainingType::class); }
}
