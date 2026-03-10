<?php

namespace App\Filament\Imports;

use App\Models\Asset;
use Filament\Actions\Imports\ImportColumn;
use Filament\Actions\Imports\Importer;
use Filament\Actions\Imports\Models\Import;

class AssetImporter extends Importer
{
    protected static ?string $model = Asset::class;

    public static function getColumns(): array
    {
        return [
            ImportColumn::make('name')
                ->label('Asset Name')
                ->requiredMapping()
                ->rules(['required', 'string', 'max:255']),

            ImportColumn::make('asset_category_id')
                ->label('Category ID')
                ->requiredMapping()
                ->numeric()
                ->rules(['required', 'integer', 'exists:asset_categories,id']),

            ImportColumn::make('status')
                ->label('Status')
                ->requiredMapping()
                ->rules(['required', 'in:available,assigned,maintenance,disposed,lost']),

            ImportColumn::make('acquisition_type')
                ->label('Acquisition Type')
                ->rules(['nullable', 'in:Purchase,Donation,Lease']),

            ImportColumn::make('condition')
                ->label('Condition')
                ->rules(['nullable', 'in:New,Good,Fair,Poor,Broken']),

            ImportColumn::make('serial_number')
                ->label('Serial Number'),

            ImportColumn::make('model')
                ->label('Model / Make'),

            ImportColumn::make('purchase_cost')
                ->label('Purchase Cost')
                ->numeric()
                ->rules(['nullable', 'numeric', 'min:0']),

            ImportColumn::make('purchase_date')
                ->label('Purchase Date (YYYY-MM-DD)')
                ->rules(['nullable', 'date']),

            ImportColumn::make('warranty_expiry_date')
                ->label('Warranty Expiry Date (YYYY-MM-DD)')
                ->rules(['nullable', 'date']),

            ImportColumn::make('depreciation_id')
                ->label('Depreciation Type ID')
                ->numeric()
                ->rules(['nullable', 'integer', 'exists:depreciations,id']),

            ImportColumn::make('location_id')
                ->label('Location ID')
                ->numeric()
                ->rules(['nullable', 'integer', 'exists:locations,id']),

            ImportColumn::make('department_id')
                ->label('Department ID')
                ->numeric()
                ->rules(['nullable', 'integer', 'exists:departments,id']),

            ImportColumn::make('currency_id')
                ->label('Currency ID')
                ->numeric()
                ->rules(['nullable', 'integer', 'exists:currencies,id']),

            ImportColumn::make('description')
                ->label('Description'),

            ImportColumn::make('barcode')
                ->label('Barcode')
                ->rules(['nullable', 'string', 'unique:assets,barcode']),

            ImportColumn::make('qr_code')
                ->label('QR Code')
                ->rules(['nullable', 'string', 'unique:assets,qr_code']),

            ImportColumn::make('rfid_tag')
                ->label('RFID Tag')
                ->rules(['nullable', 'string', 'unique:assets,rfid_tag']),

            ImportColumn::make('donor_id')
                ->label('Donor ID')
                ->numeric()
                ->rules(['nullable', 'integer', 'exists:donors,id']),
        ];
    }

    public function resolveRecord(): Asset
    {
        $record = new Asset();
        $record->is_fixed_asset = true;
        return $record;
    }

    public function getValidationMessages(): array
    {
        return [
            'asset_category_id.exists' => "Category ID ':input' does not exist in the asset_categories table.",
            'location_id.exists' => "Location ID ':input' does not exist in the locations table.",
            'department_id.exists' => "Department ID ':input' does not exist in the departments table.",
            'currency_id.exists' => "Currency ID ':input' does not exist in the currencies table.",
            'donor_id.exists' => "Donor ID ':input' does not exist in the donors table.",
            'barcode.unique' => "Barcode ':input' has already been taken.",
            'qr_code.unique' => "QR Code ':input' has already been taken.",
            'rfid_tag.unique' => "RFID Tag ':input' has already been taken.",
        ];
    }

    protected function afterFill(): void
    {
        $this->record->is_fixed_asset = true;
        $this->record->quantity ??= 1;
        $this->record->min_stock_level ??= 0;
        $this->record->status ??= 'available';
        $this->record->acquisition_type ??= 'Purchase';
    }

    public static function getCompletedNotificationBody(Import $import): string
    {
        $successful = $import->successful_rows;
        $failed = $import->getFailedRowsCount();
        $total = $import->total_rows;

        $body = "Fixed Asset import complete — {$successful} of {$total} " . str('record')->plural($total) . ' imported successfully.';

        if ($failed) {
            $body .= " {$failed} " . str('row')->plural($failed) . ' failed. This usually happens because there is no data with the specified ID for a related object. Download the error report below to see exactly what needs fixing.';
        }

        return $body;
    }
}
