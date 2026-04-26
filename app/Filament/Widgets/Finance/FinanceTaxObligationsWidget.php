<?php

namespace App\Filament\Widgets\Finance;

use App\Traits\BelongsToModuleWidget;

use App\Models\Finance\DeclaredTax;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget as BaseWidget;

class FinanceTaxObligationsWidget extends BaseWidget
{
    use BelongsToModuleWidget;

    protected static ?int $sort = 8;
    protected int|string|array $columnSpan = 1;
    protected static ?string $heading = 'Tax Obligations & Upcoming Declarations';

    public function table(Table $table): Table
    {
        return $table
            ->query(
                DeclaredTax::query()
                    ->whereIn('status', ['draft', 'filed'])
                    ->orderByDesc('declaration_date')
                    ->limit(10)
            )
            ->columns([
                TextColumn::make('taxType.name')
                    ->label('Tax Type')
                    ->badge()
                    ->color('warning'),

                TextColumn::make('declaration_period')
                    ->label('Period')
                    ->fontFamily('mono'),

                TextColumn::make('tax_payable')
                    ->label('Payable')
                    ->money('ETB')
                    ->color('danger'),

                TextColumn::make('paid_amount')
                    ->label('Paid')
                    ->money('ETB')
                    ->color('success'),

                TextColumn::make('outstanding')
                    ->label('Outstanding')
                    ->getStateUsing(fn ($record) => max(0, (float)$record->tax_payable - (float)$record->paid_amount))
                    ->formatStateUsing(fn ($state) => 'ETB ' . number_format((float)$state, 2))
                    ->color(fn ($record) => ((float)$record->tax_payable - (float)$record->paid_amount) > 0 ? 'danger' : 'success'),

                TextColumn::make('status')
                    ->label('Status')
                    ->badge()
                    ->color(fn ($state) => match ($state) {
                        'draft'  => 'gray',
                        'filed'  => 'warning',
                        'paid'   => 'success',
                        default  => 'gray',
                    }),
            ])
            ->paginated(false);
    }
}
