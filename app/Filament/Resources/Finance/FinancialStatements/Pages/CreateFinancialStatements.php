<?php
namespace App\Filament\Resources\Finance\FinancialStatements\Pages;
use App\Filament\Resources\Finance\FinancialStatements\FinancialStatementResource;
use Filament\Resources\Pages\CreateRecord;
class CreateFinancialStatements extends CreateRecord {
    protected static string $resource = FinancialStatementResource::class;
    protected function mutateFormDataBeforeCreate(array $data): array {
        $year = $data['fiscal_year'] ?? now()->year;
        $last = \App\Models\Finance\FinancialStatement::where('reference','like',"FS-{$year}-%")
            ->orderByRaw('LENGTH(reference) DESC')->orderBy('reference','desc')->value('reference');
        $seq = $last ? ((int) last(explode('-', $last))) + 1 : 1;
        $data['reference'] = sprintf('FS-%d-%04d', $year, $seq);
        $data['prepared_by'] = auth()->id();
        return $data;
    }
    protected function getRedirectUrl(): string { return $this->getResource()::getUrl('view', ['record' => $this->record]); }
}
