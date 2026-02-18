<?php

namespace App\Filament\Resources\Donations\Widgets;

use App\Services\DonationService;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget as BaseWidget;
use Illuminate\Database\Eloquent\Model;

class TopDonorsWidget extends BaseWidget
{
    protected int | string | array $columnSpan = 'half';
    
    protected static ?string $heading = 'Top Donors';

    public function table(Table $table): Table
    {
        return $table
            ->query(
                \App\Models\Donation::query()
                    ->selectRaw('donor_id as id, donor_id, COUNT(*) as donation_count, SUM(amount) as total_donated')
                    ->where('status', 'completed')
                    ->groupBy('donor_id')
                    ->orderByDesc('total_donated')
                    ->limit(5)
            )
            ->columns([
                Tables\Columns\TextColumn::make('donor.full_name')
                    ->label('Donor'),
                Tables\Columns\TextColumn::make('donation_count')
                    ->label('Donations')
                    ->alignCenter(),
                Tables\Columns\TextColumn::make('total_donated')
                    ->label('Total Given')
                    ->money(fn ($record) => $record->currency?->code ?? 'USD') // Note: currency might be mixed, relying on default or first relation
                    ->prefix('Total: '),
            ])
            ->paginated(false);
    }
}
