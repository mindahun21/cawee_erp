<?php

use App\Http\Controllers\FileSharing\FileShareController;
use Illuminate\Support\Facades\Route;
// Recruitment routes removed.

Route::get('/', function () {
    return view('welcome');
});

// GET: open/download (no password), POST: submit password for protected shares
Route::match(['get', 'post'], '/shared-files/{token}', [FileShareController::class, 'show'])
    ->name('file-shares.show');
