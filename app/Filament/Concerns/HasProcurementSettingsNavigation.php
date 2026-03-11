<?php

namespace App\Filament\Concerns;

use App\Filament\Resources\Procurement\Settings\ApprovalWorkflowResource;
use App\Filament\Resources\Procurement\Settings\ProcurementCurrencyResource;
use App\Filament\Resources\Procurement\Budgets\ProcurementBudgetResource;
use Filament\Navigation\NavigationItem;

trait HasProcurementSettingsNavigation
{
    public function getSubNavigation(): array
    {
        return [
            NavigationItem::make('Currencies')
                ->icon('heroicon-o-currency-dollar')
                ->url(ProcurementCurrencyResource::getUrl())
                ->isActiveWhen(fn () => request()->routeIs(ProcurementCurrencyResource::getRouteBaseName() . '.*')),

            NavigationItem::make('Categories')
                ->icon('heroicon-o-tag')
                ->url(\App\Filament\Resources\Procurement\Settings\ProcurementCategoryResource::getUrl())
                ->isActiveWhen(fn () => request()->routeIs(\App\Filament\Resources\Procurement\Settings\ProcurementCategoryResource::getRouteBaseName() . '.*')),

            NavigationItem::make('Procurement Methods')
                ->icon('heroicon-o-clipboard-document-check')
                ->url(\App\Filament\Resources\Procurement\Settings\ProcurementMethodResource::getUrl())
                ->isActiveWhen(fn () => request()->routeIs(\App\Filament\Resources\Procurement\Settings\ProcurementMethodResource::getRouteBaseName() . '.*')),

            NavigationItem::make('Units of Measurement')
                ->icon('heroicon-o-scale')
                ->url(\App\Filament\Resources\Procurement\Settings\ProcurementUnitResource::getUrl())
                ->isActiveWhen(fn () => request()->routeIs(\App\Filament\Resources\Procurement\Settings\ProcurementUnitResource::getRouteBaseName() . '.*')),

            NavigationItem::make('Bid Securities')
                ->icon('heroicon-o-shield-check')
                ->url(\App\Filament\Resources\Procurement\Settings\BidSecurityResource::getUrl())
                ->isActiveWhen(fn () => request()->routeIs(\App\Filament\Resources\Procurement\Settings\BidSecurityResource::getRouteBaseName() . '.*')),

            NavigationItem::make('Contract Types')
                ->icon('heroicon-o-document-text')
                ->url(\App\Filament\Resources\Procurement\Settings\ContractTypeResource::getUrl())
                ->isActiveWhen(fn () => request()->routeIs(\App\Filament\Resources\Procurement\Settings\ContractTypeResource::getRouteBaseName() . '.*')),

            NavigationItem::make('Approval Workflows')
                ->icon('heroicon-o-check-badge')
                ->url(ApprovalWorkflowResource::getUrl())
                ->isActiveWhen(fn () => request()->routeIs(ApprovalWorkflowResource::getRouteBaseName() . '.*')),

            NavigationItem::make('Budget Lines')
                ->icon('heroicon-o-banknotes')
                ->url(ProcurementBudgetResource::getUrl())
                ->isActiveWhen(fn () => request()->routeIs(ProcurementBudgetResource::getRouteBaseName() . '.*')),
        ];
    }
}
