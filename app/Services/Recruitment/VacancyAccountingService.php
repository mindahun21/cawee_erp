<?php

namespace App\Services\Recruitment;

use App\Models\JobPosition;
use App\Models\Recruitment\RecruitmentCampaign;
use App\Models\Recruitment\RecruitmentPlan;

class VacancyAccountingService
{
    /**
     * Calculate available vacancies for a position, accounting for
     * filled employees and active plans/campaigns.
     * Optionally exclude a specific plan/campaign when editing.
     */
    public static function getAvailableVacancies(
        JobPosition $position,
        ?RecruitmentPlan $excludePlan = null,
        ?RecruitmentCampaign $excludeCampaign = null
    ): int {
        $employeeCount = $position->employees()->count();
        $plannedVacancies = static::getConsumedVacancies($position, $excludePlan, $excludeCampaign);

        return max(0, $position->vacancy_count - $employeeCount - $plannedVacancies);
    }

    /**
     * Get total vacancies consumed by active (Submitted/Approved) plans
     * and active standalone campaigns (campaigns without a plan)
     * for a specific position.
     */
    public static function getConsumedVacancies(
        JobPosition $position,
        ?RecruitmentPlan $excludePlan = null,
        ?RecruitmentCampaign $excludeCampaign = null
    ): int {
        // 1. Vacancies consumed by Active Plans
        $planQuery = RecruitmentPlan::where('job_position_id', $position->id)
            ->whereIn('status', [
                RecruitmentPlan::STATUS_SUBMITTED,
                RecruitmentPlan::STATUS_APPROVED,
            ]);

        if ($excludePlan?->exists) {
            $planQuery->where('id', '!=', $excludePlan->id);
        }
        $planVacancies = (int) $planQuery->sum('vacancies_needed');

        // 2. Vacancies consumed by Active STANDALONE Campaigns
        $campaignQuery = RecruitmentCampaign::where('job_position_id', $position->id)
            ->whereNull('recruitment_plan_id')
            ->whereIn('status', [
                RecruitmentCampaign::STATUS_SUBMITTED,
                RecruitmentCampaign::STATUS_ACTIVE,
            ]);

        if ($excludeCampaign?->exists) {
            $campaignQuery->where('id', '!=', $excludeCampaign->id);
        }
        $campaignVacancies = (int) $campaignQuery->sum('vacancies_needed');

        return $planVacancies + $campaignVacancies;
    }
}
