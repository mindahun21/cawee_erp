<?php

namespace App\Services\AI;

use App\Models\Recruitment\RecruitmentApplication;
use App\Models\Recruitment\RecruitmentCampaign;
use App\Models\Recruitment\RecruitmentCandidate;
use App\Models\Recruitment\RecruitmentInterviewSchedule;
use App\Models\Recruitment\RecruitmentOffer;
use App\Models\Recruitment\RecruitmentPlan;
use Illuminate\Support\Facades\DB;

class RecruitmentDataPreloader
{
    /**
     * Build a structured text snapshot of live recruitment data
     * for injection into the AI system prompt.
     */
    public function snapshot(): string
    {
        $lines = [];
        $lines[] = '=== LIVE RECRUITMENT DATA SNAPSHOT (as of ' . now()->toDateTimeString() . ') ===';
        $lines[] = '';

        // ── Plans ──────────────────────────────────────
        $plansByStatus = RecruitmentPlan::query()
            ->select('status', DB::raw('COUNT(*) as cnt'))
            ->groupBy('status')
            ->pluck('cnt', 'status')
            ->toArray();
        $lines[] = '## Plans';
        foreach ($plansByStatus as $status => $count) {
            $lines[] = "  {$status}: {$count}";
        }
        $lines[] = "  total: " . array_sum($plansByStatus);
        $lines[] = '';

        // ── Campaigns ──────────────────────────────────
        $campaignsByStatus = RecruitmentCampaign::query()
            ->select('status', DB::raw('COUNT(*) as cnt'))
            ->groupBy('status')
            ->pluck('cnt', 'status')
            ->toArray();
        $lines[] = '## Campaigns';
        foreach ($campaignsByStatus as $status => $count) {
            $lines[] = "  {$status}: {$count}";
        }
        $lines[] = "  total: " . array_sum($campaignsByStatus);
        $lines[] = '';

        // Active campaigns detail
        $activeCampaigns = RecruitmentCampaign::query()
            ->where('status', 'active')
            ->with('jobPosition')
            ->withCount('applications')
            ->orderByDesc('applications_count')
            ->limit(10)
            ->get();
        if ($activeCampaigns->isNotEmpty()) {
            $lines[] = '## Active Campaigns (top 10 by application count)';
            foreach ($activeCampaigns as $c) {
                $posName = $c->jobPosition?->name ?? 'N/A';
                $lines[] = "  - [{$c->campaign_code}] {$c->title} | Position: {$posName} | Vacancies: {$c->vacancies_needed} | Applications: {$c->applications_count} | Ends: " . ($c->end_date?->format('Y-m-d') ?? 'N/A');
            }
            $lines[] = '';
        }

        // ── Applications ───────────────────────────────
        $appsByStatus = RecruitmentApplication::query()
            ->select('status', DB::raw('COUNT(*) as cnt'))
            ->groupBy('status')
            ->pluck('cnt', 'status')
            ->toArray();
        $lines[] = '## Applications';
        foreach ($appsByStatus as $status => $count) {
            $lines[] = "  {$status}: {$count}";
        }
        $lines[] = "  total: " . array_sum($appsByStatus);
        $lines[] = '';

        // Application trend (last 8 weeks)
        $weeklyApps = RecruitmentApplication::query()
            ->where('applied_at', '>=', now()->subWeeks(8))
            ->select(
                DB::raw("DATE_FORMAT(applied_at, '%Y-%u') as week_key"),
                DB::raw('COUNT(*) as cnt')
            )
            ->groupBy('week_key')
            ->orderBy('week_key')
            ->pluck('cnt', 'week_key')
            ->toArray();
        if (!empty($weeklyApps)) {
            $lines[] = '## Application Trend (weekly, last 8 weeks)';
            foreach ($weeklyApps as $week => $count) {
                $lines[] = "  {$week}: {$count}";
            }
            $lines[] = '';
        }

        // ── Interviews ─────────────────────────────────
        $schedulesByStatus = RecruitmentInterviewSchedule::query()
            ->select('status', DB::raw('COUNT(*) as cnt'))
            ->groupBy('status')
            ->pluck('cnt', 'status')
            ->toArray();
        $lines[] = '## Interview Schedules';
        foreach ($schedulesByStatus as $status => $count) {
            $lines[] = "  {$status}: {$count}";
        }
        $lines[] = '';

        // ── Offers ─────────────────────────────────────
        $offersByStatus = RecruitmentOffer::query()
            ->select('status', DB::raw('COUNT(*) as cnt'))
            ->groupBy('status')
            ->pluck('cnt', 'status')
            ->toArray();
        $lines[] = '## Offers';
        foreach ($offersByStatus as $status => $count) {
            $lines[] = "  {$status}: {$count}";
        }
        $lines[] = '';

        // ── Candidates ─────────────────────────────────
        $totalCandidates = RecruitmentCandidate::count();
        $recentCandidates = RecruitmentCandidate::where('created_at', '>=', now()->subDays(30))->count();
        $lines[] = '## Candidates';
        $lines[] = "  total: {$totalCandidates}";
        $lines[] = "  registered_last_30_days: {$recentCandidates}";
        $lines[] = '';

        // ── Top Skills In Demand ───────────────────────
        $topSkills = DB::table('recruitment_campaign_skill')
            ->join('recruitment_skills', 'recruitment_campaign_skill.recruitment_skill_id', '=', 'recruitment_skills.id')
            ->join('recruitment_campaigns', 'recruitment_campaign_skill.campaign_id', '=', 'recruitment_campaigns.id')
            ->where('recruitment_campaigns.status', 'active')
            ->select('recruitment_skills.name', DB::raw('COUNT(*) as demand'))
            ->groupBy('recruitment_skills.name')
            ->orderByDesc('demand')
            ->limit(10)
            ->get();
        if ($topSkills->isNotEmpty()) {
            $lines[] = '## Top Skills in Demand (active campaigns)';
            foreach ($topSkills as $skill) {
                $lines[] = "  {$skill->name}: required in {$skill->demand} campaign(s)";
            }
            $lines[] = '';
        }

        // ── Pipeline Conversion ────────────────────────
        $totalApps = array_sum($appsByStatus);
        if ($totalApps > 0) {
            $lines[] = '## Pipeline Conversion Rates';
            $shortlisted = ($appsByStatus['shortlisted'] ?? 0) + ($appsByStatus['interview_scheduled'] ?? 0) + ($appsByStatus['interviewed'] ?? 0) + ($appsByStatus['selected'] ?? 0) + ($appsByStatus['offer_pending'] ?? 0) + ($appsByStatus['offer_accepted'] ?? 0) + ($appsByStatus['hired'] ?? 0);
            $interviewed = ($appsByStatus['interviewed'] ?? 0) + ($appsByStatus['selected'] ?? 0) + ($appsByStatus['offer_pending'] ?? 0) + ($appsByStatus['offer_accepted'] ?? 0) + ($appsByStatus['hired'] ?? 0);
            $hired = $appsByStatus['hired'] ?? 0;
            $lines[] = "  applied_to_shortlisted: " . round(($shortlisted / $totalApps) * 100, 1) . "%";
            $lines[] = "  applied_to_interviewed: " . round(($interviewed / $totalApps) * 100, 1) . "%";
            $lines[] = "  applied_to_hired: " . round(($hired / $totalApps) * 100, 1) . "%";
            $lines[] = '';
        }

        return implode("\n", $lines);
    }
}
