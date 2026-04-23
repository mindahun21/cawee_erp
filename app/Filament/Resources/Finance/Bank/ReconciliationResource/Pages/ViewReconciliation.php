<?php
namespace App\Filament\Resources\Finance\Bank\ReconciliationResource\Pages;
use App\Filament\Resources\Finance\Bank\ReconciliationResource;
use Filament\Resources\Pages\ViewRecord;
use Filament\Actions\EditAction;
use Filament\Actions\Action;
class ViewReconciliation extends ViewRecord {
    protected static string $resource = ReconciliationResource::class;
    protected function getHeaderActions(): array {
        return [
            EditAction::make()->visible(fn () => $this->record->status === 'in_progress'),
            Action::make('mark_reconciled')->label('Mark Reconciled')->icon('heroicon-o-check-badge')->color('success')
                ->visible(fn () => $this->record->status === 'in_progress' && abs((float)$this->record->difference) < 0.01)
                ->requiresConfirmation()
                ->action(function () {
                    $this->record->forceFill([
                        'status' => 'reconciled',
                        'reviewed_by' => auth()->id(),
                        'reconciled_at' => now(),
                    ])->save();
                    \Filament\Notifications\Notification::make()->success()->title('Bank reconciliation completed.')->send();
                    redirect($this->getResource()::getUrl('view', ['record' => $this->record]));
                }),
        ];
    }
}
