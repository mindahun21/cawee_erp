<?php
namespace App\Filament\Resources\Finance\Budgets\CostBuildupResource\Pages;
use App\Filament\Resources\Finance\Budgets\CostBuildupResource;
use Filament\Resources\Pages\ManageRecords;
class ManageCostBuildups extends ManageRecords {
    protected static string $resource = CostBuildupResource::class;
    protected function getHeaderActions(): array { return [\Filament\Actions\CreateAction::make()]; }
}
