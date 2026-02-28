<?php

namespace App\Filament\Resources\ME\IndicatorResource\RelationManagers;

use Filament\Actions\Action;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class ReportsRelationManager extends RelationManager
{
    protected static string $relationship = 'reports';

    protected static ?string $title = 'Reports';

    public function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                DatePicker::make('period_start')
                    ->required(),
                DatePicker::make('period_end')
                    ->required()
                    ->afterOrEqual('period_start'),
                TextInput::make('actual_value')
                    ->required()
                    ->numeric()
                    ->minValue(0),
                TextInput::make('scope_location')
                    ->maxLength(255),
                TextInput::make('scope_project')
                    ->maxLength(255),
                Textarea::make('notes')
                    ->rows(3)
                    ->columnSpanFull(),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('period_start')
                    ->date()
                    ->sortable(),
                TextColumn::make('period_end')
                    ->date()
                    ->sortable(),
                TextColumn::make('actual_value')
                    ->numeric(2)
                    ->sortable(),
                TextColumn::make('scope_location')
                    ->placeholder('-'),
                TextColumn::make('scope_project')
                    ->placeholder('-'),
                TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable(),
            ])
            ->headerActions([
                Action::make('openReportsModule')
                    ->label('Manage in Reports Module')
                    ->icon('heroicon-o-arrow-top-right-on-square')
                    ->url(fn (): string => \App\Filament\Resources\ME\ReportResource::getUrl()),
            ]);
    }
}
