<?php

namespace App\Models\Finance;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PerdiemRequestExtension extends Model
{
    protected $table = 'finance_perdiem_request_extensions';

    protected $fillable = [
        'perdiem_request_id', 'extension_date', 'additional_days',
        'new_end_date', 'additional_amount', 'reason',
        'status', 'approved_by', 'approved_at',
    ];

    protected function casts(): array
    {
        return [
            'extension_date'    => 'date',
            'new_end_date'      => 'date',
            'approved_at'       => 'datetime',
            'additional_amount' => 'decimal:2',
        ];
    }

    public static function statuses(): array
    {
        return ['draft' => 'Draft', 'pending' => 'Pending', 'approved' => 'Approved', 'rejected' => 'Rejected'];
    }

    public function perdiemRequest(): BelongsTo { return $this->belongsTo(PerdiemRequest::class, 'perdiem_request_id'); }
    public function approvedBy(): BelongsTo     { return $this->belongsTo(User::class, 'approved_by'); }
}
