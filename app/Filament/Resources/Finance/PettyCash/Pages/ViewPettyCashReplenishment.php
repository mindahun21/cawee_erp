<?php

namespace App\Filament\Resources\Finance\PettyCash\Pages;

use App\Filament\Resources\Finance\PettyCash\PettyCashReplenishmentResource;
use App\Models\Finance\PettyCashReplenishment;
use App\Services\Finance\PettyCashService;
use Filament\Actions\Action;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ViewRecord;

class ViewPettyCashReplenishment extends ViewRecord
{
    protected static string $resource = PettyCashReplenishmentResource::class;

    protected function getHeaderActions(): array
    {
        /** @var PettyCashReplenishment $record */
        $record  = $this->record;
        $user    = auth()->user();
        $service = app(PettyCashService::class);

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
                        $service->submitReplenishment($record, $user);
                        $this->record->refresh();
                        Notification::make()->success()->title('Replenishment submitted for approval.')->send();
                    } catch (\Throwable $e) {
                        Notification::make()->danger()->title('Error')->body($e->getMessage())->send();
                    }
                }),

            Action::make('approve')
                ->label('Approve')
                ->icon('heroicon-o-check-badge')
                ->color('success')
                ->visible($record->isPending() && ($user->isFinanceManager() || $user->isSuperAdmin()))
                ->form([
                    TextInput::make('amount_approved')
                        ->label('Approved Amount')
                        ->required()
                        ->numeric()
                        ->default((float) $record->amount_requested)
                        ->extraInputAttributes(['class' => 'font-mono']),

                    Textarea::make('comments')
                        ->label('Approval Comments')
                        ->rows(2)
                        ->nullable(),
                ])
                ->action(function (array $data) use ($record, $service, $user) {
                    try {
                        $service->approveReplenishment(
                            $record,
                            $user,
                            (float) $data['amount_approved'],
                            $data['comments'] ?? ''
                        );
                        $this->record->refresh();
                        Notification::make()->success()->title("Replenishment approved for " . number_format($data['amount_approved'], 2) . " ETB.")->send();
                    } catch (\Throwable $e) {
                        Notification::make()->danger()->title('Error')->body($e->getMessage())->send();
                    }
                }),

            Action::make('disburse')
                ->label('Disburse & Post to GL')
                ->icon('heroicon-o-arrow-path-rounded-square')
                ->color('primary')
                ->visible($record->isApproved() && ($user->isFinanceManager() || $user->isSuperAdmin()))
                ->requiresConfirmation()
                ->modalHeading('Disburse Replenishment?')
                ->modalDescription('This will generate a journal entry (DR: Petty Cash Fund, CR: Bank), post it to the GL, and top up the fund balance. This cannot be undone.')
                ->action(function () use ($record, $service, $user) {
                    try {
                        $service->disburseReplenishment($record, $user);
                        $this->record->refresh();
                        Notification::make()->success()->title('Replenishment disbursed and posted to GL. Fund balance updated.')->send();
                    } catch (\Throwable $e) {
                        Notification::make()->danger()->title('Disbursement Failed')->body($e->getMessage())->send();
                    }
                }),
        ];
    }
}
