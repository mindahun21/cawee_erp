<?php

namespace App\Filament\Resources\Assets\Pages;

use App\Filament\Resources\Assets\AssetResource;
use Filament\Resources\Pages\CreateRecord;

class CreateAsset extends CreateRecord
{
    protected static string $resource = AssetResource::class;

    protected function beforeCreate(): void
    {
        $data = $this->form->getRawState();
        $model = \App\Models\AssetModel::with(['type', 'category'])->find($data['asset_model_id'] ?? null);

        if ($model) {
            $isVehicle = str_contains(strtolower($model->type?->name ?? ''), 'vehicle') || 
                        str_contains(strtolower($model->category?->name ?? ''), 'vehicle');

            if ($isVehicle) {
                // Ensure the user knows why they are being redirected
                \Filament\Notifications\Notification::make()
                    ->title('Redirecting to Vehicle module')
                    ->body('This asset model is configured as a Vehicle. Vehicles are managed separately.')
                    ->info()
                    ->send();

                $this->redirect(\App\Filament\Resources\VehicleManagement\Vehicles\VehicleResource::getUrl('create'));
                $this->halt();
            }
        }
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}
