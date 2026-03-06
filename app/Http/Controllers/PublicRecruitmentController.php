<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\RecruitmentCampaign;

class PublicRecruitmentController extends Controller
{
    public function index(Request $request)
    {
        $search = $request->input('search', '');
        $jobCategory = $request->input('jobCategory', '');
        $jobType = $request->input('jobType', '');

        $campaigns = RecruitmentCampaign::query()
            ->when(
                $search,
                fn($q) =>
                $q->where('position', 'like', "%{$search}%")
                    ->orWhere('company', 'like', "%{$search}%")
                    ->orWhere('department', 'like', "%{$search}%")
            )
            ->when($jobCategory, fn($q) => $q->where('department', $jobCategory))
            ->when($jobType, fn($q) => $q->where('working_form', $jobType))
            ->latest()
            ->get();

        $jobCategories = RecruitmentCampaign::distinct()->pluck('department')->filter()->values();
        $jobTypes = RecruitmentCampaign::distinct()->pluck('working_form')->filter()->values();

        return view('public.recruitment-portal', compact(
            'campaigns',
            'jobCategories',
            'jobTypes',
            'search',
            'jobCategory',
            'jobType'
        ));
    }
    public function showJob($id)
    {
        $job = RecruitmentCampaign::findOrFail($id);
        return view('public.job-details', compact('job'));
    }
}
