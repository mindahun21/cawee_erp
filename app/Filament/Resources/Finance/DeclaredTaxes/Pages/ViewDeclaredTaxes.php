<?php
namespace App\Filament\Resources\Finance\DeclaredTaxes\Pages;
use App\Filament\Resources\Finance\DeclaredTaxes\DeclaredTaxResource;
use Filament\Resources\Pages\ViewRecord;
use Filament\Actions\EditAction;
use Filament\Actions\Action;
use Filament\Notifications\Notification;
class ViewDeclaredTaxes extends ViewRecord {
    protected static string $resource = DeclaredTaxResource::class;
    protected function getHeaderActions(): array {
        return [
            EditAction::make()->visible($this->record->status === 'draft'),
            Action::make('mark_filed')->label('Mark as Filed')->icon('heroicon-o-document-check')->color('warning')
                ->visible($this->record->status === 'draft')
                ->requiresConfirmation()
                ->action(function () {
                    $this->record->forceFill(['status' => 'filed'])->save();
                    Notification::make()->success()->title('Tax marked as filed.')->send();
                    redirect($this->getResource()::getUrl('view', ['record' => $this->record]));
                }),
            Action::make('mark_paid')->label('Mark as Paid')->icon('heroicon-o-banknotes')->color('success')
                ->visible($this->record->status === 'filed')
                ->requiresConfirmation()
                ->action(function () {
                    $this->record->forceFill(['status' => 'paid', 'payment_date' => now(), 'paid_amount' => $this->record->tax_payable])->save();
                    Notification::make()->success()->title('Tax marked as paid.')->send();
                    redirect($this->getResource()::getUrl('view', ['record' => $this->record]));
                }),
        ];
    }
}
