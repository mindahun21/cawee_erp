<?php

namespace App\Filament\Widgets\Finance;

use App\Traits\BelongsToModuleWidget;

use App\Models\Finance\FundTransfer;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget as BaseWidget;

class FinanceFundTransferStatusWidget extends BaseWidget
{
    use BelongsToModuleWidget;

    protected static ?int $sort = 7;
    protected int|string|array $columnSpan = 1;
    protected static ?string $heading = 'Outstanding HO → Field Fund Transfers';

    public function table(Table $table): Table
    {
        return $table
            ->query(
                FundTransfer::query()
                    ->whereIn('status', ['approved', 'remitted', 'confirmed'])
                    ->orderByDesc('transfer_date')
                    ->limit(10)
            )
            ->columns([
                TextColumn::make('transfer_number')
                    ->label('Ref')
                    ->badge()
                    ->color('primary')
                    ->fontFamily('mono'),

                TextColumn::make('transfer_date')
                    ->label('Date')
                    ->date('d M Y'),

                TextColumn::make('fromCostCenter.name')
                    ->label('From')
                    ->limit(15)
                    ->placeholder('—'),

                TextColumn::make('toCostCenter.name')
                    ->label('To')
                    ->limit(15)
                    ->placeholder('—'),

                TextColumn::make('amount')
                    ->label('Amount')
                    ->money('ETB'),

                TextColumn::make('status')
                    ->label('Status')
                    ->badge()
                    ->color(fn ($state) => match ($state) {
                        'approved'   => 'info',
                        'remitted'   => 'warning',
                        'confirmed'  => 'success',
                        'reconciled' => 'gray',
                        default      => 'gray',
                    })
                    ->formatStateUsing(fn ($state) => FundTransfer::statuses()[$state] ?? $state),
            ])
            ->paginated(false);
    }
}
