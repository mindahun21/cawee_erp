<?php

namespace App\Support\FileSharing;

use App\Models\SharedFile;
use App\Models\SharedFolder;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use ZipArchive;

class FolderArchiveImporter
{
    public function import(string $archivePath, ?int $parentFolderId, string $visibility, int $ownerId): array
    {
        $zip = new ZipArchive();

        if ($zip->open($archivePath) !== true) {
            throw new \RuntimeException('Could not open the uploaded archive.');
        }

        $createdFolders = 0;
        $createdFiles = 0;
        $folderCache = [];

        for ($i = 0; $i < $zip->numFiles; $i++) {
            $entry = $zip->statIndex($i);

            if (! is_array($entry) || empty($entry['name'])) {
                continue;
            }

            $entryName = str_replace('\\', '/', (string) $entry['name']);
            $isDirectory = str_ends_with($entryName, '/');
            $directoryPath = trim($isDirectory ? $entryName : dirname($entryName), './');

            $folderId = $this->resolveFolderPath(
                $directoryPath,
                $parentFolderId,
                $visibility,
                $ownerId,
                $folderCache,
                $createdFolders
            );

            if ($isDirectory) {
                continue;
            }

            $stream = $zip->getStream($entryName);
            if ($stream === false) {
                continue;
            }

            $originalName = basename($entryName);
            $extension = pathinfo($originalName, PATHINFO_EXTENSION);
            $storagePath = 'shared-files/imported/'.Str::uuid().($extension ? '.'.$extension : '');

            Storage::disk(config('filesystems.default'))->put($storagePath, stream_get_contents($stream));
            fclose($stream);

            SharedFile::query()->create([
                'folder_id' => $folderId,
                'display_name' => pathinfo($originalName, PATHINFO_FILENAME),
                'original_name' => $originalName,
                'disk' => config('filesystems.default'),
                'path' => $storagePath,
                'visibility' => $visibility,
                'is_locked' => false,
                'uploaded_by' => $ownerId,
            ]);

            $createdFiles++;
        }

        $zip->close();

        return [
            'folders' => $createdFolders,
            'files' => $createdFiles,
        ];
    }

    protected function resolveFolderPath(
        string $directoryPath,
        ?int $parentFolderId,
        string $visibility,
        int $ownerId,
        array &$folderCache,
        int &$createdFolders
    ): ?int {
        if ($directoryPath === '' || $directoryPath === '.') {
            return $parentFolderId;
        }

        $segments = array_values(array_filter(explode('/', $directoryPath)));
        $currentParentId = $parentFolderId;
        $pathKey = '';

        foreach ($segments as $segment) {
            $pathKey = ltrim($pathKey.'/'.$segment, '/');

            if (isset($folderCache[$pathKey])) {
                $currentParentId = $folderCache[$pathKey];
                continue;
            }

            $folder = SharedFolder::query()->firstOrCreate(
                [
                    'parent_id' => $currentParentId,
                    'name' => $segment,
                ],
                [
                    'visibility' => $visibility,
                    'owner_id' => $ownerId,
                ]
            );

            if ($folder->wasRecentlyCreated) {
                $createdFolders++;
            }

            $folderCache[$pathKey] = $folder->getKey();
            $currentParentId = $folder->getKey();
        }

        return $currentParentId;
    }
}
