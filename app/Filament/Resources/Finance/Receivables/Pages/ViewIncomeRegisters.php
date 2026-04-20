<?php

namespace App\Filament\Resources\Finance\Receivables\Pages;

use App\Filament\Resources\Finance\Receivables\IncomeRegisterResource;
use App\Models\Finance\IncomeRegister;
use App\Services\Finance\PaymentRequisitionService;
use Filament\Actions\Action;
use Filament\Actions\EditAction;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ViewRecord;

class ViewIncomeRegisters extends ViewRecord
{
    protected static string $resource = IncomeRegisterResource::class;

    protected function getHeaderActions(): array
    {
        /** @var IncomeRegister $record */
        $record = $this->record;
        $user   = auth()->user();

        return [
            EditAction::make()->visible($record->isDraft()),

            // ── Confirm ──────────────────────────────────────────────────
            Action::make('confirm')
                ->label('Confirm Income')
                ->icon('heroicon-o-check-circle')
                ->color('success')
                ->visible($record->isDraft())
                ->requiresConfirmation()
                ->modalHeading('Confirm Income Register')
                ->modalDescription('Confirming will lock the income record. You can then post it to the General Ledger.')
                ->action(function () use ($record) {
                    $record->forceFill([
                        'status'       => 'confirmed',
                        'confirmed_by' => auth()->id(),
                        'confirmed_at' => now(),
                    ])->save();
                    Notification::make()->success()->title('Income confirmed.')->send();
                    redirect($this->getResource()::getUrl('view', ['record' => $record]));
                }),

            // ── Post to GL ───────────────────────────────────────────────
            Action::make('post_gl')
                ->label('Post to General Ledger')
                ->icon('heroicon-o-bolt')
                ->color('primary')
                ->visible(
                    $record->isConfirmed() &&
                    ($user?->isFinanceManager() || $user?->isSuperAdmin())
                )
                ->requiresConfirmation()
                ->modalHeading('Post Income to General Ledger')
                ->modalDescription(
                    "This will create a double-entry journal:\n" .
                    "  DR: Bank / AR Account\n" .
                    "  CR: " . ucfirst(str_replace('_', ' ', $record->income_type)) . " Income Account\n\n" .
                    "Amount: " . number_format($record->amount_in_base ?: $record->amount, 2) . " ETB"
                )
                ->action(function () use ($record) {
                    try {
                        $je = app(PaymentRequisitionService::class)->postIncomeRegister($record);
                        Notification::make()
                            ->success()
                            ->title('Posted to GL')
                            ->body("Journal Entry {$je->reference_number} created and posted.")
                            ->send();
                        redirect($this->getResource()::getUrl('view', ['record' => $record]));
                    } catch (\Throwable $e) {
                        Notification::make()->danger()->title('GL Posting Failed')->body($e->getMessage())->send();
                    }
                }),

            // ── View JE ──────────────────────────────────────────────────
            Action::make('view_je')
                ->label("Journal Entry {$record->journalEntry?->reference_number}")
                ->icon('heroicon-o-arrow-top-right-on-square')
                ->color('info')
                ->visible($record->isPosted() && (bool) $record->journal_entry_id)
                ->url(fn () => \App\Filament\Resources\Finance\Journals\JournalEntryResource::getUrl(
                    'view', ['record' => $record->journal_entry_id]
                )),
        ];
    }
}
