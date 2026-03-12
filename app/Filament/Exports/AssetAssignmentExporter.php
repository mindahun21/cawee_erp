<?php

namespace App\Filament\Exports;

use App\Models\AssetAssignment;
use Filament\Actions\Exports\ExportColumn;
use Filament\Actions\Exports\Exporter;
use Filament\Actions\Exports\Models\Export;
use Illuminate\Support\Number;

class AssetAssignmentExporter extends Exporter
{
    protected static ?string $model = AssetAssignment::class;

    public static function getColumns(): array
    {
        return [
            ExportColumn::make('id')->label('ID'),
            ExportColumn::make('asset.name')->label('Asset'),
            ExportColumn::make('employee.full_name')->label('Assigned Staff'),
            ExportColumn::make('department.name')->label('Assigned Department'),
            ExportColumn::make('project.project_name')->label('Assigned Project'),
            ExportColumn::make('location.location_name')->label('Assigned Location'),
            ExportColumn::make('status')->label('Status'),
            ExportColumn::make('assigned_date')->label('Assigned Date'),
            ExportColumn::make('expected_return_date')->label('Expected Return'),
            ExportColumn::make('returned_date')->label('Returned Date'),
            ExportColumn::make('condition_on_assignment')->label('Condition on Checkout'),
            ExportColumn::make('condition_on_return')->label('Condition on Return'),
            ExportColumn::make('remarks')->label('Remarks'),
        ];
    }

    public static function getCompletedNotificationBody(Export $export): string
    {
        $body = 'Your asset assignment export has completed and ' . Number::format($export->successful_rows) . ' ' . str('row')->plural($export->successful_rows) . ' exported.';

        if ($failedRowsCount = $export->getFailedRowsCount()) {
            $body .= ' ' . Number::format($failedRowsCount) . ' ' . str('row')->plural($failedRowsCount) . ' failed to export.';
        }

        return $body;
    }
}
