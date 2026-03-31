<?php

namespace App\Filament\Resources\Recruitment\RecruitmentInterviewSchedules\Pages;

use App\Filament\Resources\Recruitment\RecruitmentInterviewSchedules\RecruitmentInterviewScheduleResource;
use App\Models\Recruitment\RecruitmentInterviewSchedule;
use Filament\Actions\DeleteAction;
use Filament\Actions\ViewAction;
use Filament\Resources\Pages\EditRecord;

class EditRecruitmentInterviewSchedule extends EditRecord
{
    protected static string $resource = RecruitmentInterviewScheduleResource::class;

    protected function getHeaderActions(): array
    {
        return [
            ViewAction::make(),
            DeleteAction::make()
                ->visible(fn () => $this->record->status === RecruitmentInterviewSchedule::STATUS_DRAFT),
        ];
    }
}
