<?php

namespace App\Models\Finance;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ReferencePadBook extends Model
{
    protected $table = 'finance_reference_pad_books';

    protected $fillable = [
        'pad_number', 'book_type', 'prefix', 'start_sequence',
        'end_sequence', 'current_sequence', 'is_active', 'assigned_to'
    ];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
        ];
    }

    public function assignedTo(): BelongsTo { return $this->belongsTo(User::class, 'assigned_to'); }
}
