<?php

namespace App\Filament\Clusters;

use Filament\Clusters\Cluster;

use BackedEnum;

class Settings extends Cluster
{
    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-cog-6-tooth';
    
    protected static ?string $navigationLabel = 'Settings';

    protected static string|\UnitEnum|null $navigationGroup = 'Inventory Mgmt';

    protected static ?int $navigationSort = 5;
}
