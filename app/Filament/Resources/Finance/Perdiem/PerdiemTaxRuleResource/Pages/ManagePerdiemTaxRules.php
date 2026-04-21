<?php
namespace App\Filament\Resources\Finance\Perdiem\PerdiemTaxRuleResource\Pages;
use App\Filament\Concerns\HasFinanceSettingsNavigation;
use App\Filament\Resources\Finance\Perdiem\PerdiemTaxRuleResource;
use Filament\Resources\Pages\ManageRecords;
class ManagePerdiemTaxRules extends ManageRecords {
    use HasFinanceSettingsNavigation;
    protected static string $resource = PerdiemTaxRuleResource::class;
    protected function getHeaderActions(): array { return [\Filament\Actions\CreateAction::make()]; }
}
