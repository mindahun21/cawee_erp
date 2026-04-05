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
            ->where(function ($q) {
                $q->whereNull('end_date')
                    ->orWhere('end_date', '>=', now()->startOfDay());
            })
            ->with(['jobPosition.department', 'channel']);

        if ($search = $request->input('search')) {
            $query->where(function ($q) use ($search) {
                $q->where('title', 'like', "%{$search}%")
                    ->orWhereHas('jobPosition', function ($q2) use ($search) {
                        $q2->where('title', 'like', "%{$search}%");
                    });
            });
        }

        if ($category = $request->input('category')) {
            $query->whereHas('jobPosition.department', function ($q) use ($category) {
                $q->where('id', $category);
            });
        }

        if ($type = $request->input('type')) {
            $query->where('employment_type', $type);
        }

        $campaigns = $query->orderByDesc('created_at')->paginate(12)->withQueryString();

        // Get filter options
        $departments = \App\Models\Department::orderBy('name')->get();

        // Pluck unique employment types from active public campaigns (also respecting deadline)
        $employmentTypes = RecruitmentCampaign::query()
            ->where('status', RecruitmentCampaign::STATUS_ACTIVE)
            ->where('is_public', true)
            ->where(function ($q) {
                $q->whereNull('end_date')
                    ->orWhere('end_date', '>=', now()->startOfDay());
            })
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
        $allowedStatuses = [
            RecruitmentCampaign::STATUS_ACTIVE,
            RecruitmentCampaign::STATUS_FULL,
            RecruitmentCampaign::STATUS_PAUSED,
            RecruitmentCampaign::STATUS_CLOSED,
        ];

        // We allow viewing expired active campaigns (so they can see the 'Deadline Passed' message)
        if (! in_array($campaign->status, $allowedStatuses) || ! $campaign->is_public) {
            abort(404);
        }

        $campaign->load(['jobPosition', 'channel', 'skills']);

        return view('recruitment.portal.show', compact('campaign'));
    }

    public function myApplications()
    {
        $candidate = auth('candidate')->user();
        $applications = $candidate->applications()
            ->with(['campaign.jobPosition', 'offer'])
            ->orderByDesc('created_at')
            ->get();

        return view('recruitment.portal.my-applications', compact('candidate', 'applications'));
    }

    public function withdrawApplication(\App\Models\Recruitment\RecruitmentApplication $application)
    {
        $candidate = auth('candidate')->user();

        if ($application->candidate_id !== $candidate->id) {
            abort(403);
        }

        $allowedWithdrawStatuses = [
            \App\Models\Recruitment\RecruitmentApplication::STATUS_APPLIED,
            \App\Models\Recruitment\RecruitmentApplication::STATUS_UNDER_REVIEW,
            \App\Models\Recruitment\RecruitmentApplication::STATUS_SHORTLISTED,
            \App\Models\Recruitment\RecruitmentApplication::STATUS_INTERVIEW_SCHEDULED,
        ];

        if (! in_array($application->status, $allowedWithdrawStatuses)) {
            return back()->with('error', 'This application cannot be withdrawn at its current stage.');
        }

        $application->update(['status' => \App\Models\Recruitment\RecruitmentApplication::STATUS_WITHDRAWN]);

        return redirect()->route('candidate.my-applications')
            ->with('success', 'Your application has been withdrawn.');
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
            'offer',
        ]);

        return view('recruitment.portal.show-application', compact('application', 'candidate'));
    }

    public function profile()
    {
        $candidate = auth('candidate')->user();

        return view('recruitment.portal.profile', compact('candidate'));
    }
}
