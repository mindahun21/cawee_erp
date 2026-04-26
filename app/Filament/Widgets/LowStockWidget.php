<?php

namespace App\Filament\Widgets;

use App\Traits\BelongsToModuleWidget;

use App\Models\ItemWarehouse;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget as BaseWidget;

class LowStockWidget extends BaseWidget
{
    use BelongsToModuleWidget;

    protected static ?string $heading = 'Low Stock Alerts';

    protected int | string | array $columnSpan = 'full';

    protected static ?int $sort = 5;

    public function table(Table $table): Table
    {
        return $table
            ->query(
                ItemWarehouse::query()
                    ->whereRaw('quantity <= min_stock_value')
                    ->where('min_stock_value', '>', 0)
            )
            ->columns([
                Tables\Columns\TextColumn::make('item.name')
                    ->label('Item')
                    ->searchable(),
                Tables\Columns\TextColumn::make('warehouse.name')
                    ->label('Warehouse'),
                Tables\Columns\TextColumn::make('quantity')
                    ->label('Available')
                    ->badge()
                    ->color('danger'),
                Tables\Columns\TextColumn::make('min_stock_value')
                    ->label('Reorder Level')
                    ->color('gray'),
                Tables\Columns\TextColumn::make('item.unit.name')
                    ->label('Unit'),
            ])
            ->paginated(false);
    }
}
