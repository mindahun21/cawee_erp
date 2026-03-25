<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class InventoryMovement extends Model
{
    protected $fillable = [
        'movement_type',
        'item_id',
        'from_warehouse_id',
        'destination_type',
        'to_warehouse_id',
        'to_location_id',
        'to_department_id',
        'employee_id',
        'approved_by_id',
        'supplier_id',
        'quantity',
        'date',
        'reference_no',
        'remarks',
        'attachments',
        'reason_id',
        'status_id',
    ];

    protected $casts = [
        'date' => 'date',
        'quantity' => 'integer',
        'attachments' => 'json',
    ];

    public function approvedBy(): BelongsTo
    {
        return $this->belongsTo(Employee::class, 'approved_by_id');
    }

    public function item(): BelongsTo
    {
        return $this->belongsTo(Item::class);
    }

    public function fromWarehouse(): BelongsTo
    {
        return $this->belongsTo(Warehouse::class, 'from_warehouse_id');
    }

    public function toWarehouse(): BelongsTo
    {
        return $this->belongsTo(Warehouse::class, 'to_warehouse_id');
    }

    public function toLocation(): BelongsTo
    {
        return $this->belongsTo(Location::class, 'to_location_id');
    }

    public function toDepartment(): BelongsTo
    {
        return $this->belongsTo(Department::class, 'to_department_id');
    }

    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class, 'employee_id');
    }

    public function supplier(): BelongsTo
    {
        return $this->belongsTo(\App\Models\Procurement\Supplier::class, 'supplier_id');
    }

    public function reason(): BelongsTo
    {
        return $this->belongsTo(InventoryMovementReason::class, 'reason_id');
    }

    public function status(): BelongsTo
    {
        return $this->belongsTo(InventoryMovementStatus::class, 'status_id');
    }
}
