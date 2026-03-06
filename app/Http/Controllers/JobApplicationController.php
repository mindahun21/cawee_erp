<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\RecruitmentCampaign;
use App\Models\JobApplication;

class JobApplicationController extends Controller
{
    // Step 1: Personal Info
    public function personalInfo($jobId)
    {
        $job = RecruitmentCampaign::findOrFail($jobId);
        return view('applications.personal-info', compact('job'));
    }

    public function submitStep1(Request $request, $jobId)
    {
        $request->validate([
            'full_name' => 'required|string|max:255',
            'email' => 'required|email',
            'phone_number' => 'required|string|max:20',
            'birthplace' => 'required|string|max:255',
            'current_address' => 'required|string|max:500',
            'gender' => 'required|in:male,female',
            'birthdate' => 'required|date',
        ]);

        $application = JobApplication::firstOrCreate(['job_id' => $jobId]);
        $data = $application->data ?? [];
        $data['personal_info'] = $request->all();
        $application->data = $data;
        $application->save();

        return redirect()->route('apply.step2', $jobId);
    }

    // Step 2: Education
    public function educationInfo($jobId)
    {
        $job = RecruitmentCampaign::findOrFail($jobId);
        return view('applications.education-info', compact('job'));
    }

    public function submitStep2(Request $request, $jobId)
    {
        $request->validate([
            'highest_education' => 'required|string',
            'graduation_year' => 'required|date',
            'education_program' => 'required|string',
            'cgpa' => 'nullable|numeric',
            'field_of_study' => 'required|string',
            'educational_organization' => 'required|string',
            'institution_type' => 'required|string',
            'exit_exam' => 'nullable|string',
        ]);

        $application = JobApplication::firstOrCreate(['job_id' => $jobId]);
        $data = $application->data ?? [];
        $data['education'] = $request->all();
        $application->data = $data;
        $application->save();

        return redirect()->route('apply.step3', $jobId);
    }

    // Step 3: Work Experience
    public function workExperience($jobId)
    {
        $job = RecruitmentCampaign::findOrFail($jobId);

        // Get existing experiences from the job_application JSON
        $application = JobApplication::firstOrCreate(['job_id' => $jobId]);
        $savedExperiences = $application->data['work_experience'] ?? [];

        return view('applications.work-experience', compact('job', 'savedExperiences'));
    }

    public function submitWorkExperience(Request $request, $jobId)
    {
        $request->validate([
            'job_title' => 'required|string|max:255',
            'company_name' => 'required|string|max:255',
            'years_of_experience' => 'required|numeric|min:0',
            'from_date' => 'required|date',
            'to_date' => 'required|date|after_or_equal:from_date',
            'banking_experience' => 'required|boolean',
        ]);

        $application = JobApplication::firstOrCreate(['job_id' => $jobId]);
        $data = $application->data ?? [];
        $data['work_experience'][] = $request->all(); // multiple experiences
        $application->data = $data;
        $application->save();

        return redirect()->route('apply.step4', $jobId)->with('success', 'Work experience saved!');
    }

    // Step 4: Certifications
    public function certifications($jobId)
    {
        $job = RecruitmentCampaign::findOrFail($jobId);
        $application = JobApplication::where('job_id', $jobId)->first();
        $savedCertifications = $application->data['certifications'] ?? [];
        return view('applications.certifications', compact('job', 'savedCertifications'));
    }

    public function submitCertifications(Request $request, $jobId)
    {
        $request->validate([
            'certificate_title' => 'required|string',
            'awarding_company' => 'required|string',
            'awarding_date' => 'required|date',
        ]);

        $application = JobApplication::firstOrCreate(['job_id' => $jobId]);
        $data = $application->data ?? [];
        $data['certifications'][] = $request->all(); // multiple certificates
        $application->data = $data;
        $application->save();

        return redirect()->route('apply.step5', $jobId)->with('success', 'Certification saved!');
    }

    // Step 5: Additional Info
    public function additionalInfo($jobId)
    {
        $job = RecruitmentCampaign::findOrFail($jobId);

        // Optionally, load any saved additional info to prefill
        $application = JobApplication::where('job_id', $jobId)->first();
        $savedAdditionalInfo = $application->data['additional_info'] ?? [];

        return view('applications.additional-info', compact('job', 'savedAdditionalInfo'));
    }

    public function submitAdditionalInfo(Request $request, $jobId)
    {
        $request->validate([
            'workplace' => 'required|string',
            'application_education' => 'required|string',
            'resume' => 'required|file|mimes:pdf',
            'cover_letter' => 'nullable|string',
            'agree_terms' => 'required|boolean',
            'consent_contact' => 'required|boolean',
        ]);

        $application = JobApplication::firstOrCreate(['job_id' => $jobId]);
        $data = $application->data ?? [];

        if ($request->hasFile('resume')) {
            $resumePath = $request->file('resume')->store('resumes');
            $requestData = $request->all();
            $requestData['resume'] = $resumePath;
        } else {
            $requestData = $request->all();
        }

        $data['additional_info'] = $requestData;
        $application->data = $data;
        $application->save();

        return redirect()->route('apply.verify', $jobId);
    }

    // Step 6: Verify & Submit
    public function verify($jobId)
    {
        $job = RecruitmentCampaign::findOrFail($jobId);

        $application = JobApplication::where('job_id', $jobId)->first();
        $savedData = $application ? $application->data : [];

        return view('applications.verify', compact('job', 'savedData'));
    }
    public function finalSubmit(Request $request, $jobId)
    {
        $application = JobApplication::where('job_id', $jobId)->firstOrFail();
        $application->submitted = true;
        $application->save();

        return redirect()->route('apply.step1', $jobId)
            ->with('success', 'Application submitted successfully!');
    }
}
