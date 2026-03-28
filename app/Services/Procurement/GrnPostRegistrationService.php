<?php

namespace App\Services\Procurement;

use App\Models\Asset;
use App\Models\InventoryMovement;
use App\Models\Item;
use App\Models\Procurement\GoodsReceipt;
use App\Models\Procurement\GoodsReceiptItem;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * GrnPostRegistrationService
 *
 * Routes each accepted GRN line item into the correct downstream module:
 *  - 'consumable'  → Inventory (InventoryMovement receipt entry)
 *  - 'fixed_asset' → Asset Register (Asset record)
 *  - 'skip'        → No action
 *
 * Idempotent: items with a non-null registered_at are skipped.
 */
class GrnPostRegistrationService
{
    /**
     * Register all pending items from an accepted GRN.
     *
     * @return array{inventory: int, assets: int, skipped: int, errors: int}
     */
    public static function register(GoodsReceipt $grn): array
    {
        $grn->loadMissing(['items.poItem', 'purchaseOrder.supplier']);

        $counts = ['inventory' => 0, 'assets' => 0, 'skipped' => 0, 'errors' => 0];

        $pendingItems = $grn->items()
            ->whereNull('registered_at')
            ->where('item_type', '!=', 'skip')
            ->with('poItem')
            ->get();

        foreach ($pendingItems as $grnItem) {
            try {
                DB::transaction(function () use ($grn, $grnItem, &$counts) {
                    match ($grnItem->item_type) {
                        'fixed_asset' => static::registerAsAsset($grn, $grnItem, $counts),
                        default       => static::registerAsInventory($grn, $grnItem, $counts),
                    };
                });
            } catch (\Throwable $e) {
                Log::error('GrnPostRegistrationService: failed to register item', [
                    'grn_item_id' => $grnItem->id,
                    'error'       => $e->getMessage(),
                ]);
                $counts['errors']++;
            }
        }

        // Mark skipped items as processed too
        $grn->items()->where('item_type', 'skip')->whereNull('registered_at')
            ->update(['registered_at' => now(), 'registration_ref' => 'SKIPPED']);
        $counts['skipped'] = $grn->items()->where('item_type', 'skip')->count();

        return $counts;
    }

    // ─────────────────────────────────────────────────────────────────────────
    // Private handlers
    // ─────────────────────────────────────────────────────────────────────────

    /**
     * Create an InventoryMovement (GRN stock-in) for a consumable GRN item.
     * An Item catalog record is created automatically if one does not already
     * exist for the given description.
     */
    private static function registerAsInventory(
        GoodsReceipt    $grn,
        GoodsReceiptItem $grnItem,
        array           &$counts
    ): void {
        $description = trim($grnItem->poItem?->description ?? 'GRN Item');
        $qty         = (int) max(1, $grnItem->accepted_quantity ?? $grnItem->received_quantity ?? 1);

        // Resolve or create a catalog Item so InventoryMovement has a valid item_id.
        $catalogItem = Item::firstOrCreate(
            ['name' => $description],
            [
                'item_code'  => 'GRN-' . str_pad($grnItem->id, 6, '0', STR_PAD_LEFT),
                'item_type'  => 'consumable',
                'description'=> "Auto-created from GRN {$grn->grn_number}",
            ]
        );

        $movement = InventoryMovement::create([
            'movement_type' => 'Receipt',
            'item_id'       => $catalogItem->id,
            'quantity'      => $qty,
            'date'          => $grn->receipt_date ?? now()->toDateString(),
            'reference_no'  => $grn->grn_number,
            'supplier_id'   => $grn->purchaseOrder?->supplier_id,
            'remarks'       => "GRN Receipt — {$grn->grn_number} · PO: {$grn->purchaseOrder?->po_number}",
        ]);

        $grnItem->update([
            'registered_at'    => now(),
            'registration_ref' => 'INV-MOV-' . $movement->id,
        ]);

        $counts['inventory']++;
    }

    /**
     * Create an Asset record for a fixed-asset GRN item.
     * Uses the PO unit price as purchase_cost and GRN date as purchase_date.
     */
    private static function registerAsAsset(
        GoodsReceipt    $grn,
        GoodsReceiptItem $grnItem,
        array           &$counts
    ): void {
        $description = trim($grnItem->poItem?->description ?? 'Fixed Asset');
        $unitPrice   = (float) ($grnItem->poItem?->unit_price ?? 0);
        $qty         = max(1, (int) ($grnItem->accepted_quantity ?? 1));

        $asset = Asset::create([
            'name'           => $description,
            'is_fixed_asset' => true,
            'purchase_cost'  => $unitPrice,
            'purchase_date'  => $grn->receipt_date ?? now()->toDateString(),
            'supplier_id'    => $grn->purchaseOrder?->supplier_id,
            'quantity'       => $qty,
            'notes'          => "Registered from GRN: {$grn->grn_number} — PO: {$grn->purchaseOrder?->po_number}",
        ]);

        $grnItem->update([
            'registered_at'    => now(),
            'registration_ref' => 'ASSET-' . $asset->id,
        ]);

        $counts['assets']++;
    }

    // ─────────────────────────────────────────────────────────────────────────
    // Helpers / status queries
    // ─────────────────────────────────────────────────────────────────────────

    /** Returns true if all (non-skipped) items on the GRN are already registered. */
    public static function isFullyRegistered(GoodsReceipt $grn): bool
    {
        return ! $grn->items()
            ->whereNull('registered_at')
            ->where('item_type', '!=', 'skip')
            ->exists();
    }

    /** Human-readable registration status for display in the GRN table/view. */
    public static function registrationSummary(GoodsReceipt $grn): string
    {
        $total      = $grn->items()->count();
        $registered = $grn->items()->whereNotNull('registered_at')->count();

        if ($total === 0)        return 'No items';
        if ($registered === 0)   return 'Pending';
        if ($registered < $total) return "{$registered}/{$total} registered";
        return 'Fully Registered';
    }
}
