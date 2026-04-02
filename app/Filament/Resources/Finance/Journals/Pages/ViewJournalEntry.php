<?php

namespace App\Filament\Resources\Finance\Journals\Pages;

use App\Filament\Resources\Finance\Journals\JournalEntryResource;
use App\Models\Finance\JournalEntry;
use App\Services\Finance\JournalEntryService;
use Filament\Actions\Action;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Textarea;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ViewRecord;

class ViewJournalEntry extends ViewRecord
{
    protected static string $resource = JournalEntryResource::class;

    protected function getHeaderActions(): array
    {
        return [

            // ── Edit (draft only) ─────────────────────────────────────
            EditAction::make()
                ->visible(fn () => $this->record->isEditable()),

            // ── Submit for Approval ───────────────────────────────────
            Action::make('submit')
                ->label('Submit for Approval')
                ->icon('heroicon-o-paper-airplane')
                ->color('info')
                ->visible(fn () => $this->record->isDraft())
                ->requiresConfirmation()
                ->modalHeading('Submit Journal Entry for Approval')
                ->modalDescription(
                    'This will forward the entry to a Finance Manager for review. ' .
                    'You will not be able to edit it until it is returned for revision.'
                )
                ->action(function () {
                    try {
                        app(JournalEntryService::class)->submit($this->record, auth()->user());

                        Notification::make()
                            ->title("JE [{$this->record->reference_number}] submitted for approval.")
                            ->info()
                            ->send();

                        $this->refreshFormData(['status']);
                    } catch (\Throwable $e) {
                        Notification::make()
                            ->title('Cannot submit')
                            ->body($e->getMessage())
                            ->danger()
                            ->send();
                    }
                }),

            // ── Approve ───────────────────────────────────────────────
            Action::make('approve')
                ->label('Approve')
                ->icon('heroicon-o-check-circle')
                ->color('success')
                ->visible(fn () =>
                    $this->record->isPendingApproval() &&
                    (auth()->user()?->isFinanceManager() || auth()->user()?->isSuperAdmin())
                )
                ->requiresConfirmation()
                ->modalHeading('Approve Journal Entry')
                ->modalDescription('The entry will be marked Approved and become eligible for posting to the General Ledger.')
                ->form([
                    Textarea::make('comments')
                        ->label('Approval Comments (optional)')
                        ->rows(3)
                        ->nullable(),
                ])
                ->action(function (array $data) {
                    try {
                        app(JournalEntryService::class)->approve(
                            $this->record,
                            auth()->user(),
                            $data['comments'] ?? ''
                        );

                        Notification::make()
                            ->title("JE [{$this->record->reference_number}] approved.")
                            ->success()
                            ->send();

                        $this->refreshFormData(['status', 'approved_by']);
                    } catch (\Throwable $e) {
                        Notification::make()
                            ->title('Approval failed')
                            ->body($e->getMessage())
                            ->danger()
                            ->send();
                    }
                }),

            // ── Post to General Ledger ────────────────────────────────
            Action::make('post')
                ->label('Post to GL')
                ->icon('heroicon-o-arrow-up-tray')
                ->color('primary')
                ->visible(fn () =>
                    $this->record->isApproved() &&
                    (auth()->user()?->isFinanceManager() || auth()->user()?->isSuperAdmin())
                )
                ->requiresConfirmation()
                ->modalHeading('Post Journal Entry to General Ledger')
                ->modalDescription(
                    'This action is irreversible. The entry will be written to the General Ledger ' .
                    'and its status will change to Posted. Any corrections will require a Reversal entry.'
                )
                ->modalSubmitActionLabel('Yes, Post to GL')
                ->action(function () {
                    try {
                        app(JournalEntryService::class)->post($this->record, auth()->user());

                        Notification::make()
                            ->title("JE [{$this->record->reference_number}] posted to General Ledger.")
                            ->success()
                            ->send();

                        $this->refreshFormData(['status', 'posted_at']);
                    } catch (\Throwable $e) {
                        Notification::make()
                            ->title('Posting failed')
                            ->body($e->getMessage())
                            ->danger()
                            ->send();
                    }
                }),

            // ── Return for Revision ───────────────────────────────────
            Action::make('return')
                ->label('Return for Revision')
                ->icon('heroicon-o-arrow-uturn-left')
                ->color('warning')
                ->visible(fn () =>
                    $this->record->isPendingApproval() &&
                    (auth()->user()?->isFinanceManager() || auth()->user()?->isSuperAdmin())
                )
                ->requiresConfirmation()
                ->modalHeading('Return Journal Entry for Revision')
                ->form([
                    Textarea::make('comments')
                        ->label('Reason for Returning')
                        ->required()
                        ->rows(3)
                        ->placeholder('Explain what needs to be corrected before resubmission…'),
                ])
                ->action(function (array $data) {
                    try {
                        app(JournalEntryService::class)->returnForRevision(
                            $this->record,
                            auth()->user(),
                            $data['comments'] ?? ''
                        );

                        Notification::make()
                            ->title("JE [{$this->record->reference_number}] returned to draft.")
                            ->warning()
                            ->send();

                        $this->refreshFormData(['status']);
                    } catch (\Throwable $e) {
                        Notification::make()
                            ->title('Cannot return')
                            ->body($e->getMessage())
                            ->danger()
                            ->send();
                    }
                }),

            // ── Reverse (posted entries only) ─────────────────────────
            Action::make('reverse')
                ->label('Reverse Entry')
                ->icon('heroicon-o-arrow-path')
                ->color('danger')
                ->visible(fn () =>
                    $this->record->isPosted() &&
                    (auth()->user()?->isFinanceManager() || auth()->user()?->isSuperAdmin())
                )
                ->requiresConfirmation()
                ->modalHeading('Reverse Posted Journal Entry')
                ->modalDescription(
                    'A mirror journal entry will be created with all debits and credits swapped, ' .
                    'then immediately posted to the General Ledger. ' .
                    'The original entry will be permanently marked as Reversed.'
                )
                ->modalSubmitActionLabel('Yes, Create Reversal')
                ->form([
                    Textarea::make('reason')
                        ->label('Reason for Reversal')
                        ->required()
                        ->rows(3)
                        ->placeholder('Explain why this posted entry needs to be reversed…'),
                ])
                ->action(function (array $data) {
                    try {
                        $reversal = app(JournalEntryService::class)->reverse(
                            $this->record,
                            auth()->user(),
                            $data['reason']
                        );

                        Notification::make()
                            ->title("Reversal [{$reversal->reference_number}] created and posted to GL.")
                            ->body("Original entry [{$this->record->reference_number}] is now marked Reversed.")
                            ->success()
                            ->send();

                        $this->refreshFormData(['status']);
                    } catch (\Throwable $e) {
                        Notification::make()
                            ->title('Reversal failed')
                            ->body($e->getMessage())
                            ->danger()
                            ->send();
                    }
                }),
        ];
    }
}
