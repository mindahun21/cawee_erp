<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class HrProcess extends Model
{
    protected $table = 'hr_processes';

    protected $fillable = [
        'employee_id',
        'process_type',
        'document_name',
        'document_signed',
        'completion_date',
        'remarks',
    ];

    protected $casts = [
        'document_signed' => 'boolean',
        'completion_date' => 'date',
    ];

    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class);
    }
}
