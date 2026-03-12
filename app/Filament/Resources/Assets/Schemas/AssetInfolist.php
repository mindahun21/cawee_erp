<?php

namespace App\Filament\Resources\Assets\Schemas;

use App\Models\Asset;
use App\Models\AssetModel;
use App\Models\AcquisitionType;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Schema;

class AssetInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('General Information')
                    ->columns(['default' => 2])
                    ->schema([
                        TextEntry::make('quantity')
                            ->label('Total Quantity'),
                        TextEntry::make('name'),
                        TextEntry::make('assetModel.name')
                            ->label('Model'),
                        TextEntry::make('unit.name'),
                        TextEntry::make('serial_number')
                            ->label('Serial Number')
                            ->visible(fn ($record) => $record->quantity <= 1),
                        TextEntry::make('barcode')
                            ->label('Barcode')
                            ->visible(fn ($record) => $record->quantity <= 1),
                        TextEntry::make('qr_code')
                            ->label('QR Code')
                            ->visible(fn ($record) => $record->quantity <= 1),
                        TextEntry::make('rfid_tag')
                            ->label('RFID Tag')
                            ->visible(fn ($record) => $record->quantity <= 1),
                        TextEntry::make('description')
                            ->columnSpanFull(),
                    ]),

                Section::make('Classification')
                    ->columns(['default' => 2])
                    ->schema([
                        TextEntry::make('condition.name'),
                        TextEntry::make('statusRecord.name')
                            ->label('Status'),
                    ]),

                Section::make('Acquisition & Valuation')
                    ->columns(['default' => 2])
                    ->schema([
                        TextEntry::make('acquisitionTypeRecord.name')
                            ->label('Acquisition Type'),
                        TextEntry::make('currency.code'),
                        TextEntry::make('purchase_cost')
                            ->money(fn ($record) => $record->currency?->code ?? 'USD'),
                        TextEntry::make('purchase_date')
                            ->date(),
                        TextEntry::make('warranty_expiry_date')
                            ->label('Warranty Expiry')
                            ->date(),
                        TextEntry::make('supplier.name')
                            ->label('Supplier'),
                        TextEntry::make('donor.name')
                            ->label('Donor'),
                        TextEntry::make('notes')
                            ->columnSpanFull(),
                        TextEntry::make('contract_details')
                            ->label('Contract / Lease Details')
                            ->columnSpanFull(),
                    ]),

                Section::make('Stock Allocation')
                    ->schema([
                        \Filament\Infolists\Components\RepeatableEntry::make('stocks')
                            ->schema([
                                TextEntry::make('location.location_name')
                                    ->label('Location'),
                                TextEntry::make('department.name')
                                    ->label('Department'),
                                TextEntry::make('quantity'),
                            ])
                            ->columns(['default' => 3]),
                    ]),


                Section::make('Vehicle / Machinery Details')
                    ->columns(['default' => 3])
                    ->visible(fn ($record) => in_array($record->assetModel?->category?->name, ['Vehicles', 'Machinery']))
                    ->schema([
                        TextEntry::make('vehicleDetail.plate_number')
                            ->label('Plate Number'),
                        TextEntry::make('vehicleDetail.chassis_number')
                            ->label('Chassis Number'),
                        TextEntry::make('vehicleDetail.motor_number')
                            ->label('Motor Number'),
                        TextEntry::make('vehicleDetail.engine_size')
                            ->label('Engine Size'),
                        TextEntry::make('vehicleDetail.fuel_type')
                            ->label('Fuel Type'),
                        TextEntry::make('vehicleDetail.capacity'),
                        TextEntry::make('vehicleDetail.color'),
                        TextEntry::make('vehicleDetail.horsepower'),
                        TextEntry::make('vehicleDetail.year_manufactured')
                            ->label('Year Manufactured'),
                        TextEntry::make('vehicleDetail.manufacturer'),
                        TextEntry::make('vehicleDetail.insurance_company')
                            ->label('Insurance Company'),
                        TextEntry::make('vehicleDetail.insurance_policy_no')
                            ->label('Insurance Policy No'),
                        TextEntry::make('vehicleDetail.insurance_expiration_date')
                            ->label('Insurance Expiration')
                            ->date(),
                        TextEntry::make('vehicleDetail.technical_inspection_date')
                            ->label('Technical Inspection')
                            ->date(),
                        TextEntry::make('vehicleDetail.technical_inspection_expiration_date')
                            ->label('Technical Inspection Expiration')
                            ->date(),
                    ]),
            ]);
    }
}
