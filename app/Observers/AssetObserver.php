<?php

namespace App\Observers;

use App\Models\Asset;

class AssetObserver
{
    /**
     * Handle the Asset "creating" event.
     */
    public function creating(Asset $asset): void
    {
        if ($asset->is_fixed_asset) {
            $uniqueId = strtoupper(bin2hex(random_bytes(4)));

            if (empty($asset->barcode)) {
                $asset->barcode = 'BC-' . $uniqueId;
            }
            if (empty($asset->qr_code)) {
                $asset->qr_code = 'QR-' . $uniqueId;
            }
            if (empty($asset->rfid_tag)) {
                $asset->rfid_tag = 'RFID-' . $uniqueId;
            }
        }
    }

    /**
     * Handle the Asset "deleting" event.
     */
    public function deleting(Asset $asset): void
    {
        if ($asset->status === 'assigned' || $asset->assignments()->whereNull('returned_date')->exists()) {
            \Filament\Notifications\Notification::make()
                ->title('Cannot Delete Assigned Asset')
                ->body("The asset '{$asset->name}' is currently assigned. Please return it before deleting.")
                ->danger()
                ->send();

            // Throwing an exception with a custom message that Filament displays nicely
            // Alternatively, return false and stop the action
            throw \Illuminate\Validation\ValidationException::withMessages([
                'asset' => "Cannot delete asset '{$asset->name}' because it is currently assigned.",
            ]);
        }
    }

    /**
     * Handle the Asset "created" event.
     */
    public function created(Asset $asset): void
    {
        //
    }

    /**
     * Handle the Asset "updated" event.
     */
    public function updated(Asset $asset): void
    {
        //
    }

    /**
     * Handle the Asset "deleted" event.
     */
    public function deleted(Asset $asset): void
    {
        //
    }

    /**
     * Handle the Asset "restored" event.
     */
    public function restored(Asset $asset): void
    {
        //
    }

    /**
     * Handle the Asset "force deleted" event.
     */
    public function forceDeleted(Asset $asset): void
    {
        //
    }
}
