<?php

namespace App\Filament\Resources\Items\Schemas;

use Filament\Infolists\Components\TextEntry;
use Filament\Infolists\Components\ImageEntry;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class ItemInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Basic Info')
                    ->schema([
                        Grid::make(2)
                            ->schema([
                                TextEntry::make('name'),
                                TextEntry::make('assetModel.name')
                                    ->label('Model'),
                                TextEntry::make('test_field')
                                    ->label('DEBUG: If you see this, infolist is working')
                                    ->default('WORKING'),
                                TextEntry::make('assetModel.type.name')
                                    ->label('Item Type'),
                                TextEntry::make('assetModel.category.name')
                                    ->label('Item Category'),
                                TextEntry::make('assetModel.manufacturer.name')
                                    ->label('Manufacturer'),
                                TextEntry::make('assetModel.model_number')
                                    ->label('Model Number'),
                                TextEntry::make('unit.name')
                                    ->label('Unit'),
                                TextEntry::make('assetModel.depreciation.name')
                                    ->label('Depreciation'),
                            ]),
                    ]),

                Section::make('Additional Info')
                    ->schema([
                        Grid::make(2)
                            ->schema([
                                TextEntry::make('note')
                                    ->columnSpanFull(),
                                ImageEntry::make('image'),
                            ]),
                    ]),
            ]);
    }
}
