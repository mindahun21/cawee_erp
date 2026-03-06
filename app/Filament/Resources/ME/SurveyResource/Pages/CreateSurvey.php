<?php

namespace App\Filament\Resources\ME\SurveyResource\Pages;

use App\Filament\Resources\ME\SurveyResource;
use Filament\Resources\Pages\CreateRecord;

class CreateSurvey extends CreateRecord
{
    protected \Filament\Support\Enums\Width | string | null $maxContentWidth = \Filament\Support\Enums\Width::Full;
    protected static string $resource = SurveyResource::class;
}
