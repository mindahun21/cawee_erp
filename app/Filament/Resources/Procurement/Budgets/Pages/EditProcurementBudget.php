<?php

namespace App\Filament\Resources\Procurement\Budgets\Pages;

use App\Filament\Concerns\HasProcurementSettingsNavigation;
use App\Filament\Resources\Procurement\Budgets\ProcurementBudgetResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditProcurementBudget extends EditRecord
{
    use HasProcurementSettingsNavigation;

    protected static string $resource = ProcurementBudgetResource::class;
    protected function getHeaderActions(): array { return [DeleteAction::make()]; }
    protected function getRedirectUrl(): string { return $this->getResource()::getUrl('index'); }
}
