<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Models\Procurement\Supplier;

class Vehicle extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'plate_number',
        'vehicle_type_id',
        'country_manufacturer',
        'model',
        'year_manufactured',
        'manufacturer',
        'supplier_id',
        'acquisition_status',
        'purchase_date',
        'kms_driven_at_purchase',
        'purchase_price',
        'currency',
        'chassis_number',
        'motor_number',
        'color',
        'horsepower',
        'general_weight',
        'single_weight',
        'engine_size_cc',
        'capacity',
        'fuel_type',
        'number_of_cylinders',
        'general_insurance',
        'third_party_insurance',
        'trade_license_number',
        'latest_technical_inspection_date',
        'latest_technical_inspection_expiry',
        'latest_general_inspection_date',
        'latest_general_inspection_expiry',
        'latest_third_party_inspection_date',
        'insurance_renewal_date',
        'vehicle_status_id',
        'current_location_id',
        'remarks',
        'is_active',
    ];

    protected $casts = [
        'purchase_date' => 'date',
        'latest_technical_inspection_date' => 'date',
        'latest_technical_inspection_expiry' => 'date',
        'latest_general_inspection_date' => 'date',
        'latest_general_inspection_expiry' => 'date',
        'latest_third_party_inspection_date' => 'date',
        'insurance_renewal_date' => 'date',
        'is_active' => 'boolean',
        'kms_driven_at_purchase' => 'decimal:2',
        'purchase_price' => 'decimal:2',
    ];

    public function type()
    {
        return $this->belongsTo(VehicleSetting::class, 'vehicle_type_id');
    }

    public function statusRecord()
    {
        return $this->belongsTo(VehicleSetting::class, 'vehicle_status_id');
    }

    public function supplier()
    {
        return $this->belongsTo(Supplier::class, 'supplier_id');
    }

    public function currentLocation()
    {
        return $this->belongsTo(Location::class, 'current_location_id');
    }

    public function assignments()
    {
        return $this->hasMany(VehicleAssignment::class);
    }

    public function maintenances()
    {
        return $this->hasMany(VehicleMaintenance::class);
    }

    public function fuelLogs()
    {
        return $this->hasMany(VehicleFuelLog::class);
    }
}
