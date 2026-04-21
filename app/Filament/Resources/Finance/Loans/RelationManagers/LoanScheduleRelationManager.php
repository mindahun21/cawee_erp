<?php

namespace App\Filament\Resources\Finance\Loans\RelationManagers;

use App\Models\Finance\BankAccount;
use App\Models\Finance\LoanRepaymentSchedule;
use App\Services\Finance\PaymentRequisitionService;
use Filament\Actions\Action;
use Filament\Forms\Components\DatePicker;
use Filament\Schemas\Components\Section;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class LoanScheduleRelationManager extends RelationManager
{
    protected static string $relationship = 'schedule';
    protected static ?string $title = 'Repayment Schedule';

    public function isReadOnly(): bool
    {
        return false;
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('installment_number')
            ->columns([
                TextColumn::make('installment_number')
                    ->label('#')
                    ->badge()
                    ->color('gray')
                    ->sortable(),

                TextColumn::make('due_date')
                    ->label('Due Date')
                    ->date()
                    ->sortable()
                    ->color(fn (LoanRepaymentSchedule $r) =>
                        $r->isOverdue() ? 'danger' : null
                    ),

                TextColumn::make('principal_amount')
                    ->label('Principal')
                    ->numeric(decimalPlaces: 2)
                    ->alignEnd()
                    ->fontFamily('mono'),

                TextColumn::make('interest_amount')
                    ->label('Interest')
                    ->numeric(decimalPlaces: 2)
                    ->alignEnd()
                    ->fontFamily('mono'),

                TextColumn::make('total_due')
                    ->label('Total Due')
                    ->numeric(decimalPlaces: 2)
                    ->alignEnd()
                    ->fontFamily('mono')
                    ->weight('semibold'),

                TextColumn::make('paid_amount')
                    ->label('Paid')
                    ->numeric(decimalPlaces: 2)
                    ->alignEnd()
                    ->fontFamily('mono')
                    ->color(fn (LoanRepaymentSchedule $r) =>
                        (float)$r->paid_amount > 0 ? 'success' : null
                    ),

                TextColumn::make('balance_due')
                    ->label('Balance Due')
                    ->numeric(decimalPlaces: 2)
                    ->alignEnd()
                    ->fontFamily('mono')
                    ->color('danger')
                    ->state(fn (LoanRepaymentSchedule $r) => $r->balanceDue()),

                TextColumn::make('paid_date')
                    ->label('Paid On')
                    ->date()
                    ->placeholder('—'),

                TextColumn::make('status')
                    ->label('Status')
                    ->badge()
                    ->color(fn (string $state) => match($state) {
                        'paid'           => 'success',
                        'partially_paid' => 'warning',
                        'overdue'        => 'danger',
                        'pending'        => 'gray',
                        default          => 'gray',
                    }),

                TextColumn::make('journalEntry.reference_number')
                    ->label('JE Ref')
                    ->badge()
                    ->color('info')
                    ->placeholder('—'),
            ])
            ->recordActions([
                Action::make('record_payment')
                    ->label('Record Payment')
                    ->icon('heroicon-o-banknotes')
                    ->color('success')
                    ->button()
                    ->visible(fn (LoanRepaymentSchedule $r) => ! $r->isPaid() && $r->loan->isActive())
                    ->form(function (LoanRepaymentSchedule $record): array {
                        return [
                            Section::make('Payment Details')
                                ->icon('heroicon-o-banknotes')
                                ->schema([
                                    TextInput::make('amount')
                                        ->label('Amount Being Paid')
                                        ->numeric()
                                        ->required()
                                        ->default(fn () => number_format($record->balanceDue(), 2, '.', ''))
                                        ->helperText("Balance due: " . number_format($record->balanceDue(), 2) . " | Total due: " . number_format($record->total_due, 2)),

                                    Select::make('bank_account_id')
                                        ->label('Paid From Bank Account')
                                        ->options(fn () => BankAccount::where('is_active', true)
                                            ->orderBy('account_name')
                                            ->get()
                                            ->mapWithKeys(fn ($b) => [$b->id => "{$b->account_name} ({$b->bank_name})"]))
                                        ->required()
                                        ->native(false)
                                        ->searchable(),

                                    DatePicker::make('paid_date')
                                        ->label('Payment Date')
                                        ->default(today())
                                        ->required(),

                                    Textarea::make('notes')
                                        ->label('Notes / Reference')
                                        ->rows(2)
                                        ->placeholder('Cheque number, transfer ref, etc.'),
                                ])->columns(2),
                        ];
                    })
                    ->action(function (LoanRepaymentSchedule $record, array $data): void {
                        try {
                            app(PaymentRequisitionService::class)->recordRepayment(
                                $record,
                                (float) $data['amount'],
                                (int) $data['bank_account_id'],
                                $data['notes'] ?? ''
                            );

                            Notification::make()
                                ->success()
                                ->title('Payment recorded')
                                ->body("Installment #{$record->installment_number} payment posted to GL.")
                                ->send();
                        } catch (\Throwable $e) {
                            Notification::make()
                                ->danger()
                                ->title('Payment failed')
                                ->body($e->getMessage())
                                ->send();
                        }
                    }),
            ])
            ->defaultSort('installment_number')
            ->paginated(false);
    }
}
