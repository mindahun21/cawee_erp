<?php

namespace App\Filament\Resources\OnboardingProcesses\Pages;

use App\Filament\Resources\OnboardingProcesses\OnboardingProcessResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditOnboardingProcess extends EditRecord
{
    protected static string $resource = OnboardingProcessResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}
