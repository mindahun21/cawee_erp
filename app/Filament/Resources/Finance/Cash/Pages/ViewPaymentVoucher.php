<?php

namespace App\Filament\Resources\Finance\Cash\Pages;

use App\Filament\Resources\Finance\Cash\PaymentVoucherResource;
use App\Models\Finance\PaymentVoucher;
use App\Services\Finance\VoucherService;
use Filament\Actions\Action;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ViewRecord;

class ViewPaymentVoucher extends ViewRecord
{
    protected static string $resource = PaymentVoucherResource::class;

    protected function getHeaderActions(): array
    {
        /** @var PaymentVoucher $record */
        $record  = $this->record;
        $user    = auth()->user();
        $service = app(VoucherService::class);

        return [
            \Filament\Actions\EditAction::make()->visible($record->isDraft()),

            Action::make('submit')
                ->label('Submit for Approval')
                ->icon('heroicon-o-paper-airplane')
                ->color('warning')
                ->visible($record->isDraft())
                ->requiresConfirmation()
                ->action(function () use ($record, $service, $user) {
                    try {
                        $service->submitPv($record, $user);
                        $this->record->refresh();
                        Notification::make()->success()->title('PV submitted for approval.')->send();
                    } catch (\Throwable $e) {
                        Notification::make()->danger()->title('Error')->body($e->getMessage())->send();
                    }
                }),

            Action::make('approve')
                ->label('Approve')
                ->icon('heroicon-o-check-badge')
                ->color('success')
                ->visible($record->isPendingApproval() && ($user->isFinanceManager() || $user->isSuperAdmin()))
                ->requiresConfirmation()
                ->action(function () use ($record, $service, $user) {
                    try {
                        $service->approvePv($record, $user);
                        $this->record->refresh();
                        Notification::make()->success()->title('PV approved.')->send();
                    } catch (\Throwable $e) {
                        Notification::make()->danger()->title('Error')->body($e->getMessage())->send();
                    }
                }),

            Action::make('post')
                ->label('Post to GL')
                ->icon('heroicon-o-arrow-up-tray')
                ->color('primary')
                ->visible($record->isApproved() && ($user->isFinanceManager() || $user->isSuperAdmin()))
                ->requiresConfirmation()
                ->modalHeading('Post PV to General Ledger?')
                ->modalDescription('This will generate a journal entry and deduct from the bank balance. This action cannot be undone.')
                ->action(function () use ($record, $service, $user) {
                    try {
                        $service->postPv($record, $user);
                        $this->record->refresh();
                        Notification::make()->success()->title('PV posted to GL.')->send();
                    } catch (\Throwable $e) {
                        Notification::make()->danger()->title('Posting Failed')->body($e->getMessage())->send();
                    }
                }),
        ];
    }
}
