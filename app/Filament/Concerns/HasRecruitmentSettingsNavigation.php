<?php

namespace App\Filament\Concerns;

use App\Filament\Resources\Recruitment\Settings\RecruitmentSkillCategories\RecruitmentSkillCategoryResource;
use App\Filament\Resources\Recruitment\Settings\RecruitmentSkills\RecruitmentSkillResource;
use App\Filament\Resources\Recruitment\Settings\RecruitmentApprovalWorkflows\RecruitmentApprovalWorkflowResource;
use App\Filament\Resources\Recruitment\Settings\RecruitmentEvaluationCriterias\RecruitmentEvaluationCriteriaResource;
use App\Filament\Resources\Recruitment\Settings\RecruitmentEvaluationFormTemplates\RecruitmentEvaluationFormTemplateResource;
use Filament\Navigation\NavigationItem;

trait HasRecruitmentSettingsNavigation
{
    public function getSubNavigation(): array
    {
        return [
            NavigationItem::make('Skills')
                ->icon('heroicon-o-shield-check')
                ->url(RecruitmentSkillResource::getUrl())
                ->isActiveWhen(fn () => request()->routeIs(RecruitmentSkillResource::getRouteBaseName() . '.*')),

            NavigationItem::make('Skill Categories')
                ->icon('heroicon-o-tag')
                ->url(RecruitmentSkillCategoryResource::getUrl())
                ->isActiveWhen(fn () => request()->routeIs(RecruitmentSkillCategoryResource::getRouteBaseName() . '.*')),

            NavigationItem::make('Approval Workflows')
                ->icon('heroicon-o-arrow-path-rounded-square')
                ->url(RecruitmentApprovalWorkflowResource::getUrl())
                ->isActiveWhen(fn () => request()->routeIs(RecruitmentApprovalWorkflowResource::getRouteBaseName() . '.*')),
                

            NavigationItem::make('Evaluation Criteria')
                ->icon('heroicon-o-clipboard-document-check')
                ->url(RecruitmentEvaluationCriteriaResource::getUrl())
                ->isActiveWhen(fn () => request()->routeIs(RecruitmentEvaluationCriteriaResource::getRouteBaseName() . '.*')),

            NavigationItem::make('Evaluation Forms')
                ->icon('heroicon-o-document-text')
                ->url(RecruitmentEvaluationFormTemplateResource::getUrl())
                ->isActiveWhen(fn () => request()->routeIs(RecruitmentEvaluationFormTemplateResource::getRouteBaseName() . '.*')),
        ];
    }
}
