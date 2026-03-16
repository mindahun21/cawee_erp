<?php

namespace App\Filament\Resources\Warehouses\Schemas;

use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class WarehouseForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('General Information')
                    ->schema([
                        Grid::make(2)
                            ->schema([
                                TextInput::make('warehouse_code')
                                    ->label('Warehouse Code Number')
                                    ->prefix(fn () => \App\Models\PrefixSetting::getPrefix('warehouse_code'))
                                    ->default(fn () => \App\Models\PrefixSetting::where('key', 'warehouse_code')->value('next_number'))
                                    ->dehydrateStateUsing(fn ($state) => \App\Models\PrefixSetting::getPrefix('warehouse_code') . $state)
                                    ->required()
                                    ->unique(ignoreRecord: true),
                                TextInput::make('name')
                                    ->required()
                                    ->label('Warehouse Name'),
                            ]),
                        Grid::make(2)
                            ->schema([
                                TextInput::make('order')
                                    ->numeric()
                                    ->label('Sort Order'),
                                Select::make('employees')
                                    ->relationship('employees')
                                    ->getOptionLabelFromRecordUsing(fn (\App\Models\Employee $record) => $record->full_name)
                                    ->multiple()
                                    ->preload()
                                    ->searchable()
                                    ->label('Assign to Staff'),
                            ]),
                    ]),
                Section::make('Address Details')
                    ->schema([
                        Textarea::make('address')
                            ->rows(3)
                            ->label('Warehouse Address'),
                        Grid::make(3)
                            ->schema([
                                TextInput::make('city'),
                                TextInput::make('province'),
                                TextInput::make('postal_code')
                                    ->label('Postal Code'),
                            ]),
                        Select::make('country')
                            ->options(config('countries'))
                            ->searchable()
                            ->label('Country'),
                    ]),
                Section::make('Additional Information')
                    ->schema([
                        Textarea::make('note')
                            ->rows(3)
                            ->label('Notes'),
                    ]),
            ]);
    }
}
