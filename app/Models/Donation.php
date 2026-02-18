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
        'notes',
        'status',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'pledge_amount' => 'decimal:2',
        'donation_date' => 'date',
        'is_recurring' => 'boolean',
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
        });
    }
}
