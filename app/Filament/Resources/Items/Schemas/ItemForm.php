<?php

namespace App\Filament\Resources\Items\Schemas;

use App\Models\Item;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\Radio;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class ItemForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Basic Information')
                    ->columns(['default' => 2])
                    ->schema([
                        TextInput::make('name')
                            ->required()
                            ->maxLength(255),

                        TextInput::make('item_code')
                            ->label('Item Code')
                            ->prefix(fn () => \App\Models\PrefixSetting::getPrefix('item_code'))
                            ->default(fn () => \App\Models\PrefixSetting::where('key', 'item_code')->value('next_number'))
                            ->dehydrateStateUsing(fn ($state) => $state ? \App\Models\PrefixSetting::getPrefix('item_code') . $state : null)
                            ->unique(Item::class, 'item_code', ignoreRecord: true)
                            ->live(onBlur: true)
                            ->hint(fn ($state, $record) => $state ? (Item::where('item_code', \App\Models\PrefixSetting::getPrefix('item_code') . $state)->when($record, fn ($q) => $q->where('id', '!=', $record->id))->exists() ? 'Already taken' : 'Available') : null)
                            ->hintColor('success'),

                        Select::make('item_category_id')
                            ->label('Category')
                            ->relationship('itemCategory', 'name')
                            ->searchable()
                            ->preload()
                            ->createOptionForm([
                                TextInput::make('name')->required(),
                                Textarea::make('description'),
                            ]),

                        Select::make('item_type_id')
                            ->label('Item Type')
                            ->relationship('itemTypeRecord', 'name')
                            ->required()
                            ->createOptionForm([
                                TextInput::make('name')->required()->unique('item_types', 'name'),
                            ]),

                        Select::make('unit_id')
                            ->relationship('unit', 'name')
                            ->searchable()
                            ->preload()
                            ->required()
                            ->createOptionForm([
                                TextInput::make('name')->required(),
                                TextInput::make('code')->required(),
                            ]),

                        TextInput::make('barcode')
                            ->label('Barcode / QR Code')
                            ->maxLength(255)
                            ->unique(Item::class, 'barcode', ignoreRecord: true),

                        TextInput::make('reorder_level')
                            ->label('Reorder Level (Min Stock)')
                            ->numeric()
                            ->default(0)
                            ->suffix('units'),
                    ]),

                Section::make('Description & Image')
                    ->columns(['default' => 1])
                    ->schema([
                        Textarea::make('description')
                            ->label('Detailed Description')
                            ->rows(3),

                        Textarea::make('note')
                            ->label('Internal Notes')
                            ->rows(2),

                        FileUpload::make('image')
                            ->image()
                            ->directory('items')
                            ->imageResizeMode('cover')
                            ->imageCropAspectRatio('16:9'),
                    ]),
            ]);
    }
}
