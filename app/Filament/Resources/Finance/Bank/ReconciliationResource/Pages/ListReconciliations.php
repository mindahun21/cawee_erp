<?php
namespace App\Filament\Resources\Finance\Bank\ReconciliationResource\Pages;
use App\Filament\Resources\Finance\Bank\ReconciliationResource;
use Filament\Resources\Pages\ListRecords;
class ListReconciliations extends ListRecords {
    protected static string $resource = ReconciliationResource::class;
    protected function getHeaderActions(): array { return [\Filament\Actions\CreateAction::make()]; }
}
