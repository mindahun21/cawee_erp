<?php

namespace App\Filament\Resources\HR\Onboarding\Pages;

use App\Filament\Resources\HR\Onboarding\OnboardingChecklistResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ManageRecords;

class ManageOnboardingChecklist extends ManageRecords
{
    protected static string $resource = OnboardingChecklistResource::class;

    protected function getHeaderActions(): array
    {
        return [CreateAction::make()];
    }
}
