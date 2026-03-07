<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\RecruitmentCampaign;

class PublicRecruitmentController extends Controller
{
    /**
     * Display all recruitment campaigns with optional search and filters
     */
    public function index(Request $request)
    {
        // Get query parameters
        $search = $request->input('search', '');
        $jobCategory = $request->input('jobCategory', '');
        $jobType = $request->input('jobType', '');

        // Build the query
        $campaigns = RecruitmentCampaign::query()
            ->when($search, function ($q) use ($search) {
                // Group OR conditions for search
                $q->where(function ($query) use ($search) {
                    $query->where('position', 'like', "%{$search}%")
                        ->orWhere('company', 'like', "%{$search}%")
                        ->orWhere('department', 'like', "%{$search}%");
                });
            })
            ->when($jobCategory, fn($q) => $q->where('department', $jobCategory))
            ->when($jobType, fn($q) => $q->where('working_form', $jobType))
            ->latest()
            ->get();

        // Get distinct filters for dropdowns
        $jobCategories = RecruitmentCampaign::query()
            ->select('department')
            ->distinct()
            ->pluck('department')
            ->filter()
            ->values();

        $jobTypes = RecruitmentCampaign::query()
            ->select('working_form')
            ->distinct()
            ->pluck('working_form')
            ->filter()
            ->values();

        // Return view
        return view('public.recruitment-portal', compact(
            'campaigns',
            'jobCategories',
            'jobTypes',
            'search',
            'jobCategory',
            'jobType'
        ));
    }

    /**
     * Show a single job
     */
    public function showJob($id)
    {
        $job = RecruitmentCampaign::findOrFail($id);
        return view('public.job-details', compact('job'));
    }
}
