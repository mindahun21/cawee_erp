<?php

namespace App\Filament\Resources\RecruitmentInterviews\Pages;

use App\Filament\Resources\RecruitmentInterviews\RecruitmentInterviewResource;
use Filament\Actions\DeleteAction;
use Filament\Actions\ForceDeleteAction;
use Filament\Actions\RestoreAction;
use Filament\Resources\Pages\EditRecord;

class EditRecruitmentInterview extends EditRecord
{
    protected static string $resource = RecruitmentInterviewResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
            ForceDeleteAction::make(),
            RestoreAction::make(),
        ];
    }
}
