<?php
namespace App\Filament\Resources\Finance\ProjectProgressPayments\Pages;
use App\Filament\Resources\Finance\ProjectProgressPayments\ProjectProgressPaymentResource;
use Filament\Resources\Pages\ViewRecord;
use Filament\Actions\EditAction;
use Filament\Actions\Action;
use Filament\Notifications\Notification;
class ViewProjectProgressPayments extends ViewRecord {
    protected static string $resource = ProjectProgressPaymentResource::class;
    protected function getHeaderActions(): array {
        return [
            EditAction::make()->visible($this->record->status === 'received'),
            Action::make('mark_spent')->label('Mark as Partially Spent')->icon('heroicon-o-banknotes')->color('warning')
                ->visible($this->record->status === 'received')
                ->requiresConfirmation()
                ->action(function () {
                    $this->record->forceFill(['status' => 'partially_spent'])->save();
                    Notification::make()->success()->title('Payment marked as partially spent.')->send();
                    redirect($this->getResource()::getUrl('view', ['record' => $this->record]));
                }),
            Action::make('mark_utilized')->label('Mark as Fully Utilized')->icon('heroicon-o-check-circle')->color('success')
                ->visible(in_array($this->record->status, ['received', 'partially_spent']))
                ->requiresConfirmation()
                ->action(function () {
                    $this->record->forceFill(['status' => 'fully_utilized'])->save();
                    Notification::make()->success()->title('Payment marked as fully utilized.')->send();
                    redirect($this->getResource()::getUrl('view', ['record' => $this->record]));
                }),
        ];
    }
}
