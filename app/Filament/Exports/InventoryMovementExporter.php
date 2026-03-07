<?php

namespace App\Filament\Exports;

use App\Models\InventoryMovement;
use Filament\Actions\Exports\ExportColumn;
use Filament\Actions\Exports\Exporter;
use Filament\Actions\Exports\Models\Export;
use Illuminate\Support\Number;

class InventoryMovementExporter extends Exporter
{
    protected static ?string $model = InventoryMovement::class;

    public static function getColumns(): array
    {
        return [
            ExportColumn::make('id')->label('ID'),
            ExportColumn::make('date')->label('Date'),
            ExportColumn::make('asset.name')->label('Asset'),
            ExportColumn::make('type')->label('Movement Type'),
            ExportColumn::make('status')->label('Status'),
            ExportColumn::make('quantity')->label('Quantity'),
            ExportColumn::make('fromLocation.location_name')->label('From Location'),
            ExportColumn::make('toLocation.location_name')->label('To Location'),
            ExportColumn::make('reason')->label('Reason'),
            ExportColumn::make('reference_no')->label('Reference No.'),
            ExportColumn::make('remarks')->label('Remarks'),
            ExportColumn::make('created_at')->label('Logged At'),
        ];
    }

    public static function getCompletedNotificationBody(Export $export): string
    {
        $body = 'Your inventory movement export has completed and ' . Number::format($export->successful_rows) . ' ' . str('row')->plural($export->successful_rows) . ' exported.';

        if ($failedRowsCount = $export->getFailedRowsCount()) {
            $body .= ' ' . Number::format($failedRowsCount) . ' ' . str('row')->plural($failedRowsCount) . ' failed to export.';
        }

        return $body;
    }
}
