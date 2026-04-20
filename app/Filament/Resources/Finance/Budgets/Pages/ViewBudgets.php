<?php
namespace App\Filament\Resources\Finance\Budgets\Pages;
use App\Filament\Resources\Finance\Budgets\BudgetResource;
use App\Models\Finance\Budget;
use Filament\Actions\Action;
use Filament\Actions\EditAction;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ViewRecord;
class ViewBudgets extends ViewRecord {
    protected static string $resource = BudgetResource::class;
    protected function getHeaderActions(): array {
        /** @var Budget $record */
        $record = $this->record;
        $user   = auth()->user();
        return [
            EditAction::make()->visible($record->isDraft()),
            Action::make('approve')->label('Approve')->icon('heroicon-o-check-badge')->color('success')
                ->visible($record->isDraft() && ($user?->isFinanceManager() || $user?->isSuperAdmin()))
                ->requiresConfirmation()
                ->action(function () use ($record) {
                    $record->forceFill(['status' => 'approved', 'approved_by' => auth()->id(), 'approved_at' => now()])->save();
                    Notification::make()->success()->title('Budget approved.')->send();
                    redirect($this->getResource()::getUrl('view', ['record' => $record]));
                }),
            Action::make('activate')->label('Activate')->icon('heroicon-o-play')->color('info')
                ->visible($record->isApproved() && ($user?->isFinanceManager() || $user?->isSuperAdmin()))
                ->requiresConfirmation()
                ->action(function () use ($record) {
                    $record->forceFill(['status' => 'active'])->save();
                    Notification::make()->success()->title('Budget activated.')->send();
                    redirect($this->getResource()::getUrl('view', ['record' => $record]));
                }),
            Action::make('close')->label('Close Budget')->icon('heroicon-o-lock-closed')->color('danger')
                ->visible($record->isActive() && ($user?->isFinanceManager() || $user?->isSuperAdmin()))
                ->requiresConfirmation()
                ->action(function () use ($record) {
                    $record->forceFill(['status' => 'closed'])->save();
                    Notification::make()->warning()->title('Budget closed.')->send();
                    redirect($this->getResource()::getUrl('view', ['record' => $record]));
                }),
        ];
    }
}
