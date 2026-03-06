<?php

namespace App\Filament\Resources\Procurement\Budgets\Pages;

use App\Filament\Resources\Procurement\Budgets\ProcurementBudgetResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListProcurementBudgets extends ListRecords
{
    protected static string $resource = ProcurementBudgetResource::class;
    protected function getHeaderActions(): array { return [CreateAction::make()->label('New Budget Line')]; }
}
