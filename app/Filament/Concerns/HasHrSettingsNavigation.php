<?php

namespace App\Filament\Concerns;

use App\Filament\Resources\HR\AppraisalTemplates\AppraisalTemplateResource;
use App\Filament\Resources\HR\Locations\LocationResource;
use App\Filament\Resources\HR\PerDiemRates\PerDiemRateResource;
use App\Filament\Resources\HR\Projects\ProjectResource;
use App\Filament\Resources\HR\SalaryGrades\SalaryGradeResource;
use App\Filament\Resources\HR\Settings\ContractTypeResource;
use App\Filament\Resources\HR\Settings\DepartmentResource;
use App\Filament\Resources\HR\Settings\EducationLevelResource;
use App\Filament\Resources\HR\Settings\FieldOfStudyResource;
use App\Filament\Resources\HR\Settings\HrSettingOptionResource;
use App\Filament\Resources\HR\Settings\JobPositionResource;
use App\Filament\Resources\HR\Settings\LandlordResource;
use App\Filament\Resources\HR\Settings\LayoffChecklistResource;
use App\Filament\Resources\HR\Settings\TrainingTypeResource;
use App\Filament\Resources\HR\Settings\HolidayResource;
use App\Filament\Resources\HR\Settings\LeaveTypeResource;
use App\Filament\Resources\HR\Settings\GradeResource;
use Filament\Navigation\NavigationItem;

trait HasHrSettingsNavigation
{
    public function getSubNavigation(): array
    {
        return [
            NavigationItem::make('Departments')
                ->icon('heroicon-o-building-office-2')
                ->url(DepartmentResource::getUrl())
                ->isActiveWhen(fn () => request()->routeIs(DepartmentResource::getRouteBaseName() . '.*')),

            NavigationItem::make('Job Positions')
                ->icon('heroicon-o-briefcase')
                ->url(JobPositionResource::getUrl())
                ->isActiveWhen(fn () => request()->routeIs(JobPositionResource::getRouteBaseName() . '.*')),

            NavigationItem::make('Grades')
                ->icon('heroicon-o-clipboard-document-list')
                ->url(GradeResource::getUrl())
                ->isActiveWhen(fn () => request()->routeIs(GradeResource::getRouteBaseName() . '.*')),

            NavigationItem::make('Contract Types')
                ->icon('heroicon-o-document-text')
                ->url(ContractTypeResource::getUrl())
                ->isActiveWhen(fn () => request()->routeIs(ContractTypeResource::getRouteBaseName() . '.*')),

            NavigationItem::make('Education Levels')
                ->icon('heroicon-o-academic-cap')
                ->url(EducationLevelResource::getUrl())
                ->isActiveWhen(fn () => request()->routeIs(EducationLevelResource::getRouteBaseName() . '.*')),

            NavigationItem::make('Fields of Study')
                ->icon('heroicon-o-book-open')
                ->url(FieldOfStudyResource::getUrl())
                ->isActiveWhen(fn () => request()->routeIs(FieldOfStudyResource::getRouteBaseName() . '.*')),

            NavigationItem::make('Training Types')
                ->icon('heroicon-o-presentation-chart-line')
                ->url(TrainingTypeResource::getUrl())
                ->isActiveWhen(fn () => request()->routeIs(TrainingTypeResource::getRouteBaseName() . '.*')),

            NavigationItem::make('Layoff Checklist')
                ->icon('heroicon-o-clipboard-document-list')
                ->url(LayoffChecklistResource::getUrl())
                ->isActiveWhen(fn () => request()->routeIs(LayoffChecklistResource::getRouteBaseName() . '.*')),

            NavigationItem::make('Locations')
                ->icon('heroicon-o-map-pin')
                ->url(LocationResource::getUrl())
                ->isActiveWhen(fn () => request()->routeIs(LocationResource::getRouteBaseName() . '.*')),

            NavigationItem::make('Projects')
                ->icon('heroicon-o-folder-open')
                ->url(ProjectResource::getUrl())
                ->isActiveWhen(fn () => request()->routeIs(ProjectResource::getRouteBaseName() . '.*')),

            NavigationItem::make('Salary Grades')
                ->icon('heroicon-o-currency-dollar')
                ->url(SalaryGradeResource::getUrl())
                ->isActiveWhen(fn () => request()->routeIs(SalaryGradeResource::getRouteBaseName() . '.*')),

            NavigationItem::make('Per Diem Rates')
                ->icon('heroicon-o-banknotes')
                ->url(PerDiemRateResource::getUrl())
                ->isActiveWhen(fn () => request()->routeIs(PerDiemRateResource::getRouteBaseName() . '.*')),

            NavigationItem::make('Appraisal Templates')
                ->icon('heroicon-o-star')
                ->url(AppraisalTemplateResource::getUrl())
                ->isActiveWhen(fn () => request()->routeIs(AppraisalTemplateResource::getRouteBaseName() . '.*')),

            NavigationItem::make('Car & Rent Dropdowns')
                ->icon('heroicon-o-rectangle-stack')
                ->url(HrSettingOptionResource::getUrl())
                ->isActiveWhen(fn () => request()->routeIs(HrSettingOptionResource::getRouteBaseName() . '.*')),

            NavigationItem::make('Landlords')
                ->icon('heroicon-o-home-modern')
                ->url(LandlordResource::getUrl())
                ->isActiveWhen(fn () => request()->routeIs(LandlordResource::getRouteBaseName() . '.*')),

            NavigationItem::make('Holidays')
                ->icon('heroicon-o-calendar-days')
                ->url(HolidayResource::getUrl())
                ->isActiveWhen(fn () => request()->routeIs(HolidayResource::getRouteBaseName() . '.*')),

            NavigationItem::make('Leave Types')
                ->icon('heroicon-o-adjustments-horizontal')
                ->url(LeaveTypeResource::getUrl())
                ->isActiveWhen(fn () => request()->routeIs(LeaveTypeResource::getRouteBaseName() . '.*')),
        ];
    }
}
