<?php

use App\Http\Controllers\Supplier\SupplierAuthController;
use App\Http\Controllers\Supplier\SupplierPortalController;
use App\Http\Controllers\FileSharing\RecipientShareController;
use App\Http\Middleware\SupplierAuthenticated;
use Illuminate\Support\Facades\Route;

// ── Public tender browsing (no login required) ─────────────────────────
Route::prefix('portal')->name('supplier.')->group(function () {

    Route::get('/', [SupplierPortalController::class, 'publicTenders'])->name('home');
    Route::get('/tenders', [SupplierPortalController::class, 'publicTenders'])->name('public.tenders');
    Route::get('/tenders/{tender}', [SupplierPortalController::class, 'publicTenderShow'])->name('public.tender');

    // ── Auth ───────────────────────────────────────────────────────
    Route::middleware('guest:supplier')->group(function () {
        Route::get('/login', [SupplierAuthController::class, 'showLogin'])->name('login');
        Route::post('/login', [SupplierAuthController::class, 'login'])->name('login.submit');
        Route::get('/register', [SupplierAuthController::class, 'showRegister'])->name('register');
        Route::post('/register', [SupplierAuthController::class, 'register'])->name('register.submit');
    });

    Route::post('/logout', [SupplierAuthController::class, 'logout'])->name('logout');

    // ── Authenticated portal ───────────────────────────────────────
    Route::middleware(SupplierAuthenticated::class)->group(function () {
        Route::get('/dashboard', [SupplierPortalController::class, 'dashboard'])->name('dashboard');

        Route::get('/open-tenders', [SupplierPortalController::class, 'tenders'])->name('tenders');
        Route::get('/open-tenders/{tender}', [SupplierPortalController::class, 'tenderShow'])->name('tenders.show');
        Route::get('/open-tenders/{tender}/bid', [SupplierPortalController::class, 'bidCreate'])->name('bids.create');
        Route::post('/open-tenders/{tender}/bid', [SupplierPortalController::class, 'bidStore'])->name('bids.store');

        Route::get('/my-bids', [SupplierPortalController::class, 'myBids'])->name('my-bids');
        Route::get('/my-shares', [RecipientShareController::class, 'supplierIndex'])->name('shares.index');
        Route::get('/my-shares/{token}/preview', [RecipientShareController::class, 'supplierPreview'])->name('shares.preview');
        Route::get('/my-shares/{token}/download', [RecipientShareController::class, 'supplierDownload'])->name('shares.download');

        Route::get('/profile', [SupplierPortalController::class, 'profile'])->name('profile');
        Route::patch('/profile', [SupplierPortalController::class, 'profileUpdate'])->name('profile.update');
        Route::patch('/profile/password', [SupplierPortalController::class, 'passwordUpdate'])->name('profile.password');
    });
});
