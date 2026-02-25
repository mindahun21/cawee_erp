<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ClearanceForm extends Model
{
    protected $fillable = [
        'employee_id', 'employment_start_date', 'employment_end_date', 'employment_type',
        'organizational_property', 'reason_of_exit', 'no_further_rights',
        'supervisor_signed_by', 'supervisor_signed_at',
        'committee_signed_by', 'committee_signed_at',
        'head_office_signed_by', 'head_office_signed_at',
        'hr_signed_by', 'hr_signed_at',
        'director_signed_by', 'director_signed_at',
    ];

    protected function casts(): array
    {
        return [
            'employment_start_date'   => 'date',
            'employment_end_date'     => 'date',
            'supervisor_signed_at'    => 'date',
            'committee_signed_at'     => 'date',
            'head_office_signed_at'   => 'date',
            'hr_signed_at'            => 'date',
            'director_signed_at'      => 'date',
            'no_further_rights'       => 'boolean',
        ];
    }

    public function employee(): BelongsTo { return $this->belongsTo(Employee::class); }
}
