<?php

namespace App\Filament\Resources\Finance\Loans\Pages;

use App\Filament\Resources\Finance\Loans\LoanResource;
use App\Filament\Resources\Finance\Loans\RelationManagers\LoanScheduleRelationManager;
use App\Models\Finance\Loan;
use App\Services\Finance\PaymentRequisitionService;
use Filament\Actions\Action;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Textarea;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ViewRecord;

class ViewLoans extends ViewRecord
{
    protected static string $resource = LoanResource::class;

    protected function getHeaderActions(): array
    {
        /** @var Loan $record */
        $record = $this->record;
        $user   = auth()->user();

        return [
            EditAction::make()
                ->visible($record->isActive() && ! $record->approved_by),

            // ── Approve & Generate Schedule ──────────────────────────────
            Action::make('approve_loan')
                ->label('Approve & Generate Schedule')
                ->icon('heroicon-o-check-badge')
                ->color('success')
                ->visible($record->isActive() && ! $record->approved_by)
                ->requiresConfirmation()
                ->modalHeading('Approve Loan')
                ->modalDescription(
                    "Approving will lock the loan terms and auto-generate a " .
                    "{$record->tenor_months}-installment repayment schedule. " .
                    "Principal: " . number_format($record->principal_amount, 2) . " " .
                    ($record->currency?->code ?? 'ETB')
                )
                ->action(function () use ($record) {
                    $record->forceFill([
                        'approved_by' => auth()->id(),
                        'approved_at' => now(),
                    ])->save();
                    $record->generateSchedule();
                    Notification::make()
                        ->success()
                        ->title('Loan approved')
                        ->body("Repayment schedule with {$record->tenor_months} installments generated.")
                        ->send();
                    redirect($this->getResource()::getUrl('view', ['record' => $record]));
                }),

            // ── Mark Fully Paid (manual override) ───────────────────────
            Action::make('mark_paid')
                ->label('Mark as Fully Paid')
                ->icon('heroicon-o-banknotes')
                ->color('success')
                ->visible(
                    $record->isActive() &&
                    $record->approved_by &&
                    (float)$record->outstanding_balance <= 0.01 &&
                    ($user?->isFinanceManager() || $user?->isSuperAdmin())
                )
                ->requiresConfirmation()
                ->action(function () use ($record) {
                    $record->forceFill(['status' => 'fully_paid'])->save();
                    Notification::make()->success()->title('Loan marked as fully paid.')->send();
                    redirect($this->getResource()::getUrl('view', ['record' => $record]));
                }),

            // ── Write Off ────────────────────────────────────────────────
            Action::make('write_off')
                ->label('Write Off')
                ->icon('heroicon-o-x-circle')
                ->color('danger')
                ->visible($record->isActive() && $user?->isSuperAdmin())
                ->form([
                    Textarea::make('reason')
                        ->label('Write-Off Reason')
                        ->required()
                        ->rows(3),
                ])
                ->requiresConfirmation()
                ->modalHeading('Write Off Loan')
                ->modalDescription('This action is irreversible. The outstanding balance will be written off.')
                ->action(function (array $data) use ($record) {
                    $record->forceFill([
                        'status' => 'written_off',
                        'notes'  => ($record->notes ? $record->notes . "\n\nWRITE-OFF: " : 'WRITE-OFF: ') . $data['reason'],
                    ])->save();
                    // Mark all pending installments as written off
                    $record->schedule()
                        ->whereIn('status', ['pending', 'partially_paid', 'overdue'])
                        ->update(['status' => 'overdue', 'notes' => 'Written off']);
                    Notification::make()->warning()->title('Loan written off.')->send();
                    redirect($this->getResource()::getUrl('view', ['record' => $record]));
                }),

            // ── View GL JE ───────────────────────────────────────────────
            Action::make('view_je')
                ->label("Disbursement JE {$record->journalEntry?->reference_number}")
                ->icon('heroicon-o-arrow-top-right-on-square')
                ->color('info')
                ->visible((bool) $record->journal_entry_id)
                ->url(fn () => \App\Filament\Resources\Finance\Journals\JournalEntryResource::getUrl(
                    'view', ['record' => $record->journal_entry_id]
                )),
        ];
    }

    public function getRelationManagers(): array
    {
        return [
            LoanScheduleRelationManager::class,
        ];
    }
}
