<?php

namespace App\Filament\Clusters;

use Filament\Clusters\Cluster;
use BackedEnum;

class CarRentManagement extends Cluster
{
    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-truck';

    protected static ?string $navigationLabel = 'Car & Rent Management';

    protected static string|\UnitEnum|null $navigationGroup = 'Human Resources';

    protected static ?int $navigationSort = 92;
}
