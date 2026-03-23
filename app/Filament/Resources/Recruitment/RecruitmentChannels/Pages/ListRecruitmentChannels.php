<?php

namespace App\Filament\Resources\Recruitment\RecruitmentChannels\Pages;

use App\Filament\Resources\Recruitment\RecruitmentChannels\RecruitmentChannelResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListRecruitmentChannels extends ListRecords
{
    protected static string $resource = RecruitmentChannelResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
