<?php

namespace App\Models\Finance;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class AccountingPeriod extends Model
{
    protected $table = 'finance_accounting_periods';

    protected $fillable = [
        'name',
        'fiscal_year',
        'period_number',
        'start_date',
        'end_date',
        'status',
        'closed_by',
        'closed_at',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'fiscal_year'   => 'integer',
            'period_number' => 'integer',
            'start_date'    => 'date',
            'end_date'      => 'date',
            'closed_at'     => 'datetime',
        ];
    }

    // ── Status helpers ────────────────────────────────────────────────

    public static function statuses(): array
    {
        return [
            'open'   => 'Open',
            'closed' => 'Closed',
            'locked' => 'Locked',
        ];
    }

    public function isOpen(): bool
    {
        return $this->status === 'open';
    }

    public function isClosed(): bool
    {
        return in_array($this->status, ['closed', 'locked']);
    }

    /**
     * Returns the current open accounting period, or null if none.
     */
    public static function current(): ?self
    {
        return static::where('status', 'open')
            ->where('start_date', '<=', now())
            ->where('end_date', '>=', now())
            ->first();
    }

    /**
     * Returns [id => label] of all open periods for select dropdowns.
     */
    public static function openOptions(): array
    {
        return static::where('status', 'open')
            ->orderByDesc('start_date')
            ->get()
            ->pluck('name', 'id')
            ->toArray();
    }

    // ── Relationships ─────────────────────────────────────────────────

    public function closedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'closed_by');
    }

    public function journalEntries(): HasMany
    {
        return $this->hasMany(JournalEntry::class, 'accounting_period_id');
    }
}
