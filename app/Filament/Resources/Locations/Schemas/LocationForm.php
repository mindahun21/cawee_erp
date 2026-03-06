<?php

namespace App\Filament\Resources\Locations\Schemas;

use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Schemas\Schema;
use App\Models\Location;
use App\Models\Project;

class LocationForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->columns(1)
            ->components([
                \Filament\Schemas\Components\Section::make('Location details')
                    ->columns(2)
                    ->icon('heroicon-o-map-pin')
                    ->schema([
                        TextInput::make('name')
                            ->required()
                            ->maxLength(255),
                        Select::make('type')
                            ->options([
                                'HO' => 'Head Office',
                                'Region' => 'Region',
                                'Site' => 'Site',
                                'Project' => 'Project Site',
                            ])
                            ->required(),
                        Select::make('parent_id')
                            ->label('Parent Location')
                            ->relationship('parent', 'name')
                            ->searchable()
                            ->preload(),
                        Select::make('project_id')
                            ->label('Project')
                            ->relationship('project', 'name')
                            ->searchable()
                            ->preload(),
                        Textarea::make('description')
                            ->columnSpanFull(),
                    ]),
            ]);
    }
}
