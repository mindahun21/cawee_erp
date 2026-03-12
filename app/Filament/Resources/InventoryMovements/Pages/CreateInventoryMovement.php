<?php

namespace App\Filament\Resources\InventoryMovements\Pages;

use App\Filament\Resources\InventoryMovements\InventoryMovementResource;
use Filament\Resources\Pages\CreateRecord;

class CreateInventoryMovement extends CreateRecord
{
    protected static string $resource = InventoryMovementResource::class;

    protected function afterCreate(): void
    {
        $movement = $this->record;
        
        // 1. Decrease quantity in source warehouse
        \App\Models\ItemWarehouse::where('item_id', $movement->item_id)
            ->where('warehouse_id', $movement->from_warehouse_id)
            ->decrement('quantity', $movement->quantity);

        // 2. If destination is a warehouse, increase quantity there
        if ($movement->destination_type === 'warehouse' && $movement->to_warehouse_id) {
            $toWarehouseStock = \App\Models\ItemWarehouse::where('item_id', $movement->item_id)
                ->where('warehouse_id', $movement->to_warehouse_id)
                ->first();

            if ($toWarehouseStock) {
                $toWarehouseStock->increment('quantity', $movement->quantity);
            } else {
                // Create new record if it doesn't exist in destination warehouse
                // We'll copy some basic info from the source warehouse if needed, 
                // but for now, just creating it with the quantity is essential.
                \App\Models\ItemWarehouse::create([
                    'item_id' => $movement->item_id,
                    'warehouse_id' => $movement->to_warehouse_id,
                    'quantity' => $movement->quantity,
                    'sku' => \App\Models\ItemWarehouse::generateUniqueSku(),
                ]);
            }
        }
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}
