<?php

namespace App\Filament\Clusters\Planning\Resources\Tasks\Schemas;

use App\Models\Task;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Schema;

class TaskInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextEntry::make('plan.title')
                    ->label('Plan'),
                TextEntry::make('title'),
                TextEntry::make('description')
                    ->placeholder('-')
                    ->columnSpanFull(),
                TextEntry::make('employee.id')
                    ->label('Employee')
                    ->placeholder('-'),
                TextEntry::make('deadline')
                    ->date()
                    ->placeholder('-'),
                TextEntry::make('priority'),
                TextEntry::make('status'),
                TextEntry::make('progress_percentage')
                    ->numeric(),
                TextEntry::make('attachments')
                    ->placeholder('-')
                    ->columnSpanFull(),
                TextEntry::make('created_at')
                    ->dateTime()
                    ->placeholder('-'),
                TextEntry::make('updated_at')
                    ->dateTime()
                    ->placeholder('-'),
                TextEntry::make('deleted_at')
                    ->dateTime()
                    ->visible(fn (Task $record): bool => $record->trashed()),
            ]);
    }
}
