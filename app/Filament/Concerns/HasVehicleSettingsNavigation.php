<?php

namespace App\Filament\Concerns;

use App\Filament\Resources\Settings\VehicleStatuses\VehicleStatusResource;
use App\Filament\Resources\Settings\VehicleTypes\VehicleTypeResource;
use Filament\Navigation\NavigationItem;

trait HasVehicleSettingsNavigation
{
    public function getSubNavigation(): array
    {
        return [
            NavigationItem::make('Vehicle Types')
                ->icon('heroicon-o-rectangle-stack')
                ->url(VehicleTypeResource::getUrl())
                ->isActiveWhen(fn () => request()->routeIs(VehicleTypeResource::getRouteBaseName() . '.*')),

            NavigationItem::make('Vehicle Statuses')
                ->icon('heroicon-o-signal')
                ->url(VehicleStatusResource::getUrl())
                ->isActiveWhen(fn () => request()->routeIs(VehicleStatusResource::getRouteBaseName() . '.*')),

            NavigationItem::make('Service Types')
                ->icon('heroicon-o-wrench-screwdriver')
                ->url(\App\Filament\Resources\Settings\VehicleServiceTypes\VehicleServiceTypes\VehicleServiceTypeResource::getUrl())
                ->isActiveWhen(fn () => request()->routeIs(\App\Filament\Resources\Settings\VehicleServiceTypes\VehicleServiceTypes\VehicleServiceTypeResource::getRouteBaseName() . '.*')),
        ];
    }
}
