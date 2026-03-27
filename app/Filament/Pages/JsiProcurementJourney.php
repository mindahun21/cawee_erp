<?php

namespace App\Filament\Pages;

use App\Services\Procurement\JsiThresholds;
use Filament\Pages\Page;
use BackedEnum;

class JsiProcurementJourney extends Page
{
    protected string $view = 'filament.pages.jsi-procurement-journey';

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-map';

    protected static string|\UnitEnum|null $navigationGroup = 'Procurement';

    protected static ?string $navigationLabel = 'Procurement Journey (JSI)';

    protected static ?string $title = 'JSI Procurement Journey — End-to-End Process Guide';

    protected static ?int $navigationSort = 0;

    protected function getViewData(): array
    {
        return [
            'tiers' => JsiThresholds::tiers(),
        ];
    }
}
