<?php
namespace App\Filament\Resources\Finance\Bank\ReconciliationResource\Pages;

use App\Filament\Resources\Finance\Bank\ReconciliationResource;
use Filament\Actions\Action;
use Filament\Actions\ViewAction;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\EditRecord;

class EditReconciliation extends EditRecord
{
    protected static string $resource = ReconciliationResource::class;

    public function getSubheading(): string|\Illuminate\Contracts\Support\Htmlable|null
    {
        $diff = abs((float) $this->record->difference);

        if ($diff < 0.01) {
            return '✅ Accounts are balanced — review the items below then click Mark Reconciled on the View page.';
        }

        return sprintf(
            'Add all outstanding deposits and unpresented cheques below, then save. ' .
            'Current difference: %s — it must reach 0.00 before you can mark this reconciled.',
            number_format($diff, 2)
        );
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('back_to_reconcile')
                ->label('← Back to Reconcile')
                ->color('gray')
                ->url(route('filament.admin.pages.finance.reconcile'))
                ->outlined(),

            ViewAction::make()->label('View Summary'),
        ];
    }

    protected function afterSave(): void
    {
        $this->record->calculateTotals();

        $diff = abs((float) $this->record->difference);

        if ($diff < 0.01) {
            Notification::make()
                ->success()
                ->title('Accounts balanced!')
                ->body('The difference is now 0.00. Open the View Summary to mark this reconciliation complete.')
                ->send();
        } else {
            Notification::make()
                ->warning()
                ->title('Saved — difference remaining: ' . number_format($diff, 2))
                ->body('Continue adding or clearing items until the difference reaches 0.00.')
                ->send();
        }
    }
}
