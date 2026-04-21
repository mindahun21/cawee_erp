<?php
namespace App\Filament\Resources\Finance\Bank\ReconciliationResource\Pages;
use App\Filament\Resources\Finance\Bank\ReconciliationResource;
use Filament\Resources\Pages\CreateRecord;
class CreateReconciliation extends CreateRecord {
    protected static string $resource = ReconciliationResource::class;
    protected function mutateFormDataBeforeCreate(array $data): array {
        $year = now()->year;
        $last = \App\Models\Finance\BankReconciliation::where('reference', 'like', "BR-{$year}-%")
            ->orderByRaw('LENGTH(reference) DESC')->orderBy('reference', 'desc')->value('reference');
        $seq = $last ? ((int) last(explode('-', $last))) + 1 : 1;
        $data['reference'] = sprintf('BR-%d-%04d', $year, $seq);
        $data['prepared_by'] = auth()->id();
        return $data;
    }
    protected function afterCreate(): void {
        $this->record->calculateTotals();
    }
}
