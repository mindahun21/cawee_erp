<?php

namespace App\Filament\Resources\Warehouses\Schemas;

use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class WarehouseInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Warehouse Details')
                    ->schema([
                        Grid::make(2)
                            ->schema([
                                TextEntry::make('warehouse_code')
                                    ->label('Warehouse Code'),
                                TextEntry::make('name')
                                    ->label('Warehouse Name'),
                            ]),
                        Grid::make(2)
                            ->schema([
                                TextEntry::make('order')
                                    ->label('Sort Order'),
                                TextEntry::make('employees.full_name')
                                    ->label('Assigned Staff')
                                    ->badge()
                                    ->listWithLineBreaks(),
                            ]),
                    ]),
                Section::make('Location Information')
                    ->schema([
                        TextEntry::make('address')
                            ->label('Address')
                            ->columnSpanFull(),
                        Grid::make(3)
                            ->schema([
                                TextEntry::make('city'),
                                TextEntry::make('province'),
                                TextEntry::make('postal_code')
                                    ->label('Postal Code'),
                            ]),
                        TextEntry::make('country.name')
                            ->label('Country'),
                    ]),
                Section::make('Additional Information')
                    ->schema([
                        TextEntry::make('note')
                            ->label('Notes')
                            ->columnSpanFull(),
                    ]),
            ]);
    }
}
