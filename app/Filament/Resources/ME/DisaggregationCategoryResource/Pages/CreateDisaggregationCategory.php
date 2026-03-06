<?php

namespace App\Filament\Resources\ME\DisaggregationCategoryResource\Pages;

use App\Filament\Resources\ME\DisaggregationCategoryResource;
use Filament\Resources\Pages\CreateRecord;

class CreateDisaggregationCategory extends CreateRecord
{
    protected \Filament\Support\Enums\Width | string | null $maxContentWidth = \Filament\Support\Enums\Width::Full;
    protected static string $resource = DisaggregationCategoryResource::class;
}
