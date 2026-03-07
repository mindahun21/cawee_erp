<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class VehicleDetail extends Model
{
    protected $fillable = [
        'asset_id',
        'plate_number',
        'chassis_number',
        'motor_number',
        'engine_size',
        'fuel_type',
        'capacity',
        'color',
        'horsepower',
        'year_manufactured',
        'manufacturer',
        'insurance_company',
        'insurance_policy_no',
        'insurance_expiration_date',
        'technical_inspection_date',
        'technical_inspection_expiration_date',
    ];

    protected $casts = [
        'insurance_expiration_date' => 'date',
        'technical_inspection_date' => 'date',
        'technical_inspection_expiration_date' => 'date',
    ];

    public function asset()
    {
        return $this->belongsTo(Asset::class);
    }
}
