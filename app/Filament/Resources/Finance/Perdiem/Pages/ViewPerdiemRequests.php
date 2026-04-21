<?php
namespace App\Filament\Resources\Finance\Perdiem\Pages;
use App\Filament\Resources\Finance\Perdiem\PerdiemRequestResource;
use App\Models\Finance\PerdiemRequest;
use App\Models\Finance\PerdiemSettlement;
use App\Services\Finance\PayrollGLPostingService;
use Filament\Actions\Action;
use Filament\Actions\EditAction;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ViewRecord;
class ViewPerdiemRequests extends ViewRecord {
    protected static string $resource = PerdiemRequestResource::class;
    protected function getHeaderActions(): array {
        /** @var PerdiemRequest $record */
        $record = $this->record;
        $user   = auth()->user();
        return [
            EditAction::make()->visible($record->isDraft()),

            Action::make('submit')->label('Submit for Approval')->icon('heroicon-o-paper-airplane')->color('warning')
                ->visible($record->isDraft())
                ->requiresConfirmation()
                ->action(function () use ($record) {
                    $record->forceFill(['status' => 'pending'])->save();
                    Notification::make()->success()->title('Per diem request submitted.')->send();
                    redirect($this->getResource()::getUrl('view', ['record' => $record]));
                }),

            Action::make('approve')->label('Approve')->icon('heroicon-o-check-badge')->color('success')
                ->visible($record->isPending() && ($user?->isFinanceManager() || $user?->isSuperAdmin()))
                ->requiresConfirmation()
                ->action(function () use ($record) {
                    $record->forceFill(['status' => 'approved', 'approved_by' => auth()->id(), 'approved_at' => now()])->save();
                    Notification::make()->success()->title('Approved.')->send();
                    redirect($this->getResource()::getUrl('view', ['record' => $record]));
                }),

            Action::make('reject')->label('Reject')->icon('heroicon-o-x-circle')->color('danger')
                ->visible($record->isPending() && ($user?->isFinanceManager() || $user?->isSuperAdmin()))
                ->requiresConfirmation()
                ->form([Textarea::make('reason')->label('Rejection Reason')->required()->rows(2)])
                ->action(function (array $data) use ($record) {
                    $record->forceFill(['status' => 'rejected', 'notes' => ($record->notes ? $record->notes . "\n" : '') . "Rejected: {$data['reason']}"])->save();
                    Notification::make()->danger()->title('Rejected.')->send();
                    redirect($this->getResource()::getUrl('view', ['record' => $record]));
                }),

            Action::make('settle')->label('Record Settlement')->icon('heroicon-o-document-check')->color('info')
                ->visible($record->isApproved() && !$record->settlement()->exists())
                ->form([
                    DatePicker::make('settlement_date')->label('Settlement Date')->required()->default(today()),
                    TextInput::make('actual_days')->label('Actual Days')->numeric()->required()->default($record->days_count),
                    TextInput::make('actual_amount')->label('Actual Amount')->numeric()->required()->default($record->total_requested),
                    TextInput::make('advance_paid')->label('Advance Already Paid')->numeric()->default($record->amount_advanced),
                    Textarea::make('notes')->label('Notes')->rows(2)->nullable(),
                ])
                ->action(function (array $data) use ($record) {
                    $balance = (float)$data['actual_amount'] - (float)$data['advance_paid'];
                    $settlement = PerdiemSettlement::create([
                        'perdiem_request_id' => $record->id,
                        'settlement_date'    => $data['settlement_date'],
                        'actual_days'        => $data['actual_days'],
                        'actual_amount'      => $data['actual_amount'],
                        'advance_paid'       => $data['advance_paid'],
                        'balance_to_recover' => $balance,
                        'status'             => 'draft',
                        'notes'              => $data['notes'] ?? null,
                    ]);
                    Notification::make()->success()->title('Settlement recorded. Post to GL to close.')->send();
                    redirect($this->getResource()::getUrl('view', ['record' => $record]));
                }),

            Action::make('post_settlement')->label('Post Settlement to GL')->icon('heroicon-o-arrow-up-circle')->color('success')
                ->visible(fn () => $record->isApproved() && ($record->settlement?->isDraft() ?? false) && ($user?->isFinanceManager() || $user?->isSuperAdmin()))
                ->requiresConfirmation()
                ->modalDescription('This will post the per-diem settlement journal entry and close the request.')
                ->action(function () use ($record) {
                    try {
                        app(PayrollGLPostingService::class)->postPerdiemSettlement($record->settlement);
                        Notification::make()->success()->title('Per diem settlement posted to GL.')->send();
                    } catch (\Throwable $e) {
                        Notification::make()->danger()->title('Error')->body($e->getMessage())->send();
                    }
                    redirect($this->getResource()::getUrl('view', ['record' => $record]));
                }),
        ];
    }
}
