<?php

namespace App\Filament\Resources\RecruitmentPositions\Pages;

use App\Filament\Resources\RecruitmentPositions\RecruitmentPositionResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListRecruitmentPositions extends ListRecords
{
    protected static string $resource = RecruitmentPositionResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
