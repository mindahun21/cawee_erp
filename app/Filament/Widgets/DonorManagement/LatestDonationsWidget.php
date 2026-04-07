<?php

namespace App\Filament\Widgets\DonorManagement;

use App\Models\Donation;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget as BaseWidget;

class LatestDonationsWidget extends BaseWidget
{
    protected static ?string $heading = 'Latest Contributions';
    protected ?string $view = 'filament.widgets.latest-donations-widget';

    protected int | string | array $columnSpan = 'half';

    public function table(Table $table): Table
    {
        return $table
            ->query(
                Donation::query()->latest('donation_date')->limit(5)
            )
            ->columns([
                Tables\Columns\TextColumn::make('donor.full_name')
                    ->label('Donor'),
                Tables\Columns\TextColumn::make('amount')
                    ->money('ETB'),
                Tables\Columns\TextColumn::make('status')
                    ->badge(),
                Tables\Columns\TextColumn::make('donation_date')
                    ->date()
                    ->label('Date'),
            ])
            ->paginated(false);
    }
}
