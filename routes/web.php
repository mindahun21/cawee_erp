<?php

use App\Http\Controllers\FileSharing\FileShareController;
use Illuminate\Support\Facades\Route;
// Recruitment routes removed.

Route::get('/', function () {
    return view('welcome');
});

Route::get('/shared-files/{token}', [FileShareController::class, 'show'])
    ->name('file-shares.show');
