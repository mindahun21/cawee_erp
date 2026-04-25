<?php

namespace App\Filament\Clusters\Planning\Resources\PlanningKpis\Schemas;

use App\Models\PlanningKpi;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Schema;

class PlanningKpiInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextEntry::make('plan.title')
                    ->label('Plan'),
                TextEntry::make('indicator_name'),
                TextEntry::make('target_value')
                    ->numeric(),
                TextEntry::make('actual_value')
                    ->numeric(),
                TextEntry::make('unit')
                    ->placeholder('-'),
                TextEntry::make('department.name')
                    ->label('Department')
                    ->placeholder('-'),
                TextEntry::make('created_at')
                    ->dateTime()
                    ->placeholder('-'),
                TextEntry::make('updated_at')
                    ->dateTime()
                    ->placeholder('-'),
                TextEntry::make('deleted_at')
                    ->dateTime()
                    ->visible(fn (PlanningKpi $record): bool => $record->trashed()),
            ]);
    }
}
