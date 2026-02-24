<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class EmployeeOnboarding extends Model
{
    protected $fillable = [
        'employee_id', 'checklist_item_id', 'phase', 'completed',
        'completed_at', 'signed_document', 'verified_by',
    ];

    protected function casts(): array
    {
        return [
            'completed'    => 'boolean',
            'completed_at' => 'date',
        ];
    }

    public function employee(): BelongsTo { return $this->belongsTo(Employee::class); }
    public function checklistItem(): BelongsTo { return $this->belongsTo(OnboardingChecklistItem::class, 'checklist_item_id'); }
    public function verifier(): BelongsTo { return $this->belongsTo(Employee::class, 'verified_by'); }
}
