<?php
namespace App\Filament\Resources\Finance\Budgets\Pages;
use App\Filament\Resources\Finance\Budgets\BudgetResource;
use Filament\Resources\Pages\EditRecord;
class EditBudgets extends EditRecord {
    protected static string $resource = BudgetResource::class;
    protected function getRedirectUrl(): string { return $this->getResource()::getUrl('view', ['record' => $this->record]); }
}
