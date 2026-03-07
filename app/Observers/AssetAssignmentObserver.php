<?php

namespace App\Observers;

use App\Models\Asset;
use App\Models\AssetAssignment;

class AssetAssignmentObserver
{
    /**
     * Handle the AssetAssignment "created" event.
     */
    public function created(AssetAssignment $assetAssignment): void
    {
        \Illuminate\Support\Facades\Log::info('AssetAssignmentObserver@created for Assignment ID: ' . $assetAssignment->id);
        
        $asset = $assetAssignment->asset;
        if (!$asset && $assetAssignment->asset_id) {
            \Illuminate\Support\Facades\Log::warning('Relationship "asset" was null, fetching via Asset::find(' . $assetAssignment->asset_id . ')');
            $asset = Asset::find($assetAssignment->asset_id);
        }

        if ($asset) {
            \Illuminate\Support\Facades\Log::info('Found asset: ' . $asset->name . ' (ID: ' . $asset->id . '). Current status: ' . $asset->status);
            $updated = $asset->update([
                'status' => 'assigned',
                'location_id' => $assetAssignment->location_id ?? $asset->location_id,
                'department_id' => $assetAssignment->department_id ?? $asset->department_id,
            ]);
            \Illuminate\Support\Facades\Log::info('Asset update ' . ($updated ? 'SUCCESSFUL' : 'FAILED'));
            \Illuminate\Support\Facades\Log::info('New status in memory: ' . $asset->status);
        } else {
            \Illuminate\Support\Facades\Log::error('Asset not found for assignment ' . $assetAssignment->id);
        }
    }

    /**
     * Handle the AssetAssignment "updated" event.
     */
    public function updated(AssetAssignment $assetAssignment): void
    {
        $asset = $assetAssignment->asset;
        if (!$asset) return;

        // If returned_date was filled, mark asset as available
        if ($assetAssignment->wasChanged('returned_date') && !empty($assetAssignment->returned_date)) {
            $asset->update(['status' => 'available']);
        }

        // Handle case where assignment details changed while still active
        if (empty($assetAssignment->returned_date)) {
            $asset->update([
                'status' => 'assigned',
                'location_id' => $assetAssignment->location_id ?? $asset->location_id,
                'department_id' => $assetAssignment->department_id ?? $asset->department_id,
            ]);
        }

        // Handle asset_id change
        if ($assetAssignment->wasChanged('asset_id')) {
            $oldAssetId = $assetAssignment->getOriginal('asset_id');
            $oldAsset = Asset::find($oldAssetId);
            if ($oldAsset) {
                $oldAsset->update(['status' => 'available']);
            }
            $asset->update(['status' => 'assigned']);
        }
    }

    /**
     * Handle the AssetAssignment "deleted" event.
     */
    public function deleted(AssetAssignment $assetAssignment): void
    {
        $asset = $assetAssignment->asset;
        if ($asset && empty($assetAssignment->returned_date)) {
            $asset->update(['status' => 'available']);
        }
    }
}
