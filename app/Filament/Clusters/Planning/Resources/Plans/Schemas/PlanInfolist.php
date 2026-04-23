<?php

namespace App\Filament\Clusters\Planning\Resources\Plans\Schemas;

use App\Models\Plan;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Schema;

class PlanInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextEntry::make('title'),
                TextEntry::make('description')
                    ->placeholder('-')
                    ->columnSpanFull(),
                TextEntry::make('objectives')
                    ->placeholder('-')
                    ->columnSpanFull(),
                TextEntry::make('outcomes')
                    ->placeholder('-')
                    ->columnSpanFull(),
                TextEntry::make('type'),
                TextEntry::make('parent.title')
                    ->label('Parent')
                    ->placeholder('-'),
                TextEntry::make('department.name')
                    ->label('Department')
                    ->placeholder('-'),
                TextEntry::make('project.id')
                    ->label('Project')
                    ->placeholder('-'),
                TextEntry::make('budget.title')
                    ->label('Budget')
                    ->placeholder('-'),
                TextEntry::make('start_date')
                    ->date()
                    ->placeholder('-'),
                TextEntry::make('end_date')
                    ->date()
                    ->placeholder('-'),
                TextEntry::make('attachments')
                    ->placeholder('-')
                    ->columnSpanFull(),
                TextEntry::make('progress_percentage')
                    ->numeric(),
                TextEntry::make('created_at')
                    ->dateTime()
                    ->placeholder('-'),
                TextEntry::make('updated_at')
                    ->dateTime()
                    ->placeholder('-'),
                TextEntry::make('deleted_at')
                    ->dateTime()
                    ->visible(fn (Plan $record): bool => $record->trashed()),
            ]);
    }
}
