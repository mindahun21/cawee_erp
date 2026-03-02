<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class SalaryGrade extends Model
{
    protected $fillable = [
        'grade', 'step', 'basic_salary', 'transport_allowance',
        'house_allowance', 'communications_allowance',
        'effective_from', 'effective_to', 'is_active',
    ];

    protected function casts(): array
    {
        return [
            'effective_from' => 'date',
            'effective_to'   => 'date',
        ];
    }

    public function employees(): HasMany
    {
        return $this->hasMany(Employee::class, 'salary_grade_id');
    }

    public function getLabelAttribute(): string
    {
        return "Grade {$this->grade} - Step {$this->step}";
    }
}
