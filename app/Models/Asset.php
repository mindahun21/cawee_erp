<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Asset extends Model
{
    protected $fillable = [
        'asset_condition_id',
        'asset_status_id',
        'acquisition_type_id',
        'asset_category_id',
        'currency_id',
        'donor_id',
        'supplier_id',
        'location_id',
        'department_id',
        'depreciation_id',
        'name',
        'model',
        'serial_number',
        'barcode',
        'purchase_cost',
        'purchase_date',
        'description',
        'is_fixed_asset',
        'quantity',
        'min_stock_level',
        'qr_code',
        'rfid_tag',
        'warranty_expiry_date',
        'contract_details',
    ];

    protected $casts = [
        'purchase_date' => 'date',
        'purchase_cost' => 'decimal:2',
        'is_fixed_asset' => 'boolean',
        'warranty_expiry_date' => 'date',
        'contract_details' => 'json',
    ];

    public function assetCategory()
    {
        return $this->belongsTo(AssetCategory::class);
    }

    public function condition()
    {
        return $this->belongsTo(AssetCondition::class, 'asset_condition_id');
    }

    public function statusRecord()
    {
        return $this->belongsTo(AssetStatus::class, 'asset_status_id');
    }

    public function acquisitionTypeRecord()
    {
        return $this->belongsTo(AcquisitionType::class, 'acquisition_type_id');
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

    public function supplier()
    {
        return $this->belongsTo(\App\Models\Procurement\Supplier::class);
    }

    public function depreciation()
    {
        return $this->belongsTo(Depreciation::class);
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
     * Calculate monthly depreciation.
     */
    public function getMonthlyDepreciationAttribute()
    {
        if (!$this->depreciation || !$this->depreciation->months || $this->depreciation->months <= 0) {
            return 0;
        }

        return $this->purchase_cost / $this->depreciation->months;
    }

    /**
     * Calculate annual depreciation.
     */
    public function getAnnualDepreciationAttribute()
    {
        return $this->monthly_depreciation * 12;
    }

    public function getCurrentValueAttribute()
    {
        if (!$this->purchase_date || !$this->depreciation || !$this->depreciation->months) {
            return $this->purchase_cost;
        }

        $monthsOwned = $this->purchase_date->diffInMonths(now());
        
        if ($monthsOwned >= $this->depreciation->months) {
            return 0;
        }

        return max(0, $this->purchase_cost - ($this->monthly_depreciation * $monthsOwned));
    }

    public function getRemainingMonthsAttribute()
    {
        if (!$this->purchase_date || !$this->depreciation || !$this->depreciation->months) {
            return 0;
        }

        $monthsOwned = $this->purchase_date->diffInMonths(now());
        return max(0, $this->depreciation->months - $monthsOwned);
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

        $depreciationAmount = $this->monthly_depreciation;
        $lastLog = $this->depreciationLogs()->orderBy('period_date', 'desc')->first();
        $currentBookValue = $lastLog ? $lastLog->book_value : $this->purchase_cost;
        
        $newBookValue = max(0, $currentBookValue - $depreciationAmount);

        return $this->depreciationLogs()->create([
            'period_date' => $periodDate,
            'depreciation_amount' => $depreciationAmount,
            'book_value' => $newBookValue,
        ]);
    }
}
