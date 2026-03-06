<?php

namespace App\Filament\Widgets\Procurement;

use App\Models\Procurement\Requisition;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget as BaseWidget;

class ProcurementRecentRequisitionsWidget extends BaseWidget
{
    protected static ?string $heading = 'Recent Requisitions';
    protected static ?int $sort = 5;
    protected int|string|array $columnSpan = 'full';

    public function table(Table $table): Table
    {
        return $table
            ->query(
                Requisition::query()->with('requester')->orderByDesc('created_at')->limit(8)
            )
            ->columns([
                TextColumn::make('requisition_number')->label('REQ #')->weight('semibold')->searchable(),
                TextColumn::make('requester.name')->label('Requested By')->searchable(),
                TextColumn::make('category')->badge()
                    ->color(fn ($state) => match ($state) {
                        'Goods' => 'info', 'Services' => 'primary',
                        'Works' => 'warning', 'Consultancy' => 'purple', default => 'gray',
                    }),
                TextColumn::make('estimated_total')->label('Est. Total')->numeric(2)->prefix('ETB '),
                TextColumn::make('required_by_date')->label('Required By')->date(),
                TextColumn::make('current_stage')->label('Stage')->badge()
                    ->color(fn ($state) => match (true) {
                        str_contains((string)$state, 'Approved') => 'success',
                        str_contains((string)$state, 'Rejected') => 'danger',
                        str_contains((string)$state, 'Awaiting') => 'warning',
                        default => 'gray',
                    }),
                TextColumn::make('overall_status')->label('Status')->badge()
                    ->color(fn ($state) => match ($state) {
                        'Approved', 'Converted to PO' => 'success',
                        'Rejected' => 'danger', 'Submitted' => 'warning', default => 'gray',
                    }),
            ])
            ->paginated(false);
    }
}
