<?php

namespace App\Filament\Resources\Recruitment\RecruitmentApplications\Pages;

use App\Filament\Resources\Recruitment\RecruitmentApplications\RecruitmentApplicationResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListRecruitmentApplications extends ListRecords
{
    protected static string $resource = RecruitmentApplicationResource::class;

    protected function getHeaderActions(): array
    {
        return [
            \Filament\Actions\Action::make('kanban_view')
                ->label('Switch to Kanban')
                ->icon('heroicon-o-view-columns')
                ->color('gray')
                ->url(RecruitmentApplicationResource::getUrl('kanban')),
            CreateAction::make(),
        ];
    }
}
