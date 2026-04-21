<?php
namespace App\Filament\Resources\Finance\InventoryTakingSheets\Pages;
use App\Filament\Resources\Finance\InventoryTakingSheets\InventoryTakingSheetResource;
use Filament\Resources\Pages\ViewRecord;
use Filament\Actions\EditAction;
use Filament\Actions\Action;
use Filament\Notifications\Notification;
class ViewInventoryTakingSheets extends ViewRecord {
    protected static string $resource = InventoryTakingSheetResource::class;
    protected function getHeaderActions(): array {
        return [
            EditAction::make()->visible($this->record->status === 'draft'),
            Action::make('verify')->label('Verify')->icon('heroicon-o-check-badge')->color('primary')
                ->visible($this->record->status === 'draft' && (auth()->user()?->isFinanceOfficer() || auth()->user()?->isFinanceManager()))
                ->requiresConfirmation()
                ->action(function () {
                    $this->record->forceFill(['status' => 'verified', 'verified_by' => auth()->id()])->save();
                    Notification::make()->success()->title('Inventory count verified.')->send();
                    redirect($this->getResource()::getUrl('view', ['record' => $this->record]));
                }),
            Action::make('submit')->label('Submit')->icon('heroicon-o-paper-airplane')->color('success')
                ->visible($this->record->status === 'verified' && (auth()->user()?->isFinanceManager() || auth()->user()?->isSuperAdmin()))
                ->requiresConfirmation()
                ->action(function () {
                    $this->record->forceFill(['status' => 'submitted'])->save();
                    Notification::make()->success()->title('Inventory taking sheet submitted.')->send();
                    redirect($this->getResource()::getUrl('view', ['record' => $this->record]));
                }),
        ];
    }
}
