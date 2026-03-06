<?php

namespace App\Filament\Imports;

use App\Models\Asset;
use Filament\Actions\Imports\ImportColumn;
use Filament\Actions\Imports\Importer;
use Filament\Actions\Imports\Models\Import;

class InventoryImporter extends Importer
{
    protected static ?string $model = Asset::class;

    public static function getColumns(): array
    {
        return [
            ImportColumn::make('name')
                ->label('Item Name')
                ->requiredMapping()
                ->rules(['required', 'string', 'max:255']),

            ImportColumn::make('asset_category_id')
                ->label('Category ID')
                ->requiredMapping()
                ->numeric()
                ->rules(['required', 'integer', 'exists:asset_categories,id']),

            ImportColumn::make('status')
                ->label('Status')
                ->rules(['nullable', 'in:available,assigned,maintenance,disposed,lost']),

            ImportColumn::make('quantity')
                ->label('Total Quantity')
                ->numeric()
                ->rules(['nullable', 'integer', 'min:0']),

            ImportColumn::make('min_stock_level')
                ->label('Minimum Stock Level')
                ->numeric()
                ->rules(['nullable', 'integer', 'min:0']),

            ImportColumn::make('acquisition_type')
                ->label('Acquisition Type')
                ->rules(['nullable', 'in:Purchase,Donation,Lease']),

            ImportColumn::make('description')
                ->label('Description'),
        ];
    }

    public function resolveRecord(): Asset
    {
        $record = new Asset();
        $record->is_fixed_asset = false;
        return $record;
    }

    public function getValidationMessages(): array
    {
        return [
            'asset_category_id.exists' => "Category ID ':input' does not exist in the asset_categories table.",
        ];
    }

    protected function afterFill(): void
    {
        $this->record->is_fixed_asset = false;
        $this->record->quantity ??= 0;
        $this->record->min_stock_level ??= 0;
        $this->record->status ??= 'available';
        $this->record->acquisition_type ??= 'Purchase';
    }

    public static function getCompletedNotificationBody(Import $import): string
    {
        $successful = $import->successful_rows;
        $failed = $import->getFailedRowsCount();
        $total = $import->total_rows;

        $body = "Inventory import complete — {$successful} of {$total} " . str('item')->plural($total) . ' imported successfully.';

        if ($failed) {
            $body .= " {$failed} " . str('item')->plural($failed) . ' failed. This usually happens because there is no data with the specified ID for a related object. Download the error report below to see exactly what needs fixing.';
        }

        return $body;
    }
}
