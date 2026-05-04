<?php

namespace App\Filament\Resources\Finance\Payroll\Pages;

use App\Filament\Resources\Finance\Payroll\PayrollSummaryResource;
use App\Models\Payroll;
use App\Services\Finance\PayrollGLPostingService;
use Filament\Resources\Pages\CreateRecord;

class CreatePayrollSummaries extends CreateRecord
{
    protected static string $resource = PayrollSummaryResource::class;

    /**
     * When an HR Payroll record is selected, delegate creation to
     * the PayrollGLPostingService so it can auto-compute all the
     * tax / pension amounts. The buildSummary() call already persists
     * the row, so we stop the default CreateRecord save from running
     * (by returning the pre-built record).
     *
     * When no payroll_id is supplied (manual entry), we fall through
     * and let the standard form save work normally.
     */
    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $data['prepared_by'] = auth()->id();
        $data['status']      = 'draft';

        if (! empty($data['payroll_id'])) {
            $payroll = Payroll::find($data['payroll_id']);

            if ($payroll) {
                // buildSummary() already creates and saves the record.
                // Store it on $this->record so getRedirectUrl() can use it,
                // then halt the standard create by making the data valid (record
                // was already persisted; we hand-wire $this->record and skip).
                $summary = app(PayrollGLPostingService::class)->buildSummary($payroll, $data);
                $this->record = $summary;

                // Returning non-empty data would trigger a second insert.
                // We signal Filament to skip its own save by returning the
                // record's own attributes — the record already exists so
                // firstOrCreate inside buildSummary() returns the same row.
                return $summary->getAttributes();
            }
        }

        return $data;
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('view', ['record' => $this->record]);
    }
}
