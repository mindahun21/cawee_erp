<?php

namespace App\Filament\Resources\ME\IndicatorResource\Pages;

use App\Filament\Resources\ME\IndicatorResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditIndicator extends EditRecord
{
    protected \Filament\Support\Enums\Width | string | null $maxContentWidth = \Filament\Support\Enums\Width::Full;
    protected static string $resource = IndicatorResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    protected function mutateFormDataBeforeSave(array $data): array
    {
        $data['code'] = IndicatorResource::uniqueIndicatorCode(
            isset($data['project_id']) ? (int) $data['project_id'] : null,
            isset($data['name']) ? (string) $data['name'] : '',
            $this->record?->getKey() !== null ? (int) $this->record->getKey() : null
        );

        return $data;
    }
}
