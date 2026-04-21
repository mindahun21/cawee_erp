<?php
namespace App\Filament\Resources\Finance\Payroll\Pages;
use App\Filament\Resources\Finance\Payroll\PayrollSummaryResource;
use App\Models\Finance\PayrollSummary;
use App\Services\Finance\PayrollGLPostingService;
use Filament\Actions\Action;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ViewRecord;
class ViewPayrollSummaries extends ViewRecord {
    protected static string $resource = PayrollSummaryResource::class;
    protected function getHeaderActions(): array {
        /** @var PayrollSummary $record */
        $record = $this->record;
        return [
            Action::make('post_gl')
                ->label('Post to GL')->icon('heroicon-o-arrow-up-circle')->color('success')
                ->visible($record->isDraft() && auth()->user()?->isFinanceManager())
                ->requiresConfirmation()
                ->modalHeading('Post Payroll to General Ledger')
                ->modalDescription('This will create an immutable double-entry journal. Cannot be undone.')
                ->action(function () use ($record) {
                    try {
                        app(PayrollGLPostingService::class)->postToGL($record);
                        Notification::make()->success()->title('✅ Payroll posted to GL.')->send();
                        redirect($this->getResource()::getUrl('view', ['record' => $record]));
                    } catch (\Throwable $e) {
                        Notification::make()->danger()->title('Error')->body($e->getMessage())->send();
                    }
                }),
            Action::make('view_je')
                ->label('View Journal Entry')->icon('heroicon-o-arrow-top-right-on-square')->color('info')
                ->visible((bool) $record->journal_entry_id)
                ->url(fn () => \App\Filament\Resources\Finance\Journals\JournalEntryResource::getUrl('view', ['record' => $record->journal_entry_id])),
        ];
    }
}
