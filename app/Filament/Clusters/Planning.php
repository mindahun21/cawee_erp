<?php

namespace App\Filament\Clusters;

use App\Traits\BelongsToModuleCluster;
use Filament\Clusters\Cluster;

use UnitEnum;
use BackedEnum;

class Planning extends Cluster
{
    use BelongsToModuleCluster;

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-presentation-chart-bar';
    
    protected static ?string $navigationLabel = 'Planning & Reporting';

    protected static string|UnitEnum|null $navigationGroup = 'Systems Control';
}
