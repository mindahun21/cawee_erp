<?php

namespace App\Filament\Resources\Finance\Bank\Pages;

use App\Filament\Resources\Finance\Bank\FundTransferResource;
use App\Models\Finance\BankAccount;
use App\Models\Finance\FinanceAuditLog;
use App\Models\Finance\FinanceSetting;
use App\Models\Finance\FundTransfer;
use App\Models\Finance\JournalEntry;
use App\Models\Finance\JournalEntryLine;
use App\Services\Finance\GeneralLedgerService;
use App\Services\Finance\JournalEntryService;
use Filament\Actions\Action;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ViewRecord;
use Illuminate\Support\Facades\DB;

class ViewFundTransfer extends ViewRecord
{
    protected static string $resource = FundTransferResource::class;

    protected function getHeaderActions(): array
    {
        /** @var FundTransfer $record */
        $record = $this->record;
        $user   = auth()->user();

        return [
            \Filament\Actions\EditAction::make()->visible($record->isDraft()),

            Action::make('approve')
                ->label('Approve')
                ->icon('heroicon-o-check-badge')
                ->color('success')
                ->visible($record->isDraft() && ($user->isFinanceManager() || $user->isSuperAdmin()))
                ->requiresConfirmation()
                ->action(function () use ($record, $user) {
                    DB::transaction(function () use ($record, $user) {
                        $record->forceFill([
                            'status'      => 'approved',
                            'approved_by' => $user->id,
                            'approved_at' => now(),
                        ])->save();
                        FinanceAuditLog::record('approve', $record,
                            ['status' => 'draft'], ['status' => 'approved', 'approved_by' => $user->id]
                        );
                    });
                    Notification::make()->success()->title('Fund transfer approved.')->send();
                    redirect($this->getResource()::getUrl('view', ['record' => $record]));
                }),

            Action::make('mark_remitted')
                ->label('Mark as Remitted (Sent)')
                ->icon('heroicon-o-paper-airplane')
                ->color('warning')
                ->visible($record->isApproved())
                ->requiresConfirmation()
                ->action(function () use ($record) {
                    $record->forceFill(['status' => 'remitted'])->save();
                    Notification::make()->success()->title('Transfer marked as remitted.')->send();
                    redirect($this->getResource()::getUrl('view', ['record' => $record]));
                }),

            Action::make('confirm_received')
                ->label('Confirm Receipt & Post GL')
                ->icon('heroicon-o-check-circle')
                ->color('primary')
                ->visible($record->isRemitted())
                ->form([
                    TextInput::make('confirmation_reference')
                        ->label('Bank / Receipt Reference')
                        ->required()
                        ->maxLength(80),
                ])
                ->modalHeading('Confirm Fund Transfer Receipt')
                ->modalDescription('Enter the bank confirmation reference. A GL journal entry will be auto-generated and posted.')
                ->action(function (array $data) use ($record, $user) {
                    try {
                        DB::transaction(function () use ($record, $user, $data) {
                            $jeService  = app(JournalEntryService::class);
                            $glService  = app(GeneralLedgerService::class);

                            $fromCoaId = $record->fromBankAccount?->chart_of_account_id
                                ?? FinanceSetting::get('default_bank_account_id');
                            $toCoaId   = $record->toBankAccount?->chart_of_account_id
                                ?? FinanceSetting::get('default_bank_account_id');

                            $amount = (float) $record->amount;

                            $je = JournalEntry::create([
                                'reference_number'      => $jeService->generateReference(now()->year),
                                'accounting_period_id'  => $record->accounting_period_id,
                                'transaction_date'      => now()->toDateString(),
                                'description'           => "Fund Transfer {$record->transfer_number} — {$record->purpose}",
                                'status'                => 'approved',
                                'source'                => 'fund_transfer',
                                'source_type'           => FundTransfer::class,
                                'source_id'             => $record->id,
                                'prepared_by'           => $user->id,
                                'approved_by'           => $user->id,
                                'currency_id'           => $record->currency_id,
                                'exchange_rate_to_base' => $record->exchange_rate_to_base,
                            ]);

                            JournalEntryLine::create([
                                'journal_entry_id' => $je->id,
                                'account_id'       => $toCoaId,
                                'debit'            => $amount,
                                'credit'           => 0,
                                'cost_center_id'   => $record->to_cost_center_id,
                                'donor_id'         => $record->donor_id,
                                'project_id'       => $record->project_id,
                                'narration'        => "Transfer received: {$record->toBankAccount?->account_name}",
                            ]);

                            JournalEntryLine::create([
                                'journal_entry_id' => $je->id,
                                'account_id'       => $fromCoaId,
                                'debit'            => 0,
                                'credit'           => $amount,
                                'cost_center_id'   => $record->from_cost_center_id,
                                'donor_id'         => $record->donor_id,
                                'project_id'       => $record->project_id,
                                'narration'        => "Transfer sent: {$record->fromBankAccount?->account_name}",
                            ]);

                            $je->load('lines');
                            $glService->postJournalEntry($je);
                            $je->forceFill(['status' => 'posted', 'posted_at' => now()])->save();

                            BankAccount::where('id', $record->from_bank_account_id)->decrement('current_balance', $amount);
                            BankAccount::where('id', $record->to_bank_account_id)->increment('current_balance', $amount);

                            $record->forceFill([
                                'status'                 => 'confirmed',
                                'confirmed_by'           => $user->id,
                                'confirmed_at'           => now(),
                                'confirmation_reference' => $data['confirmation_reference'],
                                'journal_entry_id'       => $je->id,
                            ])->save();

                            FinanceAuditLog::record('post', $record,
                                ['status' => 'remitted'],
                                ['status' => 'confirmed', 'je_ref' => $je->reference_number]
                            );
                        });

                        Notification::make()->success()->title('Transfer confirmed! GL entry posted and bank balances updated.')->send();
                        redirect($this->getResource()::getUrl('view', ['record' => $record]));
                    } catch (\Throwable $e) {
                        Notification::make()->danger()->title('Confirmation Failed')->body($e->getMessage())->send();
                    }
                }),
        ];
    }
}
