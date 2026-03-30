<?php

namespace App\Filament\Resources\Recruitment\RecruitmentInterviewSchedules\Pages;

use App\Filament\Resources\Recruitment\RecruitmentInterviewSchedules\RecruitmentInterviewScheduleResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListRecruitmentInterviewSchedules extends ListRecords
{
    protected static string $resource = RecruitmentInterviewScheduleResource::class;

    protected function getHeaderActions(): array
    {
        return [
            \Filament\Actions\Action::make('calendar_view')
                ->label('Calendar View')
                ->icon('heroicon-o-calendar')
                ->color('gray')
                ->url(RecruitmentInterviewScheduleResource::getUrl('calendar')),
            CreateAction::make(),
        ];
    }
}
