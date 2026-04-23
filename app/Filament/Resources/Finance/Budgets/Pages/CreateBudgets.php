<?php
namespace App\Filament\Resources\Finance\Budgets\Pages;
use App\Filament\Resources\Finance\Budgets\BudgetResource;
use Filament\Resources\Pages\CreateRecord;
class CreateBudgets extends CreateRecord {
    protected static string $resource = BudgetResource::class;
    protected function mutateFormDataBeforeCreate(array $data): array {
        $year = $data['fiscal_year'] ?? now()->year;
        $last = \App\Models\Finance\Budget::withTrashed()->where('budget_code','like',"BDG-{$year}-%")
            ->orderByRaw('LENGTH(budget_code) DESC')->orderBy('budget_code','desc')->value('budget_code');
        $seq = $last ? ((int) last(explode('-', $last))) + 1 : 1;
        $data['budget_code'] = sprintf('BDG-%d-%04d', $year, $seq);
        // compute total_budgeted per line
        if (!empty($data['lines'])) {
            foreach ($data['lines'] as &$line) {
                $line['total_budgeted'] = array_sum(array_filter([$line['q1_amount']??0,$line['q2_amount']??0,$line['q3_amount']??0,$line['q4_amount']??0],'is_numeric'));
            }
        }
        return $data;
    }
    protected function getRedirectUrl(): string { return $this->getResource()::getUrl('view', ['record' => $this->record]); }
}
