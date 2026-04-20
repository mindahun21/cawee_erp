<?php

namespace App\Services\AI\Data;

use App\Models\Recruitment\RecruitmentApplication;
use App\Models\Recruitment\RecruitmentCampaign;
use App\Models\Recruitment\RecruitmentCandidate;
use App\Models\Recruitment\RecruitmentInterviewSchedule;
use App\Models\Recruitment\RecruitmentOffer;
use App\Models\Recruitment\RecruitmentPlan;
use Illuminate\Support\Facades\DB;
use Illuminate\Database\Eloquent\Builder;

class RecruitmentDataPreloader extends ModuleDataPreloader
{
    public function getModuleName(): string
    {
        return 'recruitment';
    }

    public function getRequiredPermission(): string
    {
        return 'View:RecruitmentDashboard';
    }

    protected function getStatusValues(): array
    {
        return [
            'plans' => ['draft', 'submitted', 'approved', 'rejected', 'closed'],
            'campaigns' => ['draft', 'submitted', 'rejected', 'active', 'paused', 'full', 'closed'],
            'applications' => ['applied', 'under_review', 'shortlisted', 'interview_scheduled', 
                'interviewed', 'selected', 'waitlisted', 'offer_pending', 'offer_accepted', 
                'offer_declined', 'hired', 'rejected', 'withdrawn'],
        ];
    }

    /**
     * Build a structured text snapshot of live recruitment data
     * for injection into the AI system prompt.
     */
    public function snapshot(array $filters = []): string
    {
        $lines = [];
        $lines[] = '=== LIVE RECRUITMENT DATA SNAPSHOT (as of ' . now()->toDateTimeString() . ') ===';
        $lines[] = '';

        // ── Plans ──────────────────────────────────────
        $plansQuery = RecruitmentPlan::query();
        if (isset($filters['status'])) {
            $plansQuery->where('status', $filters['status']);
        }
        
        $plansByStatus = (clone $plansQuery)
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
        $campaignsQuery = RecruitmentCampaign::query();
        if (isset($filters['status'])) {
            $campaignsQuery->where('status', $filters['status']);
        }
        
        $campaignsByStatus = (clone $campaignsQuery)
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
        $activeCampaignsQuery = RecruitmentCampaign::query()
            ->where('status', 'active')
            ->with('jobPosition')
            ->withCount('applications');
            
        // Apply date filter if provided
        if (isset($filters['date_range'])) {
            $fromDate = $this->parseDateRange($filters['date_range']);
            $activeCampaignsQuery->where('created_at', '>=', $fromDate);
        }
        
        // Apply limit
        $limit = isset($filters['limit']) ? min(100, max(1, (int)$filters['limit'])) : 10;
        
        $activeCampaigns = $activeCampaignsQuery
            ->orderByDesc('applications_count')
            ->limit($limit)
            ->get();
            
        if ($activeCampaigns->isNotEmpty()) {
            $lines[] = "## Active Campaigns (top {$limit} by application count)";
            foreach ($activeCampaigns as $c) {
                $posName = $c->jobPosition?->name ?? 'N/A';
                $lines[] = "  - [{$c->campaign_code}] {$c->title} | Position: {$posName} | Vacancies: {$c->vacancies_needed} | Applications: {$c->applications_count} | Ends: " . ($c->end_date?->format('Y-m-d') ?? 'N/A');
            }
            $lines[] = '';
        }

        // ── Applications ───────────────────────────────
        $appsQuery = RecruitmentApplication::query();
        if (isset($filters['status'])) {
            $appsQuery->where('status', $filters['status']);
        }
        
        $appsByStatus = (clone $appsQuery)
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
            $lines[] = "  applied_to_shortlisted: " . $this->formatPercent(($shortlisted / $totalApps) * 100);
            $lines[] = "  applied_to_interviewed: " . $this->formatPercent(($interviewed / $totalApps) * 100);
            $lines[] = "  applied_to_hired: " . $this->formatPercent(($hired / $totalApps) * 100);
            $lines[] = '';
        }

        return implode("\n", $lines);
    }

    protected function applySearchFilter(Builder $query, string $search): void
    {
        $query->where(function ($q) use ($search) {
            $q->where('title', 'like', "%{$search}%")
              ->orWhere('campaign_code', 'like', "%{$search}%")
              ->orWhere('description', 'like', "%{$search}%");
        });
    }
}

