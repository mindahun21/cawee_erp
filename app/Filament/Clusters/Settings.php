<?php

namespace App\Filament\Clusters;

use App\Traits\BelongsToModuleCluster;

use Filament\Clusters\Cluster;

use BackedEnum;
use UnitEnum;

class Settings extends Cluster
{
    use BelongsToModuleCluster;

    protected static BackedEnum|string|null $navigationIcon = 'heroicon-o-cog-6-tooth';
    
    protected static string|UnitEnum|null $navigationGroup = 'Settings';
}

