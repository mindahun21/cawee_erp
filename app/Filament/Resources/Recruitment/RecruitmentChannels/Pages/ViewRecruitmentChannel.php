<?php

namespace App\Filament\Resources\Recruitment\RecruitmentChannels\Pages;

use App\Filament\Resources\Recruitment\RecruitmentChannels\RecruitmentChannelResource;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Resources\Pages\ViewRecord;

class ViewRecruitmentChannel extends ViewRecord
{
    protected static string $resource = RecruitmentChannelResource::class;

    protected function getHeaderActions(): array
    {
        return [
            EditAction::make(),
            DeleteAction::make(),
        ];
    }
}
