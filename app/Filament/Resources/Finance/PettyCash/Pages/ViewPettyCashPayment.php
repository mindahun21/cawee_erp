<?php

namespace App\Filament\Resources\Finance\PettyCash\Pages;

use App\Filament\Resources\Finance\PettyCash\PettyCashPaymentResource;
use App\Models\Finance\PettyCashPayment;
use App\Services\Finance\PettyCashService;
use Filament\Actions\Action;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ViewRecord;
use Filament\Forms\Components\Textarea;

class ViewPettyCashPayment extends ViewRecord
{
    protected static string $resource = PettyCashPaymentResource::class;

    protected function getHeaderActions(): array
    {
        /** @var PettyCashPayment $record */
        $record  = $this->record;
        $user    = auth()->user();
        $service = app(PettyCashService::class);

        return [
            \Filament\Actions\EditAction::make()->visible($record->isPending()),

            Action::make('approve')
                ->label('Approve Payment')
                ->icon('heroicon-o-check-badge')
                ->color('success')
                ->visible($record->isPending() && ($user->isFinanceManager() || $user->isSuperAdmin()))
                ->requiresConfirmation()
                ->modalHeading('Approve Petty Cash Payment?')
                ->modalDescription("This will deduct " . number_format($record->amount, 2) . " from the fund balance immediately.")
                ->action(function () use ($record, $service, $user) {
                    try {
                        $service->approvePayment($record, $user);
                        $this->record->refresh();
                        Notification::make()->success()->title('Payment approved. Fund balance updated.')->send();
                    } catch (\Throwable $e) {
                        Notification::make()->danger()->title('Error')->body($e->getMessage())->send();
                    }
                }),

            Action::make('reject')
                ->label('Reject')
                ->icon('heroicon-o-x-circle')
                ->color('danger')
                ->visible($record->isPending() && ($user->isFinanceManager() || $user->isSuperAdmin()))
                ->form([
                    Textarea::make('reason')
                        ->label('Rejection Reason')
                        ->required()
                        ->rows(2),
                ])
                ->action(function (array $data) use ($record, $service, $user) {
                    try {
                        $service->rejectPayment($record, $user, $data['reason']);
                        $this->record->refresh();
                        Notification::make()->warning()->title('Payment rejected.')->send();
                    } catch (\Throwable $e) {
                        Notification::make()->danger()->title('Error')->body($e->getMessage())->send();
                    }
                }),
        ];
    }
}
