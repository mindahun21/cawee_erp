<?php

declare(strict_types=1);

namespace App\Filament\Imports;

use App\Models\ME\MeBeneficiary;
use Filament\Actions\Imports\ImportColumn;
use Filament\Actions\Imports\Importer;
use Filament\Actions\Imports\Models\Import;
use Illuminate\Support\Str;

class BrtBeneficiaryImporter extends Importer
{
    protected static ?string $model = MeBeneficiary::class;

    public static function getColumns(): array
    {
        return [
            ImportColumn::make('full_name')
                ->label('Child Name')
                ->requiredMapping()
                ->rules(['required', 'string', 'max:150']),

            ImportColumn::make('father_name')
                ->label("Father's Name")
                ->rules(['nullable', 'string', 'max:100']),

            ImportColumn::make('grandfather_name')
                ->label("Grandfather's Name")
                ->rules(['nullable', 'string', 'max:100']),

            ImportColumn::make('child_code')
                ->label('Child Code')
                ->requiredMapping()
                ->rules(['required', 'string', 'max:30']),

            ImportColumn::make('gender')
                ->label('Sex')
                ->requiredMapping()
                ->rules(['required', 'string', 'in:male,female,m,f,M,F']),

            ImportColumn::make('date_of_birth')
                ->label('DOB')
                ->rules(['nullable', 'date']),

            ImportColumn::make('age')
                ->label('Age')
                ->numeric()
                ->rules(['nullable', 'integer', 'min:0', 'max:130']),

            ImportColumn::make('region')
                ->label('Region')
                ->rules(['nullable', 'string', 'max:100']),

            ImportColumn::make('woreda')
                ->label('Woreda')
                ->rules(['nullable', 'string', 'max:100']),

            ImportColumn::make('kebele')
                ->label('Kebele')
                ->rules(['nullable', 'string', 'max:100']),

            ImportColumn::make('address')
                ->label('Address')
                ->rules(['nullable', 'string']),
        ];
    }

    public function resolveRecord(): MeBeneficiary
    {
        $childCode = trim((string) ($this->data['child_code'] ?? ''));

        if ($childCode !== '') {
            return MeBeneficiary::query()->firstOrNew(['child_code' => $childCode]);
        }

        return new MeBeneficiary();
    }

    protected function afterFill(): void
    {
        $this->record->full_name = Str::title(trim((string) ($this->record->full_name ?? '')));
        $this->record->father_name = $this->record->father_name ? Str::title(trim((string) $this->record->father_name)) : null;
        $this->record->grandfather_name = $this->record->grandfather_name ? Str::title(trim((string) $this->record->grandfather_name)) : null;
        $this->record->child_code = strtoupper(trim((string) ($this->record->child_code ?? '')));

        $gender = strtolower(trim((string) ($this->record->gender ?? '')));
        $this->record->gender = in_array($gender, ['m', 'male'], true) ? 'male' : 'female';
        $this->record->status ??= 'active';
        $this->record->disability_status ??= 'none';
        $this->record->registered_at ??= now()->toDateString();
        $this->record->registered_by ??= auth()->id();
    }

    public static function getCompletedNotificationBody(Import $import): string
    {
        $successful = $import->successful_rows;
        $failed = $import->getFailedRowsCount();
        $total = $import->total_rows;

        $body = "BRT beneficiary import complete - {$successful} of {$total} records processed successfully.";

        if ($failed) {
            $body .= " {$failed} rows failed validation. Download the failure report to fix and retry.";
        }

        return $body;
    }
}
