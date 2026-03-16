<?php

namespace App\Filament\Resources\InventoryMovements\Schemas;

use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\Radio;
use Filament\Forms\Components\Checkbox;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Schemas\Components\Utilities\Get;
use App\Models\ItemWarehouse;

class InventoryMovementForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->columns(1)
            ->components([
                Section::make('Movement Details')
                    ->columns(2)
                    ->schema([
                        Select::make('item_id')
                            ->relationship('item', 'name')
                            ->required()
                            ->searchable()
                            ->preload()
                            ->live(),
                        
                        Select::make('from_warehouse_id')
                            ->label('From Warehouse')
                            ->relationship('fromWarehouse', 'name', function ($query, Get $get) {
                                $itemId = $get('item_id');
                                if ($itemId) {
                                    $query->whereHas('items', function ($q) use ($itemId) {
                                        $q->where('items.id', $itemId)
                                          ->where('item_warehouse.quantity', '>', 0);
                                    });
                                }
                            })
                            ->required()
                            ->searchable()
                            ->preload()
                            ->live(),

                        Select::make('reason_id')
                            ->label('Reason')
                            ->relationship('movementReason', 'name')
                            ->searchable()
                            ->preload()
                            ->required(),

                        TextInput::make('quantity')
                            ->numeric()
                            ->default(1)
                            ->required()
                            ->live()
                            ->rules([
                                function (Get $get) {
                                    return function (string $attribute, $value, $fail) use ($get) {
                                        $itemId = $get('item_id');
                                        $fromId = $get('from_warehouse_id');

                                        if (!$itemId || !$fromId) {
                                            return;
                                        }

                                        $itemWarehouse = ItemWarehouse::where('item_id', $itemId)
                                            ->where('warehouse_id', $fromId)
                                            ->first();

                                        $available = $itemWarehouse ? $itemWarehouse->quantity : 0;

                                        if ($value > $available) {
                                            $fail("The quantity cannot exceed the available stock ($available) in the selected warehouse.");
                                        }
                                    };
                                },
                            ]),

                        DatePicker::make('date')
                            ->default(now())
                            ->required(),

                        Select::make('status_id')
                            ->label('Status')
                            ->relationship('movementStatus', 'name')
                            ->searchable()
                            ->preload()
                            ->required(),

                        TextInput::make('reference_no')
                            ->label('Reference Number / Ticket')
                            ->maxLength(255),

                        Select::make('employee_id')
                            ->label('Handled By')
                            ->relationship('employee', 'first_name')
                            ->getOptionLabelFromRecordUsing(fn ($record) => "{$record->first_name} {$record->last_name}")
                            ->searchable()
                            ->preload()
                            ->required(),
                        
                        Checkbox::make('confirm_min_stock')
                            ->label('Acknowledge: Stock will drop below minimum level')
                            ->visible(function (Get $get) {
                                $itemId = $get('item_id');
                                $fromId = $get('from_warehouse_id');
                                $quantity = (int) $get('quantity');

                                if (!$itemId || !$fromId || !$quantity) {
                                    return false;
                                }

                                $itemWarehouse = ItemWarehouse::where('item_id', $itemId)
                                    ->where('warehouse_id', $fromId)
                                    ->first();

                                if (!$itemWarehouse) {
                                    return false;
                                }

                                return ($itemWarehouse->quantity - $quantity) < $itemWarehouse->min_stock_value;
                            })
                            ->required()
                            ->columnSpanFull(),
                    ]),

                Section::make('Destination Information')
                    ->columns(2)
                    ->schema([
                        Radio::make('destination_type')
                            ->options([
                                'warehouse' => 'Warehouse',
                                'location_department' => 'Location & Department'
                            ])
                            ->required()
                            ->live()
                            ->columnSpanFull(),

                        Select::make('to_warehouse_id')
                            ->label('To Warehouse')
                            ->relationship('toWarehouse', 'name')
                            ->searchable()
                            ->preload()
                            ->visible(fn (Get $get) => $get('destination_type') === 'warehouse')
                            ->required(fn (Get $get) => $get('destination_type') === 'warehouse'),

                        Select::make('to_location_id')
                            ->label('To Location')
                            ->relationship('toLocation', 'location_name')
                            ->searchable()
                            ->preload()
                            ->visible(fn (Get $get) => $get('destination_type') === 'location_department')
                            ->required(fn (Get $get) => $get('destination_type') === 'location_department'),

                        Select::make('to_department_id')
                            ->label('To Department')
                            ->relationship('toDepartment', 'name')
                            ->searchable()
                            ->preload()
                            ->visible(fn (Get $get) => $get('destination_type') === 'location_department'),
                    ]),

                Section::make('Remarks')
                    ->schema([
                        Textarea::make('remarks')
                            ->columnSpanFull(),
                    ]),
            ]);
    }
}
