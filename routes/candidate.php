<?php

use App\Http\Controllers\Recruitment\CandidateApplicationController;
use App\Http\Controllers\Recruitment\CandidateAuthController;
use App\Http\Controllers\Recruitment\RecruitmentPortalController;
use Illuminate\Support\Facades\Route;

// Recruitment Portal Routing
Route::prefix('recruitment')->name('candidate.')->group(function () {

    // Main Portal (fixes the /recruitment/recruitment_portal 404 from the admin panel link)
    Route::get('/recruitment_portal', function () {
        return redirect()->route('candidate.campaigns');
    });

    // Public campaigns listing (no login required)
    Route::get('/', [RecruitmentPortalController::class, 'campaigns'])->name('home');
    Route::get('/campaigns', [RecruitmentPortalController::class, 'campaigns'])->name('campaigns');
    Route::get('/campaigns/{campaign}', [RecruitmentPortalController::class, 'show'])->name('campaigns.show');
    Route::get('/campaigns/{campaign}/apply', [CandidateApplicationController::class, 'create'])->name('campaigns.apply');
    Route::post('/campaigns/{campaign}/apply', [CandidateApplicationController::class, 'store'])->name('campaigns.apply.store');

    // Auth
    Route::middleware('guest:candidate')->group(function () {
        Route::get('/login', [CandidateAuthController::class, 'showLogin'])->name('login');
        Route::post('/login', [CandidateAuthController::class, 'login'])->name('login.submit');
        Route::get('/register', [CandidateAuthController::class, 'showRegister'])->name('register');
        Route::post('/register', [CandidateAuthController::class, 'register'])->name('register.submit');
    });

    Route::post('/logout', [CandidateAuthController::class, 'logout'])->name('logout');

    // Authenticated Portal
    Route::middleware('auth:candidate')->group(function () {
        Route::get('/my-applications', [RecruitmentPortalController::class, 'myApplications'])->name('my-applications');
        Route::get('/my-applications/{application}', [RecruitmentPortalController::class, 'showApplication'])->name('my-applications.show');
        Route::post('/my-applications/{application}/withdraw', [RecruitmentPortalController::class, 'withdrawApplication'])->name('my-applications.withdraw');
        Route::get('/profile', [RecruitmentPortalController::class, 'profile'])->name('profile');
    });
});
