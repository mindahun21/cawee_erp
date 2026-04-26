<?php

namespace App\Filament\Widgets;

use App\Traits\BelongsToModuleWidget;

use App\Models\Maintenance;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget as BaseWidget;

class MaintenanceAlertsWidget extends BaseWidget
{
    use BelongsToModuleWidget;

    protected static ?string $heading = 'Critical Maintenance Alerts';

    protected int | string | array $columnSpan = 'full';

    protected static ?int $sort = 6;

    public function table(Table $table): Table
    {
        return $table
            ->query(
                Maintenance::query()
                    ->whereIn('priority', ['Urgent', 'High'])
                    ->whereNotIn('status', ['Completed', 'Cancelled'])
            )
            ->columns([
                Tables\Columns\TextColumn::make('asset.name')
                    ->label('Asset')
                    ->searchable(),
                Tables\Columns\TextColumn::make('priority')
                    ->badge()
                    ->color(fn ($state) => match ($state) {
                        'Urgent' => 'danger',
                        'High' => 'warning',
                        'Normal' => 'info',
                        'Low' => 'gray',
                        default => 'gray',
                    }),
                Tables\Columns\TextColumn::make('status')
                    ->badge(),
                Tables\Columns\TextColumn::make('maintenance_date')
                    ->label('Scheduled Date')
                    ->date(),
                Tables\Columns\TextColumn::make('performedBy.first_name')
                    ->label('Assigned To'),
            ])
            ->paginated(false);
    }
}
