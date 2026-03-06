<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class EmployeeContract extends Model
{
    protected $table = 'hr_contracts';

    protected $fillable = [
        'employee_id', 'contract_type_id', 'contract_number',
        'start_date', 'end_date', 'salary', 'status', 'notes', 'file_path',
    ];

    protected function casts(): array
    {
        return [
            'start_date' => 'date',
            'end_date'   => 'date',
            'salary'     => 'decimal:2',
        ];
    }

    public function employee(): BelongsTo   { return $this->belongsTo(Employee::class); }
    public function contractType(): BelongsTo { return $this->belongsTo(ContractType::class); }

    public function getIsActiveAttribute(): bool
    {
        return $this->status === 'Active'
            && ($this->end_date === null || $this->end_date->isFuture());
    }
}
