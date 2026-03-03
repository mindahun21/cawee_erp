<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Dependent extends Model
{
    protected $table = 'hr_dependents';

    protected $fillable = [
        'employee_id', 'full_name', 'relationship',
        'date_of_birth', 'national_id', 'phone_number', 'is_beneficiary',
    ];

    protected function casts(): array
    {
        return [
            'date_of_birth'  => 'date',
            'is_beneficiary' => 'boolean',
        ];
    }

    public function employee(): BelongsTo { return $this->belongsTo(Employee::class); }
}
