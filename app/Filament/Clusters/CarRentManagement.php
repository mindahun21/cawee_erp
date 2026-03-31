<?php

namespace App\Filament\Clusters;

use Filament\Clusters\Cluster;

use BackedEnum;

class CarRentManagement extends Cluster
{
    protected static BackedEnum|string|null $navigationIcon = 'heroicon-o-truck';
}

