<?php

namespace App\Http\Controllers\Recruitment;

use App\Http\Controllers\Controller;
use App\Models\Recruitment\RecruitmentCampaign;
use Illuminate\Http\Request;

class RecruitmentPortalController extends Controller
{
    /**
     * Public campaigns listing — job postings page.
     */
    public function campaigns(Request $request)
    {
        $query = RecruitmentCampaign::query()
            ->where('status', RecruitmentCampaign::STATUS_ACTIVE)
            ->where('is_public', true)
            ->with(['jobPosition.department', 'channel']);

        // 1. Text Search (Campaign title or Job Position title)
        if ($search = $request->input('search')) {
            $query->where(function($q) use ($search) {
                $q->where('title', 'like', "%{$search}%")
                  ->orWhereHas('jobPosition', function($q2) use ($search) {
                      $q2->where('title', 'like', "%{$search}%");
                  });
            });
        }

        // 2. Category / Department Filter
        if ($category = $request->input('category')) {
            $query->whereHas('jobPosition.department', function($q) use ($category) {
                $q->where('id', $category);
            });
        }

        // 3. Employment Type Filter
        if ($type = $request->input('type')) {
            $query->where('employment_type', $type);
        }

        $campaigns = $query->orderByDesc('created_at')->paginate(12)->withQueryString();

        // Get filter options
        $departments = \App\Models\Department::orderBy('name')->get();
        
        // Pluck unique employment types from active public campaigns
        $employmentTypes = RecruitmentCampaign::query()
            ->where('status', RecruitmentCampaign::STATUS_ACTIVE)
            ->where('is_public', true)
            ->select('employment_type')
            ->distinct()
            ->pluck('employment_type')
            ->filter();

        return view('recruitment.portal.campaigns', compact('campaigns', 'departments', 'employmentTypes'));
    }

    /**
     * Single campaign detail page.
     */
    public function show(RecruitmentCampaign $campaign)
    {
        if ($campaign->status !== RecruitmentCampaign::STATUS_ACTIVE || ! $campaign->is_public) {
            abort(404);
        }

        $campaign->load(['jobPosition', 'channel', 'skills']);

        return view('recruitment.portal.show', compact('campaign'));
    }

    public function myApplications()
    {
        $candidate = auth('candidate')->user();
        $applications = $candidate->applications()->with('campaign.jobPosition')->orderByDesc('created_at')->get();
        return view('recruitment.portal.my-applications', compact('candidate', 'applications'));
    }

    public function showApplication(\App\Models\Recruitment\RecruitmentApplication $application)
    {
        $candidate = auth('candidate')->user();

        if ($application->candidate_id !== $candidate->id) {
            abort(403);
        }

        $application->load([
            'campaign.jobPosition.department',
            'campaign.channel',
            'campaign.skills',
            'candidate.skills',
            'candidate.seniorities',
            'candidate.literacies',
            'candidate.references',
        ]);

        return view('recruitment.portal.show-application', compact('application', 'candidate'));
    }

    public function profile()
    {
        $candidate = auth('candidate')->user();
        return view('recruitment.portal.profile', compact('candidate'));
    }
}
