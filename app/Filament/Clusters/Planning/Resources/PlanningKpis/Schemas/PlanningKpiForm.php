<?php

namespace App\Filament\Clusters\Planning\Resources\PlanningKpis\Schemas;

use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;

class PlanningKpiForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                \Filament\Schemas\Components\Section::make()->columns(2)->schema([
                    Select::make('plan_id')
                        ->relationship('plan', 'title')
                        ->searchable()
                        ->preload()
                        ->required(),
                    TextInput::make('indicator_name')
                        ->required()
                        ->maxLength(255),
                    TextInput::make('unit')
                        ->placeholder('e.g. Percentage, Count, USD')
                        ->maxLength(50),
                    Select::make('department_id')
                        ->relationship('department', 'name')
                        ->searchable()
                        ->preload(),
                ]),
                \Filament\Schemas\Components\Section::make('Performance Tracking')
                    ->columns(2)
                    ->schema([
                        TextInput::make('target_value')
                            ->required()
                            ->numeric()
                            ->default(0)
                            ->prefix('Target'),
                        TextInput::make('actual_value')
                            ->required()
                            ->numeric()
                            ->default(0)
                            ->prefix('Actual'),
                    ]),
            ]);
    }
}
