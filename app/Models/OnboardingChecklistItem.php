<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class OnboardingChecklistItem extends Model
{
    protected $fillable = [
        'title', 'phase', 'category', 'document_template',
        'requires_signature', 'is_active', 'sort_order',
    ];

    public function employeeOnboardings(): HasMany
    {
        return $this->hasMany(EmployeeOnboarding::class, 'checklist_item_id');
    }
}
