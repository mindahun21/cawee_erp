<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Asset extends Model
{
    protected $fillable = [
        'asset_category_id',
        'currency_id',
        'donor_id',
        'location_id',
        'department_id',
        'name',
        'model',
        'serial_number',
        'barcode',
        'acquisition_type',
        'purchase_cost',
        'purchase_date',
        'useful_life',
        'residual_value',
        'status',
        'condition',
        'description',
        'is_fixed_asset',
        'quantity',
        'min_stock_level',
        'qr_code',
        'rfid_tag',
        'warranty_expiry_date',
        'contract_details',
        'depreciation_method',
    ];

    protected $casts = [
        'purchase_date' => 'date',
        'purchase_cost' => 'decimal:2',
        'residual_value' => 'decimal:2',
        'is_fixed_asset' => 'boolean',
        'warranty_expiry_date' => 'date',
        'contract_details' => 'json',
    ];

    public function assetCategory()
    {
        return $this->belongsTo(AssetCategory::class);
    }


    public function location()
    {
        return $this->belongsTo(Location::class);
    }

    public function currency()
    {
        return $this->belongsTo(Currency::class);
    }

    public function donor()
    {
        return $this->belongsTo(Donor::class);
    }

    public function department()
    {
        return $this->belongsTo(Department::class);
    }

    public function assignments()
    {
        return $this->hasMany(AssetAssignment::class);
    }

    public function movements()
    {
        return $this->hasMany(InventoryMovement::class);
    }

    public function stocks()
    {
        return $this->hasMany(AssetStock::class);
    }

    public function vehicleDetail()
    {
        return $this->hasOne(VehicleDetail::class);
    }

    public function depreciationLogs()
    {
        return $this->hasMany(DepreciationLog::class);
    }

    /**
     * Calculate straight-line depreciation.
     * Annual = (Cost - Residual) / Useful Life
     */
    public function getAnnualDepreciationAttribute()
    {
        if (!$this->useful_life || $this->useful_life <= 0) {
            return 0;
        }

        return ($this->purchase_cost - $this->residual_value) / $this->useful_life;
    }

    public function getCurrentValueAttribute()
    {
        if (!$this->purchase_date || !$this->useful_life) {
            return $this->purchase_cost;
        }

        $yearsOwned = $this->purchase_date->diffInYears(now());
        
        if ($yearsOwned >= $this->useful_life) {
            return $this->residual_value;
        }

        return $this->purchase_cost - ($this->annual_depreciation * $yearsOwned);
    }

    public function getQuantityAttribute()
    {
        // For fixed assets, quantity is always strictly 1 (or 0 if disposed)
        // for inventory items, we now sum up the quantities mapped in the asset_stocks pivot table
        if ($this->is_fixed_asset) {
            // we check if it has a global quantity over 0 to determine if it exists, but max is 1 since fixed
            return ($this->attributes['quantity'] ?? 0) > 0 ? 1 : 0;
        }

        // If there are stock records, sum them up. Otherwise fallback to the master quantity
        // This is a graceful fallback for existing data before the pivot migration
        $stockSum = $this->exists ? $this->stocks()->sum('quantity') : 0;
        return $stockSum > 0 ? $stockSum : ($this->attributes['quantity'] ?? 0);
    }

    public function getIsLowStockAttribute()
    {
        if ($this->is_fixed_asset) return false;
        return $this->quantity <= $this->min_stock_level;
    }

    /**
     * Post depreciation for a specific period.
     */
    public function postDepreciation($periodDate = null)
    {
        $periodDate = $periodDate ? \Carbon\Carbon::parse($periodDate) : now();
        
        // Prevent double posting for the same month/year
        $exists = $this->depreciationLogs()
            ->whereMonth('period_date', $periodDate->month)
            ->whereYear('period_date', $periodDate->year)
            ->exists();

        if ($exists) {
            return null;
        }

        $depreciationAmount = $this->annual_depreciation / 12;
        $lastLog = $this->depreciationLogs()->orderBy('period_date', 'desc')->first();
        $currentBookValue = $lastLog ? $lastLog->book_value : $this->purchase_cost;
        
        $newBookValue = max($this->residual_value, $currentBookValue - $depreciationAmount);

        return $this->depreciationLogs()->create([
            'period_date' => $periodDate,
            'depreciation_amount' => $depreciationAmount,
            'book_value' => $newBookValue,
        ]);
    }
}
