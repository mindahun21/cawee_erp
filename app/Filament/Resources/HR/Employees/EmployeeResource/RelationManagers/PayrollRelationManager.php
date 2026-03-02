<?php

namespace App\Filament\Resources\HR\Employees\EmployeeResource\RelationManagers;

use Filament\Forms\Components\TextInput;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Schema;
use Filament\Actions\CreateAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

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
            ])
            ->headerActions([CreateAction::make()])
            ->recordActions([EditAction::make(), DeleteAction::make()])
            ->defaultSort('year', 'desc');
    }
}
