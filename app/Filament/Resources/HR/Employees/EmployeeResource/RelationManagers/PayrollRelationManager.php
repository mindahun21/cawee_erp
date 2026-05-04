<?php

namespace App\Filament\Resources\HR\Employees\EmployeeResource\RelationManagers;

use App\Models\Finance\PayrollSummary;
use App\Services\Finance\PayrollGLPostingService;
use Filament\Actions\CreateAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\BadgeColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Filament\Actions\Action;

class PayrollRelationManager extends RelationManager
{
    protected static string $relationship = 'payrollRecords';

    protected static ?string $title = 'Payroll History';

    public function form(Schema $schema): Schema
    {
        $moneyField = fn (string $name, string $label, bool $required = false) => TextInput::make($name)
            ->label($label)
            ->numeric()
            ->prefix('ETB')
            ->minValue(0)
            ->required($required);

        return $schema->components([
            TextInput::make('year')
                ->numeric()
                ->minValue(2000)
                ->maxValue(2100)
                ->required(),

            TextInput::make('month')
                ->numeric()
                ->minValue(1)
                ->maxValue(12)
                ->required(),

            $moneyField('basic_salary', 'Basic Salary', true),
            $moneyField('transport_allowance', 'Transport Allowance'),
            $moneyField('house_allowance', 'House Allowance'),
            $moneyField('communications_allowance', 'Communications Allowance'),
            $moneyField('overtime_allowance', 'Overtime Allowance'),
            $moneyField('incentive', 'Incentive'),
            $moneyField('other_allowances', 'Other Allowances'),

            // Total is computed automatically by the model observer
            TextInput::make('total_compensation')
                ->label('Total Compensation')
                ->numeric()
                ->prefix('ETB')
                ->disabled()
                ->dehydrated(false)
                ->helperText('Auto-computed on save'),
        ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('year')->sortable(),
                TextColumn::make('month')->sortable(),
                TextColumn::make('basic_salary')->numeric(decimalPlaces: 2)->prefix('ETB '),
                TextColumn::make('total_compensation')
                    ->label('Total')
                    ->numeric(decimalPlaces: 2)
                    ->prefix('ETB ')
                    ->weight('semibold'),
                // Show the Finance posting status linked to this HR payroll record
                TextColumn::make('financeStatus')
                    ->label('Finance Status')
                    ->badge()
                    ->state(function ($record) {
                        $summary = PayrollSummary::where('payroll_id', $record->id)->first();
                        if (! $summary) {
                            return 'not_synced';
                        }
                        return $summary->status;
                    })
                    ->color(fn ($state) => match ($state) {
                        'journal_posted' => 'success',
                        'draft'          => 'warning',
                        'not_synced'     => 'gray',
                        default          => 'gray',
                    })
                    ->formatStateUsing(fn ($state) => match ($state) {
                        'journal_posted' => '✅ GL Posted',
                        'draft'          => '📋 Draft in Finance',
                        'not_synced'     => '—',
                        default          => $state,
                    }),
            ])
            ->headerActions([CreateAction::make()])
            ->recordActions([
                EditAction::make(),
                // Push this HR payroll record to Finance as a PayrollSummary
                Action::make('push_to_finance')
                    ->label('Push to Finance')
                    ->icon('heroicon-o-arrow-right-circle')
                    ->color('success')
                    ->button()
                    ->requiresConfirmation()
                    ->modalHeading('Push to Finance Module')
                    ->modalDescription('This will create a draft Payroll Summary in Finance, pre-filled with calculated tax and pension amounts. The Finance team can then review and post it to the GL.')
                    ->visible(function ($record) {
                        // Only show if not yet synced to finance
                        return ! PayrollSummary::where('payroll_id', $record->id)->exists();
                    })
                    ->action(function ($record) {
                        try {
                            $summary = app(PayrollGLPostingService::class)->buildSummary($record);
                            Notification::make()
                                ->success()
                                ->title('Payroll synced to Finance')
                                ->body("Draft payroll summary created for {$record->employee?->full_name} ({$record->year}-{$record->month}).")
                                ->send();
                        } catch (\Throwable $e) {
                            Notification::make()
                                ->danger()
                                ->title('Sync failed')
                                ->body($e->getMessage())
                                ->send();
                        }
                    }),
                DeleteAction::make(),
            ])
            ->defaultSort('year', 'desc');
    }
}
