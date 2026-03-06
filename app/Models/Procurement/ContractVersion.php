<?php

namespace App\Models\Procurement;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ContractVersion extends Model
{
    protected $table = 'procurement_contract_versions';

    protected $fillable = [
        'contract_id', 'version_number', 'change_summary',
        'amended_value', 'amendment_date', 'amended_by', 'document',
    ];

    protected function casts(): array
    {
        return [
            'amendment_date' => 'date',
            'amended_value'  => 'decimal:2',
        ];
    }

    // ── Relationships ───────────────────────────────────────────────
    public function contract(): BelongsTo { return $this->belongsTo(Contract::class); }
    public function amendedBy(): BelongsTo { return $this->belongsTo(User::class, 'amended_by'); }
}
