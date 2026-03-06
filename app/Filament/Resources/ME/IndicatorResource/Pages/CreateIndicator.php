<?php

namespace App\Filament\Resources\ME\IndicatorResource\Pages;

use App\Filament\Resources\ME\IndicatorResource;
use Filament\Resources\Pages\CreateRecord;

class CreateIndicator extends CreateRecord
{
    protected \Filament\Support\Enums\Width | string | null $maxContentWidth = \Filament\Support\Enums\Width::Full;
    protected static string $resource = IndicatorResource::class;

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $data['code'] = IndicatorResource::uniqueIndicatorCode(
            isset($data['project_id']) ? (int) $data['project_id'] : null,
            isset($data['name']) ? (string) $data['name'] : ''
        );

        return $data;
    }
}
