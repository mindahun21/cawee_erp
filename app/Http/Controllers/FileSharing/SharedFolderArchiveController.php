<?php

namespace App\Http\Controllers\FileSharing;

use App\Http\Controllers\Controller;
use App\Models\SharedFile;
use App\Models\SharedFolder;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use ZipArchive;

class SharedFolderArchiveController extends Controller
{
    public function downloadAll(): BinaryFileResponse|Response
    {
        abort_unless(auth()->check(), Response::HTTP_FORBIDDEN);

        $zipPath = storage_path('app/tmp/all-shared-folders-'.now()->format('YmdHis').'.zip');
        $zip = $this->openArchive($zipPath);

        $added = 0;

        foreach (SharedFolder::query()->whereNull('parent_id')->orderBy('name')->get() as $folder) {
            $added += $this->addFolderContentsToZip($zip, $folder, '');
        }

        $zip->close();

        if ($added === 0) {
            @unlink($zipPath);
            abort(Response::HTTP_NOT_FOUND, 'There are no downloadable files in the folder library.');
        }

        return response()
            ->download($zipPath, 'all-shared-folders.zip')
            ->deleteFileAfterSend(true);
    }

    public function __invoke(SharedFolder $folder): BinaryFileResponse|Response
    {
        abort_unless(auth()->check(), Response::HTTP_FORBIDDEN);

        $zipPath = storage_path('app/tmp/admin-folder-'.$folder->id.'-'.now()->format('YmdHis').'.zip');
        $zip = $this->openArchive($zipPath);

        $added = $this->addFolderContentsToZip($zip, $folder, '');

        $zip->close();

        if ($added === 0) {
            @unlink($zipPath);
            abort(Response::HTTP_NOT_FOUND, 'This folder does not contain any downloadable files.');
        }

        $archiveName = str($folder->name)->slug('_')->value() ?: 'shared-folder';

        return response()
            ->download($zipPath, $archiveName.'.zip')
            ->deleteFileAfterSend(true);
    }

    protected function openArchive(string $zipPath): ZipArchive
    {
        $zipDir = dirname($zipPath);

        if (! is_dir($zipDir)) {
            mkdir($zipDir, 0777, true);
        }

        $zip = new ZipArchive();

        if ($zip->open($zipPath, ZipArchive::CREATE | ZipArchive::OVERWRITE) !== true) {
            abort(Response::HTTP_INTERNAL_SERVER_ERROR, 'Could not prepare folder archive.');
        }

        return $zip;
    }

    protected function addFolderContentsToZip(ZipArchive $zip, SharedFolder $folder, string $prefix): int
    {
        $count = 0;
        $basePath = trim($prefix.'/'.$folder->name, '/');

        /** @var SharedFile $file */
        foreach ($folder->files()->orderBy('display_name')->get() as $file) {
            if (! Storage::disk($file->disk)->exists($file->path)) {
                continue;
            }

            $sourcePath = Storage::disk($file->disk)->path($file->path);
            $entryName = trim($basePath.'/'.$this->fileName($file), '/');

            $zip->addFile($sourcePath, $entryName);
            $count++;
        }

        foreach ($folder->children()->orderBy('name')->get() as $child) {
            $count += $this->addFolderContentsToZip($zip, $child, $basePath);
        }

        return $count;
    }

    protected function fileName(SharedFile $file): string
    {
        if ($file->original_name) {
            return $file->original_name;
        }

        $extension = $file->extension ? '.'.$file->extension : '';

        return $file->display_name.$extension;
    }
}
