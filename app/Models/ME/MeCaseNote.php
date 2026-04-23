<?php

declare(strict_types=1);

namespace App\Models\ME;

use App\Models\ME\Concerns\LogsMeAudit;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MeCaseNote extends Model
{
    use HasFactory;
    use LogsMeAudit;

    protected $table = 'me_case_notes';

    protected $fillable = [
        'beneficiary_id',
        'project_id',
        'authored_by',
        'note_type',
        'content',
        'follow_up_date',
        'is_confidential',
    ];

    protected $casts = [
        'follow_up_date'  => 'date',
        'is_confidential' => 'boolean',
        'beneficiary_id'  => 'integer',
        'project_id'      => 'integer',
        'authored_by'     => 'integer',
    ];

    public function beneficiary(): BelongsTo
    {
        return $this->belongsTo(MeBeneficiary::class, 'beneficiary_id');
    }

    public function project(): BelongsTo
    {
        return $this->belongsTo(MeProject::class, 'project_id');
    }

    public function author(): BelongsTo
    {
        return $this->belongsTo(User::class, 'authored_by');
    }

    public function getNoteTypeColorAttribute(): string
    {
        return match ($this->note_type) {
            'incident'   => 'danger',
            'counseling' => 'warning',
            'follow_up'  => 'info',
            'home_visit' => 'success',
            default      => 'gray',
        };
    }

    public function isOverdue(): bool
    {
        return $this->follow_up_date !== null && $this->follow_up_date->isPast();
    }
}
