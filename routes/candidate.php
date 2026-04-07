<?php

use App\Http\Controllers\Recruitment\CandidateApplicationController;
use App\Http\Controllers\Recruitment\CandidateAuthController;
use App\Http\Controllers\Recruitment\CandidateOfferController;
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

    // Signed one-time offer access link (no auth middleware — auto-logs in candidate)
    Route::get('/offer-access/{offer}', [CandidateOfferController::class, 'accessViaLink'])
        ->name('offer-access')
        ->middleware('signed');

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

        // My Offers
        Route::get('/my-offers', [CandidateOfferController::class, 'index'])->name('my-offers');
        Route::get('/my-offers/{offer}', [CandidateOfferController::class, 'show'])->name('my-offers.show');
        Route::post('/my-offers/{offer}/accept', [CandidateOfferController::class, 'accept'])->name('my-offers.accept');
        Route::post('/my-offers/{offer}/decline', [CandidateOfferController::class, 'decline'])->name('my-offers.decline');
        Route::get('/my-offers/{offer}/download', [CandidateOfferController::class, 'downloadLetter'])
            ->name('my-offers.download')
            ->middleware('signed');

        // First-time password setup (accessed after signed link login)
        Route::get('/set-password', [CandidateOfferController::class, 'showSetPasswordForm'])->name('set-password');
        Route::post('/set-password', [CandidateOfferController::class, 'savePassword'])->name('set-password.save');
    });
});

