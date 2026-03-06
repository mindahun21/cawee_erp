<?php

use Illuminate\Support\Facades\Route;
use App\Filament\Pages\JobDetails;
use App\Http\Controllers\JobApplicationController;
use App\Filament\Pages\RecruitmentPortal;
use App\Http\Controllers\PublicRecruitmentController;


Route::get('/jobs/{id}', [PublicRecruitmentController::class, 'showJob'])->name('jobs.show');
Route::get('/recruitment-portal', [PublicRecruitmentController::class, 'index'])
    ->name('recruitment.portal');
Route::get('/jobs', [PublicRecruitmentController::class, 'index']);
Route::get('/jobs/{id}', [PublicRecruitmentController::class, 'show']);

Route::get('/jobs', function () {
    return app(RecruitmentPortal::class)->render();
});

Route::get('/jobs/{id}', JobDetails::class);

// Step 1: Personal Info
Route::get('/apply/{jobId}/step1', [JobApplicationController::class, 'personalInfo'])->name('apply.step1');
Route::post('/apply/{jobId}/step1', [JobApplicationController::class, 'submitStep1'])->name('apply.submitStep1');

// Step 2: Education Info
Route::get('/apply/{jobId}/step2', [JobApplicationController::class, 'educationInfo'])->name('apply.step2');
Route::post('/apply/{jobId}/step2', [JobApplicationController::class, 'submitStep2'])->name('apply.submitStep2');

// Step 3: Work Experience
Route::get('/apply/{jobId}/step3', [JobApplicationController::class, 'workExperience'])->name('apply.step3');
Route::post('/apply/{jobId}/step3', [JobApplicationController::class, 'submitWorkExperience'])->name('apply.submitStep3');

// Step 4: Certifications
Route::get('/apply/{jobId}/step4', [JobApplicationController::class, 'certifications'])
    ->name('apply.step4');

Route::post('/apply/{jobId}/step4', [JobApplicationController::class, 'submitCertifications'])
    ->name('apply.submitCertifications');
// Step 5: Additional Information
Route::get('/apply/{jobId}/step5', [JobApplicationController::class, 'additionalInfo'])
    ->name('apply.step5');

Route::post('/apply/{jobId}/step5', [JobApplicationController::class, 'submitAdditionalInfo'])
    ->name('apply.submitAdditionalInfo');
// Step 6: Verify & Submit
Route::get('/apply/{jobId}/verify', [JobApplicationController::class, 'verify'])->name('apply.verify');
Route::post('/apply/{jobId}/submit', [JobApplicationController::class, 'finalSubmit'])->name('apply.finalSubmit');

Route::get('/verify/{jobId}', [JobApplicationController::class, 'verify'])->name('apply.verify');
Route::post('/submit/{jobId}', [JobApplicationController::class, 'finalSubmit'])->name('apply.finalSubmit');
Route::get('/', function () {
    return view('welcome');
});
