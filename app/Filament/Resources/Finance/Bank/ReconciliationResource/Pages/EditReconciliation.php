<?php
namespace App\Filament\Resources\Finance\Bank\ReconciliationResource\Pages;
use App\Filament\Resources\Finance\Bank\ReconciliationResource;
use Filament\Resources\Pages\EditRecord;
class EditReconciliation extends EditRecord {
    protected static string $resource = ReconciliationResource::class;
    protected function getHeaderActions(): array { return [\Filament\Actions\ViewAction::make()]; }
    protected function afterSave(): void {
        $this->record->calculateTotals();
    }
}
