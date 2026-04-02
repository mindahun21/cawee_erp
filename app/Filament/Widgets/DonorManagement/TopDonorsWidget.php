<?php

namespace App\Filament\Widgets\DonorManagement;

use App\Models\Donation;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget as BaseWidget;
use Illuminate\Support\Facades\DB;

class TopDonorsWidget extends BaseWidget
{
    protected static ?string $heading = 'Top Donors (All Time)';

    protected int | string | array $columnSpan = 'half';

    public function table(Table $table): Table
    {
        // ── Query design note ────────────────────────────────────────────────
        // MySQL's only_full_group_by mode rejects ORDER BY on non-aggregated
        // columns that are not in GROUP BY.  Filament's TableWidget appends
        // "ORDER BY {model_table}.id ASC" as a secondary sort key, which
        // references the raw donations.id column — not valid in a GROUP BY query.
        //
        // Fix: wrap the aggregation in a subquery (fromSub).  The outer query's
        // "table" becomes the alias `top_donors`, so Filament generates
        // "ORDER BY top_donors.id ASC", which now references the MIN(id)
        // aggregate column selected in the inner query — fully valid in MySQL.
        // ────────────────────────────────────────────────────────────────────

        $inner = Donation::query()
            ->selectRaw('
                MIN(id)          AS id,
                donor_id,
                SUM(amount)      AS total_amount,
                COUNT(*)         AS donation_count
            ')
            ->where('status', 'completed')
            ->groupBy('donor_id')
            ->orderByDesc('total_amount')
            ->limit(10);

        // Use fromSub() on an Eloquent query so Filament still gets an Eloquent
        // Builder, but bound to our pre-aggregated 'top_donors' alias.
        $query = Donation::query()
            ->fromSub($inner, 'top_donors')
            ->select('*')
            ->orderByDesc('total_amount');

        return $table
            ->query($query)
            ->columns([
                Tables\Columns\TextColumn::make('donor.full_name')
                    ->label('Donor')
                    ->weight('semibold')
                    ->searchable(false),

                Tables\Columns\TextColumn::make('donation_count')
                    ->label('Donations')
                    ->alignCenter()
                    ->numeric(),

                Tables\Columns\TextColumn::make('total_amount')
                    ->label('Total (ETB)')
                    ->alignEnd()
                    ->numeric(decimalPlaces: 2)
                    ->fontFamily('mono'),
            ])
            ->defaultSort('total_amount', 'desc')
            ->paginated(false);
    }
}
