<?php

namespace App\Filament\Widgets\Finance;

use App\Traits\BelongsToModuleWidget;

use App\Models\Finance\PettyCashFund;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget as BaseWidget;

class FinancePettyCashStatusWidget extends BaseWidget
{
    use BelongsToModuleWidget;

    protected static ?int $sort = 5;
    protected int|string|array $columnSpan = 1;
    protected static ?string $heading = 'Petty Cash Fund Status';

    public function table(Table $table): Table
    {
        return $table
            ->query(
                PettyCashFund::query()
                    ->whereIn('status', ['active', 'suspended'])
                    ->orderByRaw("CASE WHEN status = 'active' THEN 0 ELSE 1 END")
                    ->orderBy('fund_name')
            )
            ->columns([
                TextColumn::make('fund_code')
                    ->label('Code')
                    ->badge()
                    ->color('primary')
                    ->fontFamily('mono'),

                TextColumn::make('fund_name')
                    ->label('Fund')
                    ->limit(20)
                    ->searchable(),

                TextColumn::make('current_balance')
                    ->label('Balance')
                    ->money('ETB')
                    ->color(fn ($record) => $record->needsReplenishment() ? 'danger' : 'success'),

                TextColumn::make('utilizationPercent')
                    ->label('Used %')
                    ->suffix('%')
                    ->getStateUsing(fn ($record) => $record->utilizationPercent())
                    ->color(fn ($record) => match (true) {
                        $record->utilizationPercent() >= 80 => 'danger',
                        $record->utilizationPercent() >= 60 => 'warning',
                        default                             => 'success',
                    }),

                TextColumn::make('status')
                    ->label('Status')
                    ->badge()
                    ->color(fn ($state) => match ($state) {
                        'active'    => 'success',
                        'suspended' => 'warning',
                        'closed'    => 'gray',
                        default     => 'gray',
                    }),
            ])
            ->paginated(false);
    }
}
