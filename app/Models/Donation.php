<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Donation extends Model
{
    use HasFactory;

    protected $fillable = [
        'donor_id',
        'campaign_id',
        'donation_type_id',
        'amount',
        'currency_id',
        'donation_date',
        'is_recurring',
        'pledge_amount',
        'in_kind_description',
        'payment_method',
        'transaction_id',
        'receipt_number',
        'pledge_id',
        'notes',
        'status',
        'exchange_rate',
        'base_amount',
        'is_tax_deductible',
        'is_gift_aid_eligible',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'exchange_rate' => 'decimal:6',
        'base_amount' => 'decimal:2',
        'pledge_amount' => 'decimal:2',
        'donation_date' => 'date',
        'is_recurring' => 'boolean',
        'is_tax_deductible' => 'boolean',
        'is_gift_aid_eligible' => 'boolean',
    ];

    public function donor(): BelongsTo
    {
        return $this->belongsTo(Donor::class);
    }

    public function campaign(): BelongsTo
    {
        return $this->belongsTo(Campaign::class);
    }

    public function donationType(): BelongsTo
    {
        return $this->belongsTo(DonationType::class);
    }

    public function currency(): BelongsTo
    {
        return $this->belongsTo(Currency::class);
    }

    public function pledge(): BelongsTo
    {
        return $this->belongsTo(Pledge::class);
    }

    // Query Scopes
    public function scopeRecurring($query)
    {
        return $query->where('is_recurring', true);
    }

    public function scopeByDateRange($query, $startDate, $endDate)
    {
        return $query->whereBetween('donation_date', [$startDate, $endDate]);
    }

    public function scopeByCampaign($query, $campaignId)
    {
        return $query->where('campaign_id', $campaignId);
    }

    public function scopeByDonor($query, $donorId)
    {
        return $query->where('donor_id', $donorId);
    }

    public function scopeByStatus($query, $status)
    {
        return $query->where('status', $status);
    }

    public function scopeCompleted($query)
    {
        return $query->where('status', 'completed');
    }

    // Helper Methods
    public static function generateReceiptNumber(): string
    {
        $year = date('Y');
        $month = date('m');
        $lastReceipt = static::whereYear('created_at', $year)
            ->whereMonth('created_at', $month)
            ->orderBy('id', 'desc')
            ->first();

        $sequence = $lastReceipt ? (int) substr($lastReceipt->receipt_number, -4) + 1 : 1;
        
        return sprintf('RCPT-%s%s-%04d', $year, $month, $sequence);
    }

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($donation) {
            if (empty($donation->receipt_number)) {
                $donation->receipt_number = static::generateReceiptNumber();
            }
            if (empty($donation->status)) {
                $donation->status = 'completed';
            }
            if ($donation->exchange_rate === null) {
                $donation->exchange_rate = 1.000000;
            }
            if ($donation->base_amount === null && $donation->amount !== null) {
                $donation->base_amount = $donation->amount * $donation->exchange_rate;
            }
        });
        
        static::updating(function ($donation) {
            if ($donation->isDirty(['amount', 'exchange_rate'])) {
                $donation->base_amount = $donation->amount * ($donation->exchange_rate ?? 1);
            }
        });
    }
}
