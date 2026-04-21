<?php
namespace App\Filament\Resources\Finance\InventoryTakingSheets\Pages;
use App\Filament\Resources\Finance\InventoryTakingSheets\InventoryTakingSheetResource;
use Filament\Resources\Pages\ListRecords;
class ListInventoryTakingSheets extends ListRecords {
    protected static string $resource = InventoryTakingSheetResource::class;
    protected function getHeaderActions(): array { return [\Filament\Actions\CreateAction::make()]; }
}
