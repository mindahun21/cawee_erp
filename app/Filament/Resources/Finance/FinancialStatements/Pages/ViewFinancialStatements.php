<?php
namespace App\Filament\Resources\Finance\FinancialStatements\Pages;
use App\Filament\Resources\Finance\FinancialStatements\FinancialStatementResource;
use Filament\Resources\Pages\ViewRecord;
use Filament\Actions\EditAction;
use Filament\Actions\Action;
use Filament\Notifications\Notification;
class ViewFinancialStatements extends ViewRecord {
    protected static string $resource = FinancialStatementResource::class;
    protected function getHeaderActions(): array {
        return [
            EditAction::make()->visible($this->record->status === 'draft'),
            Action::make('export_pdf')->label('Export PDF')->icon('heroicon-o-arrow-down-tray')->color('primary')
                ->action(fn (\App\Services\Finance\FinanceReportService $reportService) => $reportService->downloadPdf($this->record)),
            Action::make('finalize')->label('Finalize')->icon('heroicon-o-check-badge')->color('success')
                ->visible($this->record->status === 'draft' && (auth()->user()?->isFinanceManager() || auth()->user()?->isSuperAdmin()))
                ->requiresConfirmation()
                ->action(function () {
                    $this->record->forceFill(['status' => 'finalized', 'approved_by' => auth()->id(), 'approved_at' => now()])->save();
                    Notification::make()->success()->title('Statement finalized.')->send();
                    redirect($this->getResource()::getUrl('view', ['record' => $this->record]));
                }),
        ];
    }
}
