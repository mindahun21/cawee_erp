<?php

namespace App\Filament\Resources\Finance\Bank\ReconciliationResource\Pages;

use App\Filament\Resources\Finance\Bank\ReconciliationResource;
use App\Models\Finance\BankReconciliation;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\CreateRecord;

class CreateReconciliation extends CreateRecord
{
    protected static string $resource = ReconciliationResource::class;

    /**
     * Reconciliations must be started through the Reconcile wizard page, which
     * auto-computes the GL balance and sets up the record correctly.
     * Redirect anyone who hits /create directly.
     */
    public function mount(): void
    {
        Notification::make()
            ->warning()
            ->title('Use the Reconcile wizard')
            ->body('Please start a new reconciliation from the Finance → Reconcile page.')
            ->send();

        $this->redirect(route('filament.admin.pages.finance.reconcile'));
    }

    /**
     * Safety fallback — should not normally run since mount() redirects.
     * Computes gl_balance from the ledger if not already set.
     */
    protected function mutateFormDataBeforeCreate(array $data): array
    {
        // Auto-reference
        $year = now()->year;
        $last = BankReconciliation::withTrashed()->where('reference', 'like', "BR-{$year}-%")
            ->orderByRaw('LENGTH(reference) DESC')
            ->orderBy('reference', 'desc')
            ->value('reference');
        $seq = $last ? ((int) last(explode('-', $last))) + 1 : 1;
        $data['reference']   = sprintf('BR-%d-%04d', $year, $seq);
        $data['prepared_by'] = auth()->id();

        // Auto-compute gl_balance from the ledger if missing
        if (empty($data['gl_balance']) && ! empty($data['bank_account_id']) && ! empty($data['statement_date'])) {
            $data['gl_balance'] = BankReconciliation::glBalanceFor(
                (int) $data['bank_account_id'],
                $data['statement_date']
            );
        }

        // Ensure gl_balance is never null
        $data['gl_balance'] = $data['gl_balance'] ?? 0;

        return $data;
    }

    protected function afterCreate(): void
    {
        $this->record->calculateTotals();
    }
}

