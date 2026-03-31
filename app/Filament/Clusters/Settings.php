<?php

namespace App\Filament\Clusters;

use Filament\Clusters\Cluster;

use BackedEnum;
use UnitEnum;

class Settings extends Cluster
{
    protected static BackedEnum|string|null $navigationIcon = 'heroicon-o-cog-6-tooth';
    
    protected static string|UnitEnum|null $navigationGroup = 'Settings';
}

