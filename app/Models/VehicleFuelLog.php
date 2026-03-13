<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class VehicleFuelLog extends Model
{
    use HasFactory;

    protected $fillable = [
        'vehicle_id',
        'date',
        'quantity',
        'cost',
        'odometer_reading'
    ];

    protected $casts = [
        'date' => 'date',
        'quantity' => 'decimal:2',
        'cost' => 'decimal:2',
        'odometer_reading' => 'decimal:2',
    ];

    public function vehicle()
    {
        return $this->belongsTo(Vehicle::class);
    }
}
