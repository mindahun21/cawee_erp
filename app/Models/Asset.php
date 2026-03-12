<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Asset extends Model
{
    protected $fillable = [
        'asset_condition_id',
        'asset_status_id',
        'acquisition_type_id',
        'currency_id',
        'donor_id',
        'supplier_id',
        'location_id',
        'department_id',
        'asset_model_id',
        'name',
        'notes',
        'serial_number',
        'barcode',
        'purchase_cost',
        'purchase_date',
        'description',
        'is_fixed_asset',
        'quantity',
        'qr_code',
        'rfid_tag',
        'warranty_expiry_date',
        'unit_id',
        'contract_details',
    ];

    protected $casts = [
        'purchase_date' => 'date',
        'purchase_cost' => 'decimal:2',
        'warranty_expiry_date' => 'date',
        'contract_details' => 'json',
    ];

    public function getAssetCategoryAttribute()
    {
        return $this->assetModel?->category;
    }

    public function assetModel()
    {
        return $this->belongsTo(AssetModel::class, 'asset_model_id');
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

    public function unit()
    {
        return $this->belongsTo(Unit::class);
    }

    public function getDepreciationAttribute()
    {
        return $this->assetModel?->depreciation;
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
        // If there are stock records, sum them up. Otherwise fallback to the master quantity
        // This is a graceful fallback for existing data before the pivot migration
        $stockSum = $this->exists ? $this->stocks()->sum('quantity') : 0;
        return $stockSum > 0 ? $stockSum : ($this->attributes['quantity'] ?? 0);
    }


    /**
     * Calculate current value as of a specific date (defaults to now).
     */
    public function getCurrentValueAsOf(?\Carbon\Carbon $asOf = null): float
    {
        $asOf = $asOf ?? now();

        if (!$this->purchase_date || !$this->depreciation || !$this->depreciation->months) {
            return (float) ($this->purchase_cost ?? 0);
        }

        $monthsOwned = (int) $this->purchase_date->diffInMonths($asOf);

        if ($monthsOwned >= $this->depreciation->months) {
            return 0;
        }

        return max(0, (float) $this->purchase_cost - ($this->getMonthlyDepreciationAsOf() * $monthsOwned));
    }

    /**
     * Monthly depreciation amount (not date-dependent, but kept parallel for clarity).
     */
    public function getMonthlyDepreciationAsOf(): float
    {
        if (!$this->depreciation || !$this->depreciation->months || $this->depreciation->months <= 0) {
            return 0;
        }

        return (float) $this->purchase_cost / $this->depreciation->months;
    }

    /**
     * Remaining months as of a specific date.
     */
    public function getRemainingMonthsAsOf(?\Carbon\Carbon $asOf = null): int
    {
        $asOf = $asOf ?? now();

        if (!$this->purchase_date || !$this->depreciation || !$this->depreciation->months) {
            return 0;
        }

        $monthsOwned = (int) $this->purchase_date->diffInMonths($asOf);
        return max(0, $this->depreciation->months - $monthsOwned);
    }

    public function getEolDateAttribute()
    {
        if (!$this->purchase_date || !$this->depreciation || !$this->depreciation->months) {
            return null;
        }

        return $this->purchase_date->copy()->addMonths($this->depreciation->months);
    }

    public function getCheckedOutStatusAttribute()
    {
        // An asset is checked out if it has any active assignment without a returned_date
        $activeAssignments = $this->assignments()->whereNull('returned_date')->count();
        return $activeAssignments > 0 ? 'Checked Out' : 'Available';
    }

    public function getRemainingValueAttribute()
    {
        return (float) $this->purchase_cost - (float) $this->current_value;
    }

    protected static function boot()
    {
        parent::boot();

        static::created(function ($asset) {
            if ($asset->serial_number) {
                \App\Models\PrefixSetting::updateNextNumberFromCode('asset_serial_number', $asset->serial_number);
            }
            if ($asset->barcode) {
                \App\Models\PrefixSetting::updateNextNumberFromCode('asset_barcode', $asset->barcode);
            }
            if ($asset->qr_code) {
                \App\Models\PrefixSetting::updateNextNumberFromCode('asset_qr_code', $asset->qr_code);
            }
            if ($asset->rfid_tag) {
                \App\Models\PrefixSetting::updateNextNumberFromCode('asset_rfid_tag', $asset->rfid_tag);
            }
        });
    }
}
