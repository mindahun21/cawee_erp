<?php
namespace App\Filament\Resources\Finance\Payroll\Pages;
use App\Filament\Resources\Finance\Payroll\PayrollSummaryResource;
use App\Services\Finance\PayrollGLPostingService;
use Filament\Resources\Pages\CreateRecord;
class CreatePayrollSummaries extends CreateRecord {
    protected static string $resource = PayrollSummaryResource::class;
    protected function mutateFormDataBeforeCreate(array $data): array {
        $data['prepared_by'] = auth()->id();
        $data['status']      = 'draft';
        // Auto-compute if payroll_id provided
        if (!empty($data['payroll_id'])) {
            $payroll = \App\Models\Payroll::find($data['payroll_id']);
            if ($payroll) {
                $summary = app(PayrollGLPostingService::class)->buildSummary($payroll, $data);
                // redirect to the created record
                $this->record = $summary;
                return [];
            }
        }
        return $data;
    }
    protected function getRedirectUrl(): string {
        return $this->getResource()::getUrl('view', ['record' => $this->record]);
    }
}
