<?php

namespace App\Filament\Resources\Procurement\Budgets\Pages;

use App\Filament\Resources\Procurement\Budgets\ProcurementBudgetResource;
use Filament\Resources\Pages\CreateRecord;

class CreateProcurementBudget extends CreateRecord
{
    protected static string $resource = ProcurementBudgetResource::class;
    protected function getRedirectUrl(): string { return $this->getResource()::getUrl('index'); }
}
