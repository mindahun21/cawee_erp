<?php

namespace App\Filament\Resources\PurchaseRequests\Schemas;

use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Grid;
use Filament\Infolists\Components\TextEntry as Placeholder;
use Filament\Schemas\Schema;

class PurchaseRequestForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->columns(1)
            ->components([
                Section::make('Purchase Request Information')
                    ->columnSpanFull()
                    ->columns(2)
                    ->schema([
                        TextInput::make('code')
                            ->label('Purchase Request Code')
                            ->placeholder('#PR-XXXXX-YYYY-DEPT')
                            ->disabled()
                            ->dehydrated(),
                        TextInput::make('name')
                            ->label('Purchase Request Name')
                            ->required()
                            ->columnSpan(2),
                        DatePicker::make('request_date')
                            ->label('Request Date')
                            ->required()
                            ->default(now())
                            ->columnSpan(1),
                        
                        Select::make('project_id')
                            ->label('Project')
                            ->relationship('project', 'name')
                            ->searchable()
                            ->preload()
                            ->required()
                            ->placeholder('Select a project'),
                        TextInput::make('sale_estimate')
                            ->label('Sale estimate')
                            ->numeric()
                            ->prefix('$')
                            ->placeholder('0.00'),
                        Select::make('type')
                            ->label('Type')
                            ->options([
                                'none' => 'None',
                                'local' => 'Local',
                                'international' => 'International',
                            ])
                            ->required()
                            ->native(false),
                        Select::make('currency_id')
                            ->label('Currency')
                            ->relationship('currency', 'name')
                            ->searchable()
                            ->preload()
                            ->default(1)
                            ->required(),

                        Select::make('department_id')
                            ->label('Department')
                            ->relationship('department', 'name')
                            ->searchable()
                            ->preload()
                            ->required()
                            ->live(),
                        TextInput::make('sale_invoice_id')
                            ->label('Sale invoices'),
                        Select::make('requester_id')
                            ->label('Requester')
                            ->relationship('requester', 'name')
                            ->searchable()
                            ->preload()
                            ->default(auth()->id())
                            ->required(),
                        Select::make('share_to_vendors')
                            ->label('Share to vendors')
                            ->options([
                                'none' => 'None',
                                'yes' => 'Yes',
                                'no' => 'No',
                            ])
                            ->required()
                            ->native(false),
                        
                        Textarea::make('description')
                            ->label('Description')
                            ->placeholder('Enter a general description for this purchase request...')
                            ->required()
                            ->columnSpanFull(),
                    ]),

                Section::make('Items Details')
                    ->columnSpanFull()
                    ->schema([
                        Repeater::make('items')
                            ->relationship()
                            ->schema([
                                Select::make('item_id')
                                    ->label('Item')
                                    ->relationship('item', 'name')
                                    ->searchable()
                                    ->preload()
                                    ->required()
                                    ->live()
                                    ->afterStateUpdated(function ($state, $set) {
                                        $item = \App\Models\Item::find($state);
                                        if ($item) {
                                            $set('unit_price', $item->unit_price);
                                            $set('unit', $item->sku); // Using SKU as unit placeholder or if there was a unit field
                                        }
                                    })
                                    ->columnSpan(2),
                                TextInput::make('description')
                                    ->placeholder('Item Name/Description')
                                    ->columnSpan(2),
                                TextInput::make('unit_price')
                                    ->label('Unit price')
                                    ->numeric()
                                    ->prefix('$')
                                    ->required()
                                    ->minValue(0)
                                    ->live()
                                    ->afterStateUpdated(fn ($state, $set, $get) => static::calculateItemTotals($set, $get)),
                                TextInput::make('quantity')
                                    ->numeric()
                                    ->default(1)
                                    ->required()
                                    ->minValue(1)
                                    ->live()
                                    ->afterStateUpdated(fn ($state, $set, $get) => static::calculateItemTotals($set, $get)),
                                TextInput::make('unit')
                                    ->placeholder('Unit')
                                    ->disabled()
                                    ->dehydrated(),
                                TextInput::make('subtotal')
                                    ->label('Subtotal')
                                    ->numeric()
                                    ->prefix('$')
                                    ->disabled()
                                    ->dehydrated()
                                    ->columnSpan(1),
                                Select::make('tax_id')
                                    ->label('Tax')
                                    ->relationship('tax', 'name')
                                    ->searchable()
                                    ->preload()
                                    ->live()
                                    ->afterStateUpdated(fn ($state, $set, $get) => static::calculateItemTotals($set, $get)),
                                TextInput::make('tax_value')
                                    ->label('Tax Value')
                                    ->numeric()
                                    ->prefix('$')
                                    ->disabled()
                                    ->dehydrated(),
                                TextInput::make('total')
                                    ->label('Total')
                                    ->numeric()
                                    ->prefix('$')
                                    ->disabled()
                                    ->dehydrated(),
                            ])
                            ->columns(11)
                            ->defaultItems(1)
                            ->addActionLabel('Add New Item')
                            ->live()
                            ->afterStateUpdated(fn ($state, $set, $get) => static::updateTotals($set, $get)),

                        \Filament\Schemas\Components\Section::make('Totals')
                            ->schema([
                                Placeholder::make('subtotal_placeholder')
                                    ->label('Subtotal')
                                    ->state(fn ($get) => '$' . number_format($get('subtotal') ?? 0, 2))
                                    ->extraAttributes(['class' => 'text-right font-bold']),
                                Placeholder::make('tax_amount_placeholder')
                                    ->label('VAT')
                                    ->state(fn ($get) => '$' . number_format($get('tax_amount') ?? 0, 2))
                                    ->extraAttributes(['class' => 'text-right font-bold']),
                                Placeholder::make('total_amount_placeholder')
                                    ->label('Total')
                                    ->state(fn ($get) => '$' . number_format($get('total_amount') ?? 0, 2))
                                    ->extraAttributes(['class' => 'text-right font-bold']),
                            ]),
                        
                        // Hidden fields to store totals
                        \Filament\Forms\Components\Hidden::make('subtotal'),
                        \Filament\Forms\Components\Hidden::make('tax_amount'),
                        \Filament\Forms\Components\Hidden::make('total_amount'),
                    ]),
            ]);
    }

    public static function calculateItemTotals($set, $get)
    {
        $quantity = (float) ($get('quantity') ?? 0);
        $unitPrice = (float) ($get('unit_price') ?? 0);
        $subtotal = $quantity * $unitPrice;
        
        $taxId = $get('tax_id');
        $taxRate = 0;
        if ($taxId) {
            $tax = \App\Models\Tax::find($taxId);
            $taxRate = $tax ? (float) $tax->rate : 0;
        }
        
        $taxValue = $subtotal * ($taxRate / 100);
        $total = $subtotal + $taxValue;
        
        $set('subtotal', $subtotal);
        $set('tax_value', $taxValue);
        $set('total', $total);
    }

    public static function updateTotals($set, $get)
    {
        $items = $get('items') ?? [];
        $subtotalTotal = 0;
        $taxAmountTotal = 0;
        $totalAmountTotal = 0;

        foreach ($items as $item) {
            $subtotalTotal += (float) ($item['subtotal'] ?? 0);
            $taxAmountTotal += (float) ($item['tax_value'] ?? 0);
            $totalAmountTotal += (float) ($item['total'] ?? 0);
        }

        $set('subtotal', $subtotalTotal);
        $set('tax_amount', $taxAmountTotal);
        $set('total_amount', $totalAmountTotal);
    }
}
