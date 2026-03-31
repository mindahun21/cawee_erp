<?php

namespace App\Http\Controllers\FileSharing;

use App\Http\Controllers\Controller;
use App\Models\FileAccessLog;
use App\Models\FileShare;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;

class FileShareController extends Controller
{
    public function show(Request $request, string $token): StreamedResponse
    {
        $share = FileShare::query()
            ->with('file')
            ->where('share_token', $token)
            ->where('is_active', true)
            ->firstOrFail();

        abort_unless($share->share_type === 'public', 403, 'This share requires authenticated internal or client access.');
        abort_unless($share->shared_file_id !== null && $share->file, 404, 'This share does not point to a downloadable file.');
        abort_if($share->expires_at?->isPast(), 403, 'This share link has expired.');
        abort_unless(in_array($share->access_level, ['download', 'manage'], true), 403, 'This share does not allow file downloads.');

        if ($share->max_downloads !== null && $share->download_count >= $share->max_downloads) {
            abort(403, 'This share link has reached its download limit.');
        }

        if ($share->password) {
            $password = (string) $request->query('password', '');
            abort_unless($password !== '' && Hash::check($password, $share->password), 403, 'Invalid share password.');
        }

        abort_unless(Storage::disk($share->file->disk)->exists($share->file->path), 404, 'The shared file is not available.');

        $share->increment('download_count');

        FileAccessLog::create([
            'shared_file_id' => $share->shared_file_id,
            'file_share_id' => $share->id,
            'user_id' => auth()->id(),
            'action' => 'downloaded',
            'ip_address' => $request->ip(),
            'user_agent' => $request->userAgent(),
            'accessed_at' => now(),
        ]);

        return Storage::disk($share->file->disk)->download(
            $share->file->path,
            $share->file->original_name ?: $share->file->display_name
        );
    }
}
