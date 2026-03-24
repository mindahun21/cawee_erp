<?php

declare(strict_types=1);

namespace App\Filament\Resources\BRT\HighRiskAlertResource\Pages;

use App\Filament\Resources\BRT\HighRiskAlertResource;
use Filament\Resources\Pages\ListRecords;

class ListHighRiskAlerts extends ListRecords
{
    protected static string $resource = HighRiskAlertResource::class;
}
