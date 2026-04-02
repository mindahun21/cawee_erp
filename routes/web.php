<?php

use App\Http\Controllers\FileSharing\FileShareController;
use Illuminate\Support\Facades\Route;
// Recruitment routes removed.

Route::get('/', function () {
    return view('welcome');
});

Route::get('/shared-files/{token}', [FileShareController::class, 'show'])
    ->name('file-shares.show');
Route::post('/shared-files/{token}/unlock', [FileShareController::class, 'unlock'])
    ->name('file-shares.unlock');
Route::get('/shared-files/{token}/preview', [FileShareController::class, 'preview'])
    ->name('file-shares.preview');
Route::get('/shared-files/{token}/download', [FileShareController::class, 'download'])
    ->name('file-shares.download');
