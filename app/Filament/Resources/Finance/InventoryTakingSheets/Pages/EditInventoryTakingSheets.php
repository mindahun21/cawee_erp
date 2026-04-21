<?php
namespace App\Filament\Resources\Finance\InventoryTakingSheets\Pages;
use App\Filament\Resources\Finance\InventoryTakingSheets\InventoryTakingSheetResource;
use Filament\Resources\Pages\EditRecord;
class EditInventoryTakingSheets extends EditRecord {
    protected static string $resource = InventoryTakingSheetResource::class;
    protected function getRedirectUrl(): string { return $this->getResource()::getUrl('view', ['record' => $this->record]); }
}
