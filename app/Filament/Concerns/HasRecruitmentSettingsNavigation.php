<?php

namespace App\Filament\Concerns;

use App\Filament\Resources\Recruitment\Settings\RecruitmentSkills\RecruitmentSkillResource;
use Filament\Navigation\NavigationItem;

trait HasRecruitmentSettingsNavigation
{
    public function getSubNavigation(): array
    {
        return [
            NavigationItem::make('Recruitment Skills')
                ->icon('heroicon-o-shield-check')
                ->url(RecruitmentSkillResource::getUrl())
                ->isActiveWhen(fn () => request()->routeIs(RecruitmentSkillResource::getRouteBaseName() . '.*')),
        ];
    }
}
