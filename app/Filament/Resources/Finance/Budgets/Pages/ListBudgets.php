<?php
namespace App\Filament\Resources\Finance\Budgets\Pages;
use App\Filament\Resources\Finance\Budgets\BudgetResource;
use Filament\Resources\Pages\ListRecords;
class ListBudgets extends ListRecords {
    protected static string $resource = BudgetResource::class;
    protected function getHeaderActions(): array { return [\Filament\Actions\CreateAction::make()]; }
}
