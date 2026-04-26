<?php

namespace App\Filament\Clusters;

use App\Traits\BelongsToModuleCluster;

use Filament\Clusters\Cluster;

use BackedEnum;

class CarRentManagement extends Cluster
{
    use BelongsToModuleCluster;

    protected static BackedEnum|string|null $navigationIcon = 'heroicon-o-truck';
}

