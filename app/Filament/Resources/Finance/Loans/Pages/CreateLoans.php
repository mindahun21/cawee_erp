<?php

namespace App\Filament\Resources\Finance\Loans\Pages;

use App\Filament\Resources\Finance\Loans\LoanResource;
use App\Services\Finance\PaymentRequisitionService;
use Filament\Resources\Pages\CreateRecord;

class CreateLoans extends CreateRecord
{
    protected static string $resource = LoanResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $svc = app(PaymentRequisitionService::class);

        $data['loan_reference']    = $svc->generateLoanReference(now()->year);
        $data['prepared_by']       = auth()->id();
        $data['status']            = 'active';
        $data['outstanding_balance'] = $data['principal_amount'];

        // Auto-compute maturity date
        if (! empty($data['start_repayment_date']) && ! empty($data['tenor_months'])) {
            $data['maturity_date'] = \Carbon\Carbon::parse($data['start_repayment_date'])
                ->addMonths((int) $data['tenor_months'])
                ->toDateString();
        }

        // Total interest estimate (simple interest)
        if (! empty($data['principal_amount']) && ! empty($data['interest_rate']) && ! empty($data['tenor_months'])) {
            $data['total_interest'] = round(
                (float) $data['principal_amount'] *
                (float) $data['interest_rate'] *
                ((int) $data['tenor_months'] / 12),
                2
            );
        }

        return $data;
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('view', ['record' => $this->record]);
    }
}
