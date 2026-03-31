<?php

namespace App\Filament\Widgets\DonorManagement;

use App\Models\Donation;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget as BaseWidget;

class TopDonorsWidget extends BaseWidget
{
    protected static ?string $heading = 'Top Donors (All Time)';

    protected int | string | array $columnSpan = 'half';

    public function table(Table $table): Table
    {
        return $table
            ->query(
                Donation::query()
                    ->selectRaw('donor_id, SUM(amount) as total_amount, COUNT(*) as donation_count')
                    ->where('status', 'completed')
                    ->groupBy('donor_id')
                    ->orderByDesc('total_amount')
                    ->limit(5)
            )
            ->columns([
                Tables\Columns\TextColumn::make('donor.full_name')
                    ->label('Donor')
                    ->weight('semibold'),
                Tables\Columns\TextColumn::make('donation_count')
                    ->label('Count')
                    ->alignCenter(),
                Tables\Columns\TextColumn::make('total_amount')
                    ->label('Total (ETB)')
                    ->money('ETB'),
            ])
            ->paginated(false);
    }
}
