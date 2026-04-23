<?php

namespace App\Filament\Imports;

use App\Models\Recruitment\RecruitmentCandidate;
use Filament\Actions\Imports\ImportColumn;
use Filament\Actions\Imports\Importer;
use Filament\Actions\Imports\Models\Import;
use Illuminate\Support\Number;

class RecruitmentCandidateImporter extends Importer
{
    protected static ?string $model = RecruitmentCandidate::class;

    public static function getColumns(): array
    {
        return [
            ImportColumn::make('candidate_code')->rules(['max:255']),
            ImportColumn::make('first_name')->requiredMapping()->rules(['required', 'max:255']),
            ImportColumn::make('last_name')->requiredMapping()->rules(['required', 'max:255']),
            ImportColumn::make('email')->requiredMapping()->rules(['required', 'email', 'max:255']),
            ImportColumn::make('phone')->rules(['max:255']),
            ImportColumn::make('gender')->rules(['max:20']),
            ImportColumn::make('nationality')->rules(['max:100']),
            ImportColumn::make('linkedin_url')->rules(['max:255']),
            ImportColumn::make('seniority')->rules(['max:50']),
        ];
    }

    public function resolveRecord(): ?RecruitmentCandidate
    {
        // Find existing candidate by email to update without overwriting source
        $existing = RecruitmentCandidate::query()
            ->where('email', $this->data['email'])
            ->first();

        if ($existing) {
            return $existing;
        }

        return new RecruitmentCandidate();
    }

    public static function getCompletedNotificationBody(Import $import): string
    {
        $body = 'Your recruitment candidate import has completed and ' . Number::format($import->successful_rows) . ' ' . str('row')->plural($import->successful_rows) . ' imported.';

        if ($failedRowsCount = $import->getFailedRowsCount()) {
            $body .= ' ' . Number::format($failedRowsCount) . ' ' . str('row')->plural($failedRowsCount) . ' failed to import.';
        }

        return $body;
    }
}
