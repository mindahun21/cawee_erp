<?php

namespace App\Filament\Resources\PurchaseOrders\Schemas;

use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class PurchaseOrderForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->columns(1)
            ->components([
                Section::make('Order Information')
                    ->columns(2)
                    ->schema([
                        Select::make('purchase_request_id')
                            ->relationship('purchaseRequest', 'project_name')
                            ->required()
                            ->searchable()
                            ->preload(),
                        Select::make('supplier_id')
                            ->relationship('supplier', 'name')
                            ->required()
                            ->searchable()
                            ->preload(),
                        TextInput::make('po_number')
                            ->label('PO Number')
                            ->required()
                            ->unique(ignoreRecord: true),
                        DatePicker::make('po_date')
                            ->default(now())
                            ->required(),
                        Select::make('status')
                            ->options([
                                'pending' => 'Pending',
                                'ordered' => 'Ordered',
                                'received' => 'Received',
                                'cancelled' => 'Cancelled',
                            ])
                            ->default('pending')
                            ->required(),
                    ]),
                Section::make('Order Items')
                    ->schema([
                        Repeater::make('items')
                            ->relationship()
                            ->schema([
                                TextInput::make('description')
                                    ->required()
                                    ->columnSpan(3),
                                TextInput::make('quantity')
                                    ->numeric()
                                    ->default(1)
                                    ->required()
                                    ->live()
                                    ->afterStateUpdated(function ($state, $set, $get) {
                                        $set('total_cost', (float) $state * (float) ($get('unit_cost') ?? 0));
                                        static::updateTotal($set, $get);
                                    })
                                    ->columnSpan(1),
                                TextInput::make('unit_cost')
                                    ->numeric()
                                    ->prefix('INR')
                                    ->default(0)
                                    ->required()
                                    ->live()
                                    ->afterStateUpdated(function ($state, $set, $get) {
                                        $set('total_cost', (float) $state * (float) ($get('quantity') ?? 0));
                                        static::updateTotal($set, $get);
                                    })
                                    ->columnSpan(2),
                                TextInput::make('total_cost')
                                    ->numeric()
                                    ->prefix('INR')
                                    ->disabled()
                                    ->dehydrated()
                                    ->columnSpan(2),
                            ])
                            ->columns(8)
                            ->defaultItems(1)
                            ->reorderableWithButtons()
                            ->addActionLabel('Add Item')
                            ->live()
                            ->afterStateUpdated(fn ($state, $set, $get) => static::updateTotal($set, $get)),
                    ]),
                Section::make('Total Amount')
                    ->schema([
                        TextInput::make('total_amount')
                            ->numeric()
                            ->prefix('INR')
                            ->default(0)
                            ->dehydrated()
                            ->disabled(),
                    ]),
            ]);
    }

    public static function updateTotal($set, $get)
    {
        $items = $get('items') ?? [];
        $total = 0;
        foreach ($items as $item) {
            $total += (float) ($item['quantity'] ?? 0) * (float) ($item['unit_cost'] ?? 0);
        }
        $set('total_amount', $total);
    }
}
