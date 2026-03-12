<?php

namespace App\Filament\Resources\Items\Schemas;

use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class ItemForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Basic Info')
                    ->schema([
                        Grid::make(2)
                            ->schema([
                                TextInput::make('name')
                                    ->required(),
                                Select::make('asset_model_id')
                                    ->relationship('assetModel', 'name')
                                    ->searchable()
                                    ->preload()
                                    ->required(),
                                Select::make('unit_id')
                                    ->relationship('unit', 'name')
                                    ->searchable()
                                    ->preload()
                                    ->required(),
                            ]),
                    ]),

                Section::make('Additional Info')
                    ->schema([
                        Grid::make(1)
                            ->schema([
                                Textarea::make('note'),
                                FileUpload::make('image')
                                    ->image()
                                    ->directory('items'),
                            ]),
                    ]),
            ]);
    }
}
