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
        $statusWasCompleted = strtolower(trim($inventoryMovement->getOriginal('status'))) === 'completed';
        $statusIsCompleted = strtolower(trim($inventoryMovement->status)) === 'completed';

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
            $keys = ['asset_id', 'type', 'quantity', 'from_location_id', 'to_location_id'];
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
        if (strtolower(trim($inventoryMovement->status)) === 'completed') {
            $this->revertMovement($inventoryMovement);
        }
    }

    protected function processMovement(InventoryMovement $inventoryMovement, bool $useOriginal = false): void
    {
        $status = $useOriginal ? $inventoryMovement->getOriginal('status') : $inventoryMovement->status;
        if (strtolower(trim($status)) !== 'completed') {
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
        $assetId = $useOriginal ? $movement->getOriginal('asset_id') : $movement->asset_id;
        $type = $useOriginal ? $movement->getOriginal('type') : $movement->type;
        $quantity = $useOriginal ? $movement->getOriginal('quantity') : $movement->quantity;
        $fromId = $useOriginal ? $movement->getOriginal('from_location_id') : $movement->from_location_id;
        $toId = $useOriginal ? $movement->getOriginal('to_location_id') : $movement->to_location_id;

        $asset = \App\Models\Asset::find($assetId);
        if (!$asset) return;

        // Fixed assets logic
        if ($asset->is_fixed_asset) {
            if ($type === 'Transfer') {
                $locationId = $isRevert ? $fromId : $toId;
                if ($locationId) {
                    $asset->update(['location_id' => $locationId]);
                }
            }

            // Status Management for Fixed Assets
            $newStatus = null;
            $reason = $useOriginal ? $movement->getOriginal('reason') : $movement->reason;

            if (!$isRevert) {
                if ($reason === 'Issue/Assignment') {
                    $newStatus = 'assigned';
                } elseif ($type === 'Return' || $reason === 'Return') {
                    $newStatus = 'available';
                } elseif ($type === 'Damage' || $reason === 'Damage/Breakage') {
                    $newStatus = 'maintenance';
                } elseif ($type === 'Disposal') {
                    $newStatus = 'disposed';
                }
            } else {
                // Reverting: mostly back to available unless it was a return
                if ($reason === 'Issue/Assignment') {
                    $newStatus = 'available';
                } elseif ($type === 'Return' || $reason === 'Return') {
                    $newStatus = 'assigned'; // Reverting a return means it's back to being assigned? Or just available. Assigned is more accurate if it was previously assigned.
                }
            }

            if ($newStatus) {
                \Illuminate\Support\Facades\Log::info('Fixed Asset ' . $asset->id . ' status update to ' . $newStatus . ' from movement ' . $movement->id);
                $updated = $asset->update(['status' => $newStatus]);
                \Illuminate\Support\Facades\Log::info('Update result: ' . ($updated ? 'SUCCESS' : 'FAIL'));
            }
            return;
        }

        // Helper to adjust stock record
        $change = $isRevert ? -$quantity : $quantity;

        switch ($type) {
            case 'Transfer':
                if ($fromId && $toId) {
                    // Adjust source (decrement normally, increment if revert)
                    $this->updateStock($asset, $fromId, -$change);
                    // Adjust destination
                    $this->updateStock($asset, $toId, $change);
                }
                break;
            case 'Stock In':
            case 'Return':
                if ($toId) {
                    $this->updateStock($asset, $toId, $change);
                }
                break;
            case 'Stock Out':
            case 'Damage':
            case 'Disposal':
            case 'Issue/Assignment':
                if ($fromId) {
                    $this->updateStock($asset, $fromId, -$change);
                }
                break;
        }
    }

    protected function updateStock($asset, $locationId, $amount): void
    {
        $stock = $asset->stocks()->firstOrCreate(
            ['location_id' => $locationId],
            ['department_id' => null, 'quantity' => 0]
        );

        $stock->increment('quantity', $amount);
    }
}
