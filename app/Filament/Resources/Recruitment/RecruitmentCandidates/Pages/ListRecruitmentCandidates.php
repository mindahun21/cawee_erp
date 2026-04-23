<?php

namespace App\Filament\Resources\Recruitment\RecruitmentCandidates\Pages;

use App\Filament\Resources\Recruitment\RecruitmentCandidates\RecruitmentCandidateResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListRecruitmentCandidates extends ListRecords
{
    protected static string $resource = RecruitmentCandidateResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
