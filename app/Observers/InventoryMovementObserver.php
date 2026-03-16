<?php

namespace App\Observers;

use App\Models\InventoryMovement;

class InventoryMovementObserver
{
    /**
     * Handle the InventoryMovement "created" event.
     */
    public function created(InventoryMovement $inventoryMovement): void
    {
        $this->processMovement($inventoryMovement);
    }

    /**
     * Handle the InventoryMovement "updated" event.
     */
    public function updated(InventoryMovement $inventoryMovement): void
    {
        $oldStatusName = $inventoryMovement->getOriginal('status_id') 
            ? \App\Models\InventoryMovementStatus::find($inventoryMovement->getOriginal('status_id'))?->name 
            : null;
        $newStatusName = $inventoryMovement->movementStatus?->name;

        $statusWasCompleted = $oldStatusName === 'Completed / Received';
        $statusIsCompleted = $newStatusName === 'Completed / Received';

        // 1. Status changed to completed
        if (!$statusWasCompleted && $statusIsCompleted) {
            $this->processMovement($inventoryMovement);
            return;
        }

        // 2. Status changed away from completed
        if ($statusWasCompleted && !$statusIsCompleted) {
            $this->revertMovement($inventoryMovement, true); // use original values
            return;
        }

        // 3. Status remains completed but key fields changed
        if ($statusWasCompleted && $statusIsCompleted) {
            $keys = ['item_id', 'quantity', 'from_warehouse_id', 'to_warehouse_id', 'to_location_id', 'to_department_id'];
            if ($inventoryMovement->wasChanged($keys)) {
                // Revert with old values, then apply new ones
                $this->revertMovement($inventoryMovement, true);
                $this->processMovement($inventoryMovement);
            }
        }
    }

    /**
     * Handle the InventoryMovement "deleted" event.
     */
    public function deleted(InventoryMovement $inventoryMovement): void
    {
        if ($inventoryMovement->movementStatus?->name === 'Completed / Received') {
            $this->revertMovement($inventoryMovement);
        }
    }

    protected function processMovement(InventoryMovement $inventoryMovement, bool $useOriginal = false): void
    {
        $status_id = $useOriginal ? $inventoryMovement->getOriginal('status_id') : $inventoryMovement->status_id;
        $statusName = \App\Models\InventoryMovementStatus::find($status_id)?->name;

        if ($statusName !== 'Completed / Received') {
            return;
        }

        $this->adjustStock($inventoryMovement, $useOriginal, false);
    }

    protected function revertMovement(InventoryMovement $inventoryMovement, bool $useOriginal = false): void
    {
        $this->adjustStock($inventoryMovement, $useOriginal, true);
    }

    protected function adjustStock(InventoryMovement $movement, bool $useOriginal, bool $isRevert): void
    {
        $itemId = $useOriginal ? $movement->getOriginal('item_id') : $movement->item_id;
        $quantity = $useOriginal ? $movement->getOriginal('quantity') : $movement->quantity;
        $fromId = $useOriginal ? $movement->getOriginal('from_warehouse_id') : $movement->from_warehouse_id;
        
        $destinationType = $useOriginal ? $movement->getOriginal('destination_type') : $movement->destination_type;
        $toWarehouseId = $useOriginal ? $movement->getOriginal('to_warehouse_id') : $movement->to_warehouse_id;

        $item = \App\Models\Item::find($itemId);
        if (!$item) return;

        // Helper to adjust stock record
        $change = $isRevert ? -$quantity : $quantity;

        // Decrease stock from source warehouse
        if ($fromId) {
            $this->updateStock($item, $fromId, -$change);
        }

        // Increase stock in destination (if warehouse)
        if ($destinationType === 'warehouse' && $toWarehouseId) {
            $this->updateStock($item, $toWarehouseId, $change);
        }
        
        // Note: For 'location_department', it doesn't currently update any 'item_location' table 
        // as per the requirement in conversation c2a36021 which focused on item_warehouse.
    }

    protected function updateStock($item, $warehouseId, $amount): void
    {
        $itemWarehouse = \App\Models\ItemWarehouse::where('item_id', $item->id)
            ->where('warehouse_id', $warehouseId)
            ->first();

        if ($itemWarehouse) {
            $itemWarehouse->increment('quantity', $amount);
        } else {
            // If it doesn't exist, we might want to create it if it's an increase
            if ($amount > 0) {
                \App\Models\ItemWarehouse::create([
                    'item_id' => $item->id,
                    'warehouse_id' => $warehouseId,
                    'quantity' => $amount,
                ]);
            }
        }
    }
}
