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

    protected static function booted(): void
    {
        // When a vehicle is saved, mirror it as an Asset
        static::saved(function (Vehicle $vehicle) {
            $name = trim(implode(' ', array_filter([
                $vehicle->manufacturer,
                $vehicle->model,
                $vehicle->plate_number ? '(' . $vehicle->plate_number . ')' : null,
            ]))) ?: ('Vehicle #' . $vehicle->id);

            // Ensure Asset Metadata exists
            $category = \App\Models\AssetCategory::firstOrCreate(['name' => 'Vehicles']);
            $type = \App\Models\AssetType::firstOrCreate(['name' => 'Vehicle']);
            
            $modelName = trim("{$vehicle->manufacturer} {$vehicle->model}") ?: 'Generic Vehicle';
            $assetModel = \App\Models\AssetModel::firstOrCreate(
                ['name' => $modelName],
                ['asset_category_id' => $category->id, 'asset_type_id' => $type->id]
            );

            // Status mapping from Vehicle Status to Asset Status
            $vStatus = strtolower($vehicle->statusRecord?->name ?? 'available');
            $assetStatusName = match(true) {
                str_contains($vStatus, 'maintenance') || str_contains($vStatus, 'repair') => 'Maintenance',
                str_contains($vStatus, 'assign') || str_contains($vStatus, 'busy')    => 'Assigned',
                str_contains($vStatus, 'dispos') || str_contains($vStatus, 'junk')    => 'Disposed',
                default => 'Available',
            };
            $assetStatus = \App\Models\AssetStatus::firstOrCreate(['name' => $assetStatusName]);

            Asset::withoutTimestamps(function () use ($vehicle, $name, $assetModel, $assetStatus) {
                Asset::updateOrCreate(
                    ['asset_tag' => 'VEH-' . $vehicle->id],
                    [
                        'name'              => $name,
                        'asset_tag'         => 'VEH-' . $vehicle->id,
                        'asset_model_id'    => $assetModel->id,
                        'asset_status_id'   => $assetStatus?->id,
                        'purchase_date'     => $vehicle->purchase_date,
                        'purchase_cost'     => $vehicle->purchase_price ?? 0,
                        'notes'             => "Plate: {$vehicle->plate_number} | Managed in Vehicle module.",
                    ]
                );
            });
        });

        // When a vehicle is permanently deleted, remove the asset mirror
        static::deleted(function (Vehicle $vehicle) {
            Asset::where('asset_tag', 'VEH-' . $vehicle->id)->delete();
        });
    }

    public function type()
    {
        return $this->belongsTo(VehicleType::class, 'vehicle_type_id');
    }

    public function statusRecord()
    {
        return $this->belongsTo(VehicleStatus::class, 'vehicle_status_id');
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

    public function getNameAttribute(): string
    {
        return trim(implode(' ', array_filter([
            $this->manufacturer,
            $this->model,
            $this->plate_number ? "({$this->plate_number})" : null,
        ]))) ?: "Vehicle #{$this->id}";
    }
}
