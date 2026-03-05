<?php

namespace App\Filament\Resources\OnboardingProcesses\Pages;

use App\Filament\Resources\OnboardingProcesses\OnboardingProcessResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListOnboardingProcesses extends ListRecords
{
    protected static string $resource = OnboardingProcessResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
