<?php

namespace App\Filament\Exports;

use App\Models\Asset;
use Filament\Actions\Exports\ExportColumn;
use Filament\Actions\Exports\Exporter;
use Filament\Actions\Exports\Models\Export;
use Illuminate\Support\Number;

class AssetExporter extends Exporter
{
    protected static ?string $model = Asset::class;

    public static function getColumns(): array
    {
        return [
            ExportColumn::make('id')->label('ID'),
            ExportColumn::make('barcode')->label('Barcode'),
            ExportColumn::make('qr_code')->label('QR Code'),
            ExportColumn::make('rfid_tag')->label('RFID Tag'),
            ExportColumn::make('name')->label('Asset Name'),
            ExportColumn::make('model')->label('Model'),
            ExportColumn::make('serial_number')->label('Serial Number'),
            ExportColumn::make('assetCategory.name')->label('Category'),
            ExportColumn::make('location.location_name')->label('Location'),
            ExportColumn::make('department.name')->label('Department'),
            ExportColumn::make('is_fixed_asset')->label('Type')
                ->formatStateUsing(fn ($state) => $state ? 'Fixed Asset' : 'Inventory'),
            ExportColumn::make('statusRecord.name')->label('Status'),
            ExportColumn::make('condition.name')->label('Condition'),
            ExportColumn::make('acquisitionTypeRecord.name')->label('Acquisition Type'),
            ExportColumn::make('purchase_cost')->label('Purchase Cost'),
            ExportColumn::make('purchase_date')->label('Purchase Date'),
            ExportColumn::make('depreciation.name')->label('Depreciation Type'),
            ExportColumn::make('depreciation.months')->label('Useful Life (Months)'),
            ExportColumn::make('warranty_expiry_date')->label('Warranty Expiry'),
            ExportColumn::make('quantity')->label('Quantity'),
            ExportColumn::make('description')->label('Description'),
            ExportColumn::make('created_at')->label('Created At'),
        ];
    }

    public static function getCompletedNotificationBody(Export $export): string
    {
        $body = 'Your asset export has completed and ' . Number::format($export->successful_rows) . ' ' . str('row')->plural($export->successful_rows) . ' exported.';

        if ($failedRowsCount = $export->getFailedRowsCount()) {
            $body .= ' ' . Number::format($failedRowsCount) . ' ' . str('row')->plural($failedRowsCount) . ' failed to export.';
        }

        return $body;
    }
}
