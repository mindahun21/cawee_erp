<?php

namespace App\Filament\Resources\HR\BranchUtilities\Pages;

use App\Filament\Resources\HR\BranchUtilities\BranchUtilityResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ManageRecords;

class ManageBranchUtilities extends ManageRecords
{
    protected static string $resource = BranchUtilityResource::class;

    protected function getHeaderActions(): array
    {
        return [CreateAction::make()];
    }
}

