<?php

use App\Http\Controllers\FileSharing\FileShareController;
use App\Http\Controllers\FileSharing\RecipientShareController;
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
Route::get('/shared-files/{token}/files/{file}/preview', [FileShareController::class, 'previewFolderFile'])
    ->name('file-shares.folder-files.preview');
Route::get('/shared-files/{token}/files/{file}/download', [FileShareController::class, 'downloadFolderFile'])
    ->name('file-shares.folder-files.download');

Route::middleware('auth')->prefix('my-shares')->name('recipient-shares.')->group(function () {
    Route::get('/', [RecipientShareController::class, 'staffIndex'])->name('index');
    Route::get('/{token}/preview', [RecipientShareController::class, 'staffPreview'])->name('preview');
    Route::get('/{token}/download', [RecipientShareController::class, 'staffDownload'])->name('download');
});
