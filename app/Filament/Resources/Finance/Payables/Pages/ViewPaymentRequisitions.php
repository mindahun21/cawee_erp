<?php

namespace App\Filament\Resources\Finance\Payables\Pages;

use App\Filament\Resources\Finance\Payables\PaymentRequisitionResource;
use App\Models\Finance\ApprovalHistory;
use App\Models\Finance\BankAccount;
use App\Models\Finance\PaymentRequisition;
use App\Services\Finance\PaymentRequisitionService;
use Filament\Actions\Action;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ViewRecord;

class ViewPaymentRequisitions extends ViewRecord
{
    protected static string $resource = PaymentRequisitionResource::class;

    protected function getHeaderActions(): array
    {
        /** @var PaymentRequisition $record */
        $record = $this->record;
        $user   = auth()->user();

        return [
            // ── Edit (draft only) ────────────────────────────────────────
            EditAction::make()
                ->visible($record->isDraft()),

            // ── Submit for Approval ──────────────────────────────────────
            Action::make('submit')
                ->label('Submit for Approval')
                ->icon('heroicon-o-paper-airplane')
                ->color('warning')
                ->visible($record->isDraft())
                ->requiresConfirmation()
                ->modalHeading('Submit Payment Requisition')
                ->modalDescription('Are you sure you want to submit this PR for Finance Manager approval?')
                ->action(function () use ($record) {
                    $prev = $record->status;
                    $record->forceFill(['status' => 'pending_approval'])->save();
                    ApprovalHistory::log(
                        $record, 'forwarded', 'Finance Officer Submission', 1,
                        $prev, 'pending_approval'
                    );
                    Notification::make()->success()->title('PR submitted for approval.')->send();
                    redirect($this->getResource()::getUrl('view', ['record' => $record]));
                }),

            // ── Approve ──────────────────────────────────────────────────
            Action::make('approve')
                ->label('Approve')
                ->icon('heroicon-o-check-badge')
                ->color('success')
                ->visible(
                    $record->isPendingApproval() &&
                    ($user?->isFinanceManager() || $user?->isSuperAdmin())
                )
                ->form([
                    Textarea::make('comments')
                        ->label('Approval Comments')
                        ->rows(3)
                        ->placeholder('Add any notes for the approval record…'),
                ])
                ->action(function (array $data) use ($record) {
                    $prev = $record->status;
                    $record->forceFill([
                        'status'      => 'approved',
                        'approved_by' => auth()->id(),
                        'approved_at' => now(),
                    ])->save();
                    ApprovalHistory::log(
                        $record, 'approved', 'Finance Manager Approval', 2,
                        $prev, 'approved', $data['comments'] ?? null
                    );
                    Notification::make()->success()->title('PR approved.')->send();
                    redirect($this->getResource()::getUrl('view', ['record' => $record]));
                }),

            // ── Reject ───────────────────────────────────────────────────
            Action::make('reject')
                ->label('Reject')
                ->icon('heroicon-o-x-circle')
                ->color('danger')
                ->visible(
                    $record->isPendingApproval() &&
                    ($user?->isFinanceManager() || $user?->isSuperAdmin())
                )
                ->form([
                    Textarea::make('comments')
                        ->label('Rejection Reason')
                        ->required()
                        ->rows(3)
                        ->placeholder('State the reason for rejection…'),
                ])
                ->action(function (array $data) use ($record) {
                    $prev = $record->status;
                    $record->forceFill(['status' => 'rejected'])->save();
                    ApprovalHistory::log(
                        $record, 'rejected', 'Finance Manager Rejection', 2,
                        $prev, 'rejected', $data['comments']
                    );
                    Notification::make()->danger()->title('PR rejected.')->send();
                    redirect($this->getResource()::getUrl('view', ['record' => $record]));
                }),

            // ── Return for Revision ──────────────────────────────────────
            Action::make('return')
                ->label('Return for Revision')
                ->icon('heroicon-o-arrow-uturn-left')
                ->color('gray')
                ->visible(
                    $record->isPendingApproval() &&
                    ($user?->isFinanceManager() || $user?->isSuperAdmin())
                )
                ->form([
                    Textarea::make('comments')
                        ->label('Reason for Return')
                        ->required()
                        ->rows(3),
                ])
                ->action(function (array $data) use ($record) {
                    $prev = $record->status;
                    $record->forceFill(['status' => 'draft'])->save();
                    ApprovalHistory::log(
                        $record, 'returned', 'Returned for Revision', 2,
                        $prev, 'draft', $data['comments']
                    );
                    Notification::make()->warning()->title('PR returned for revision.')->send();
                    redirect($this->getResource()::getUrl('view', ['record' => $record]));
                }),

            // ── Convert to Payment Voucher ───────────────────────────────
            Action::make('convert_to_pv')
                ->label('Create Payment Voucher')
                ->icon('heroicon-o-document-plus')
                ->color('primary')
                ->visible($record->isApproved() && ! $record->payment_voucher_id)
                ->form([
                    Select::make('bank_account_id')
                        ->label('Payment Bank Account')
                        ->options(fn () => BankAccount::where('is_active', true)
                            ->orderBy('account_name')
                            ->get()
                            ->mapWithKeys(fn ($b) => [
                                $b->id => "{$b->account_name} ({$b->bank_name}) — " .
                                    number_format($b->current_balance, 2),
                            ])
                        )
                        ->required()
                        ->native(false)
                        ->searchable()
                        ->helperText('Select the bank account from which payment will be made.'),
                ])
                ->modalHeading('Create Payment Voucher from PR')
                ->modalDescription("This will create a draft Payment Voucher pre-filled with all data from {$record->pr_number}. You can then complete and post it.")
                ->requiresConfirmation()
                ->action(function (array $data) use ($record) {
                    try {
                        $pv = app(PaymentRequisitionService::class)
                            ->convertToPv($record, (int) $data['bank_account_id']);

                        Notification::make()
                            ->success()
                            ->title('Payment Voucher created')
                            ->body("PV {$pv->pv_number} created successfully. Review and post it.")
                            ->send();

                        redirect($this->getResource()::getUrl('view', ['record' => $record]));
                    } catch (\Throwable $e) {
                        Notification::make()->danger()->title('Error')->body($e->getMessage())->send();
                    }
                }),

            // ── Linked PV Badge ──────────────────────────────────────────
            Action::make('view_pv')
                ->label("View PV #{$record->payment_voucher_id}")
                ->icon('heroicon-o-arrow-top-right-on-square')
                ->color('info')
                ->visible((bool) $record->payment_voucher_id)
                ->url(fn () => \App\Filament\Resources\Finance\Cash\PaymentVoucherResource::getUrl(
                    'view', ['record' => $record->payment_voucher_id]
                )),
        ];
    }
}
