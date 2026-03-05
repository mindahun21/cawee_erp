<?php

namespace App\Filament\Resources\RecruitmentCandidates\Pages;

use App\Filament\Resources\RecruitmentCandidates\RecruitmentCandidateResource;
use Filament\Actions\DeleteAction;
use Filament\Actions\ForceDeleteAction;
use Filament\Actions\RestoreAction;
use Filament\Resources\Pages\EditRecord;

class EditRecruitmentCandidate extends EditRecord
{
    protected static string $resource = RecruitmentCandidateResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
            ForceDeleteAction::make(),
            RestoreAction::make(),
        ];
    }
}
