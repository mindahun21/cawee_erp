<?php

namespace App\Filament\Widgets\HR;

use App\Models\Employee;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget as BaseWidget;

class BirthdaysThisMonthWidget extends BaseWidget
{
    protected static ?string $heading = '🎂 Birthdays This Month';

    protected static ?int $sort = 6;

    protected int | string | array $columnSpan = 1;

    public function table(Table $table): Table
    {
        return $table
            ->query(
                Employee::query()
                    ->whereNull('date_resigned')
                    ->whereNotNull('date_of_birth')
                    ->whereMonth('date_of_birth', now()->month)
                    ->orderByRaw('CAST(STRFTIME("%d", date_of_birth) AS INTEGER)')
            )
            ->columns([
                TextColumn::make('full_name')
                    ->label('Employee')
                    ->weight('semibold')
                    ->searchable(['first_name', 'last_name']),

                TextColumn::make('date_of_birth')
                    ->label('Birthday')
                    ->formatStateUsing(fn ($state) => $state?->format('d M'))
                    ->badge()
                    ->color('info'),

                TextColumn::make('department.name')
                    ->label('Department')
                    ->badge()
                    ->color('gray'),
            ])
            ->paginated(false);
    }
}
