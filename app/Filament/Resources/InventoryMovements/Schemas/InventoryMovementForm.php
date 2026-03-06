<?php

namespace App\Filament\Resources\InventoryMovements\Schemas;

use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Schemas\Components\Utilities\Get;

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
                        Select::make('asset_id')
                            ->relationship('asset', 'name')
                            ->required()
                            ->searchable()
                            ->preload()
                            ->live(),
                        Select::make('type')
                            ->options([
                                'Stock In' => 'Stock In',
                                'Stock Out' => 'Stock Out',
                                'Transfer' => 'Transfer',
                                'Return' => 'Return',
                                'Adjustment' => 'Adjustment',
                                'Damage' => 'Damage',
                                'Disposal' => 'Disposal',
                            ])
                            ->required()
                            ->live(),
                        Select::make('reason')
                            ->options([
                                'New Purchase' => 'New Purchase',
                                'Donation' => 'Donation',
                                'Return' => 'Return',
                                'Issue/Assignment' => 'Issue/Assignment',
                                'Damage/Breakage' => 'Damage/Breakage',
                                'Expired' => 'Expired',
                                'Disposal' => 'Disposal',
                                'Lost/Stolen' => 'Lost/Stolen',
                                'Audit Adjustment' => 'Audit Adjustment',
                                'Other' => 'Other',
                            ])
                            ->searchable()
                            ->required(),
                        TextInput::make('quantity')
                            ->numeric()
                            ->default(1)
                            ->required()
                            ->rules([
                                function (Get $get) {
                                    return function (string $attribute, $value, $fail) use ($get) {
                                        $type = $get('type');
                                        if (!in_array($type, ['Transfer', 'Stock Out', 'Damage', 'Disposal', 'Return', 'Issue/Assignment'])) {
                                            return;
                                        }

                                        $assetId = $get('asset_id');
                                        $fromId = $get('from_location_id');

                                        if (!$assetId || !$fromId) {
                                            return;
                                        }

                                        $stock = \App\Models\AssetStock::where('asset_id', $assetId)
                                            ->where('location_id', $fromId)
                                            ->first();

                                        $available = $stock ? $stock->quantity : 0;

                                        if ($value > $available) {
                                            $fail("The quantity cannot exceed the available stock ($available) in the selected location.");
                                        }
                                    };
                                },
                            ]),
                        DatePicker::make('date')
                            ->default(now())
                            ->required(),
                        Select::make('status')
                            ->options([
                                'pending' => 'Pending Approval',
                                'in_transit' => 'In Transit',
                                'completed' => 'Completed / Received',
                                'rejected' => 'Rejected',
                             ])
                            ->default('completed')
                            ->required(),
                        TextInput::make('reference_no')
                            ->label('Reference Number / Ticket')
                            ->maxLength(255),
                        Select::make('user_id')
                            ->label('Handled By')
                            ->relationship('user', 'name')
                            ->searchable()
                            ->preload()
                            ->required(),
                    ]),
                Section::make('Location Information')
                    ->columns(2)
                    ->hidden(fn (Get $get) => blank($get('type')))
                    ->schema([
                        Select::make('from_location_id')
                            ->label('From Location')
                            ->relationship('fromLocation', 'name', function ($query, Get $get) {
                                $assetId = $get('asset_id');
                                $type = $get('type');
                                
                                if ($assetId && in_array($type, ['Transfer', 'Stock Out', 'Damage', 'Disposal', 'Return', 'Issue/Assignment'])) {
                                    $query->whereHas('stocks', function($q) use ($assetId) {
                                        $q->where('asset_id', $assetId)->where('quantity', '>', 0);
                                    });
                                }
                            })
                            ->searchable()
                            ->preload()
                            ->live()
                            ->required(fn (Get $get) => in_array($get('type'), ['Stock Out', 'Damage', 'Disposal', 'Transfer']))
                            ->hidden(fn (Get $get) => $get('type') === 'Stock In'),
                        Select::make('to_location_id')
                            ->label('To Location')
                            ->relationship('toLocation', 'name')
                            ->searchable()
                            ->preload()
                            ->required(fn (Get $get) => in_array($get('type'), ['Stock In', 'Transfer']))
                            ->hidden(fn (Get $get) => in_array($get('type'), ['Stock Out', 'Damage', 'Disposal'])),
                    ]),
                Section::make('Remarks')
                    ->schema([
                        Textarea::make('remarks')
                            ->columnSpanFull(),
                    ]),
            ]);
    }
}
