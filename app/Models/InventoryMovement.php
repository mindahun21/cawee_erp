<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class InventoryMovement extends Model
{
    protected $fillable = [
        'item_id',
        'from_warehouse_id',
        'destination_type',
        'to_warehouse_id',
        'to_location_id',
        'to_department_id',
        'employee_id',
        'reason',
        'quantity',
        'date',
        'reference_no',
        'remarks',
        'status',
        'reason_id',
        'status_id',
    ];

    protected $casts = [
        'date' => 'date',
    ];

    public function item()
    {
        return $this->belongsTo(Item::class);
    }

    public function fromWarehouse()
    {
        return $this->belongsTo(Warehouse::class, 'from_warehouse_id');
    }

    public function toWarehouse()
    {
        return $this->belongsTo(Warehouse::class, 'to_warehouse_id');
    }

    public function toLocation()
    {
        return $this->belongsTo(Location::class, 'to_location_id');
    }

    public function toDepartment()
    {
        return $this->belongsTo(Department::class, 'to_department_id');
    }

    public function employee()
    {
        return $this->belongsTo(Employee::class, 'employee_id');
    }

    public function supplier()
    {
        return $this->belongsTo(\App\Models\Procurement\Supplier::class);
    }

    public function movementReason()
    {
        return $this->belongsTo(InventoryMovementReason::class, 'reason_id');
    }

    public function movementStatus()
    {
        return $this->belongsTo(InventoryMovementStatus::class, 'status_id');
    }
}
