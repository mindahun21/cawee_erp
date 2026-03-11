<?php

namespace App\Filament\Resources\RecruitmentChannels\Pages;

use App\Filament\Resources\RecruitmentChannels\RecruitmentChannelResource;
use Filament\Actions\DeleteAction;
use Filament\Actions\ForceDeleteAction;
use Filament\Actions\RestoreAction;
use Filament\Resources\Pages\EditRecord;

class EditRecruitmentChannel extends EditRecord
{
    protected static string $resource = RecruitmentChannelResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
            ForceDeleteAction::make(),
            RestoreAction::make(),
        ];
    }
}
