<?php

namespace App\Filament\Resources\Recruitment\RecruitmentChannels\Pages;

use App\Filament\Resources\Recruitment\RecruitmentChannels\RecruitmentChannelResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditRecruitmentChannel extends EditRecord
{
    protected static string $resource = RecruitmentChannelResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}
