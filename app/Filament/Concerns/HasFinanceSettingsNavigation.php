<?php

namespace App\Filament\Concerns;

use Filament\Navigation\NavigationItem;
use App\Filament\Resources\Finance\Settings\AccountTypeResource;
use App\Filament\Resources\Finance\Settings\AccountSubClassificationResource;
use App\Filament\Resources\Finance\Settings\AccountingPeriodResource;
use App\Filament\Resources\Finance\Settings\BudgetTypeResource;
use App\Filament\Resources\Finance\Settings\CashierResource;
use App\Filament\Resources\Finance\Settings\CostCenterResource;
use App\Filament\Resources\Finance\Settings\FinanceSettingResource;
use App\Filament\Resources\Finance\Perdiem\PerdiemTaxRuleResource;
use App\Filament\Resources\Finance\Settings\PerdiemTypeResource;
use App\Filament\Resources\Finance\Settings\TaxTypeResource;
use App\Filament\Resources\Finance\Settings\FinancialStatementCategoryResource;

trait HasFinanceSettingsNavigation
{
    public function getSubNavigation(): array
    {
        return [
            NavigationItem::make('Account Types')
                ->icon('heroicon-o-rectangle-stack')
                ->url(AccountTypeResource::getUrl())
                ->isActiveWhen(fn () => request()->routeIs(AccountTypeResource::getRouteBaseName() . '.*')),

            NavigationItem::make('Account Sub-Classifications')
                ->icon('heroicon-o-tag')
                ->url(AccountSubClassificationResource::getUrl())
                ->isActiveWhen(fn () => request()->routeIs(AccountSubClassificationResource::getRouteBaseName() . '.*')),

            NavigationItem::make('Statement Categories')
                ->icon('heroicon-o-document-chart-bar')
                ->url(FinancialStatementCategoryResource::getUrl())
                ->isActiveWhen(fn () => request()->routeIs(FinancialStatementCategoryResource::getRouteBaseName() . '.*')),

            NavigationItem::make('Budget Types')
                ->icon('heroicon-o-banknotes')
                ->url(BudgetTypeResource::getUrl())
                ->isActiveWhen(fn () => request()->routeIs(BudgetTypeResource::getRouteBaseName() . '.*')),

            NavigationItem::make('Tax Types')
                ->icon('heroicon-o-receipt-percent')
                ->url(TaxTypeResource::getUrl())
                ->isActiveWhen(fn () => request()->routeIs(TaxTypeResource::getRouteBaseName() . '.*')),

            NavigationItem::make('Per Diem Types')
                ->icon('heroicon-o-map-pin')
                ->url(PerdiemTypeResource::getUrl())
                ->isActiveWhen(fn () => request()->routeIs(PerdiemTypeResource::getRouteBaseName() . '.*')),

            NavigationItem::make('Per Diem Tax Rules')
                ->icon('heroicon-o-receipt-percent')
                ->url(PerdiemTaxRuleResource::getUrl())
                ->isActiveWhen(fn () => request()->routeIs(PerdiemTaxRuleResource::getRouteBaseName() . '.*')),

            NavigationItem::make('Cost Centers')
                ->icon('heroicon-o-building-office-2')
                ->url(CostCenterResource::getUrl())
                ->isActiveWhen(fn () => request()->routeIs(CostCenterResource::getRouteBaseName() . '.*')),

            NavigationItem::make('Cashiers')
                ->icon('heroicon-o-user-circle')
                ->url(CashierResource::getUrl())
                ->isActiveWhen(fn () => request()->routeIs(CashierResource::getRouteBaseName() . '.*')),

            NavigationItem::make('Accounting Periods')
                ->icon('heroicon-o-calendar-days')
                ->url(AccountingPeriodResource::getUrl())
                ->isActiveWhen(fn () => request()->routeIs(AccountingPeriodResource::getRouteBaseName() . '.*')),

            NavigationItem::make('System Defaults')
                ->icon('heroicon-o-cog-6-tooth')
                ->url(FinanceSettingResource::getUrl())
                ->isActiveWhen(fn () => request()->routeIs(FinanceSettingResource::getRouteBaseName() . '.*')),
        ];
    }
}
