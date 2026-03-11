<?php

namespace App\Filament\Resources\RecruitmentChannels\Pages;

use App\Filament\Resources\RecruitmentChannels\RecruitmentChannelResource;
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
