<?php

namespace App\Filament\Resources\Recruitment\RecruitmentCandidates\Pages;

use App\Filament\Resources\Recruitment\RecruitmentCandidates\RecruitmentCandidateResource;
use Filament\Actions\CreateAction;
use Filament\Actions\ImportAction;
use App\Filament\Imports\RecruitmentCandidateImporter;
use Filament\Resources\Pages\ListRecords;

class ListRecruitmentCandidates extends ListRecords
{
    protected static string $resource = RecruitmentCandidateResource::class;

    protected function getHeaderActions(): array
    {
        return [
            ImportAction::make()
                ->importer(RecruitmentCandidateImporter::class),
            CreateAction::make(),
        ];
    }
}
