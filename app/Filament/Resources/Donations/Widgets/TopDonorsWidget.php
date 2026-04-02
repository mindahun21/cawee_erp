<?php

namespace App\Filament\Resources\Donations\Widgets;

use App\Models\Donation;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget as BaseWidget;
use Illuminate\Support\Facades\DB;

class TopDonorsWidget extends BaseWidget
{
    protected int | string | array $columnSpan = 'half';

    protected static ?string $heading = 'Top Donors';

    public function table(Table $table): Table
    {
        // ── Query design note ────────────────────────────────────────────────
        // MySQL's only_full_group_by mode rejects ORDER BY on non-aggregated
        // columns that are absent from GROUP BY.  Filament's TableWidget
        // appends "ORDER BY {model_table}.id ASC" as a secondary sort, which
        // references the raw donations.id — invalid in a GROUP BY query.
        //
        // Fix: wrap the aggregation in a subquery via fromSub().  The outer
        // query's logical table becomes the alias `top_donors`, so Filament
        // generates "ORDER BY top_donors.id ASC", which now resolves to the
        // MIN(id) aggregate selected in the inner query — perfectly valid.
        // ────────────────────────────────────────────────────────────────────

        $inner = Donation::query()
            ->selectRaw('
                MIN(id)      AS id,
                donor_id,
                SUM(amount)  AS total_donated,
                COUNT(*)     AS donation_count
            ')
            ->where('status', 'completed')
            ->groupBy('donor_id')
            ->orderByDesc('total_donated')
            ->limit(10);

        // Use DB::table() on the subquery so Filament has no Donation model
        // context and cannot append an invalid 'donations.id' secondary sort.
        $query = DB::table(
            DB::raw('(' . $inner->toSql() . ') as top_donors')
        )
            ->mergeBindings($inner->getQuery())
            ->select('*')
            ->orderByDesc('total_donated');

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

                Tables\Columns\TextColumn::make('total_donated')
                    ->label('Total Given (ETB)')
                    ->alignEnd()
                    ->numeric(decimalPlaces: 2)
                    ->fontFamily('mono'),
            ])
            ->defaultSort('total_donated', 'desc')
            ->paginated(false);
    }
}
