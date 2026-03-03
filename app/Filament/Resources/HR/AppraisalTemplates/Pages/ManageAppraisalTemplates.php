<?php

namespace App\Filament\Resources\HR\AppraisalTemplates\Pages;

use App\Filament\Concerns\HasHrSettingsNavigation;
use App\Filament\Resources\HR\AppraisalTemplates\AppraisalTemplateResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ManageRecords;

class ManageAppraisalTemplates extends ManageRecords
{
    use HasHrSettingsNavigation;

    protected static string $resource = AppraisalTemplateResource::class;

    protected function getHeaderActions(): array
    {
        return [CreateAction::make()];
    }
}
