<?php

namespace App\Http\Controllers\FileSharing;

use App\Http\Controllers\Controller;
use App\Models\FileAccessLog;
use App\Models\FileShare;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;

class FileShareController extends Controller
{
    public function show(Request $request, string $token): View|RedirectResponse
    {
        $share = $this->resolveShare($request, $token);
        if ($redirect = $this->redirectForMissingAuthentication($request, $share)) {
            return $redirect;
        }

        $this->ensureAccessible($request, $share);

        return view('file-sharing.show', [
            'share' => $share,
            'file' => $share->file,
            'isUnlocked' => $this->isUnlocked($request, $share),
            'canPreview' => $share->shared_file_id !== null && $share->allowsPreview(),
            'canDownload' => $share->shared_file_id !== null && $share->allowsDownload(),
        ]);
    }

    public function unlock(Request $request, string $token): RedirectResponse
    {
        $share = $this->resolveShare($request, $token);
        if ($redirect = $this->redirectForMissingAuthentication($request, $share)) {
            return $redirect;
        }

        $this->ensureAccessible($request, $share);

        if (! $share->password) {
            return redirect()->route('file-shares.show', $share->share_token);
        }

        $password = (string) $request->input('password', '');

        if ($password === '' || ! Hash::check($password, $share->password)) {
            $this->logAccess($request, $share, 'previewed', 'Denied access: Invalid share password.');

            return back()
                ->withErrors(['password' => 'Invalid share password.'])
                ->withInput();
        }

        $request->session()->put($share->passwordSessionKey(), true);

        return redirect()
            ->route('file-shares.show', $share->share_token)
            ->with('status', 'Share unlocked successfully.');
    }

    public function preview(Request $request, string $token): StreamedResponse|Response
    {
        $share = $this->resolveShare($request, $token);
        $this->ensureAccessible($request, $share);
        $this->ensureUnlocked($request, $share);

        if (! $share->allowsPreview()) {
            return $this->deny($request, $share, Response::HTTP_FORBIDDEN, 'This share does not allow file preview.');
        }

        if ($share->shared_file_id === null || ! $share->file) {
            return $this->deny($request, $share, Response::HTTP_NOT_FOUND, 'This share does not point to a previewable file.');
        }

        if (! $this->fileExists($share)) {
            return $this->deny($request, $share, Response::HTTP_NOT_FOUND, 'The shared file is not available.');
        }

        $this->logAccess($request, $share, 'previewed');

        return Storage::disk($share->file->disk)->response(
            $share->file->path,
            $share->file->original_name ?: $share->file->display_name
        );
    }

    public function download(Request $request, string $token): StreamedResponse|Response
    {
        $share = $this->resolveShare($request, $token);
        $this->ensureAccessible($request, $share);
        $this->ensureUnlocked($request, $share);

        if (! $share->allowsDownload()) {
            return $this->deny($request, $share, Response::HTTP_FORBIDDEN, 'This share does not allow file downloads.');
        }

        if ($share->shared_file_id === null || ! $share->file) {
            return $this->deny($request, $share, Response::HTTP_NOT_FOUND, 'This share does not point to a downloadable file.');
        }

        if ($share->max_downloads !== null && $share->download_count >= $share->max_downloads) {
            return $this->deny($request, $share, Response::HTTP_FORBIDDEN, 'This share link has reached its download limit.');
        }

        if (! $this->fileExists($share)) {
            return $this->deny($request, $share, Response::HTTP_NOT_FOUND, 'The shared file is not available.');
        }

        $share->increment('download_count');
        $this->logAccess($request, $share, 'downloaded');

        return Storage::disk($share->file->disk)->download(
            $share->file->path,
            $share->file->original_name ?: $share->file->display_name
        );
    }

    protected function resolveShare(Request $request, string $token): FileShare
    {
        return FileShare::query()
            ->with('file')
            ->where('share_token', $token)
            ->where('is_active', true)
            ->firstOrFail();
    }

    protected function ensureAccessible(Request $request, FileShare $share): void
    {
        if ($share->expires_at?->isPast()) {
            $this->deny($request, $share, Response::HTTP_FORBIDDEN, 'This share link has expired.');
        }

        if (! $this->shareCanBeAccessed($share)) {
            $this->deny($request, $share, Response::HTTP_FORBIDDEN, 'You are not allowed to access this shared file.');
        }
    }

    protected function redirectForMissingAuthentication(Request $request, FileShare $share): ?RedirectResponse
    {
        if ($share->share_type === 'staff' && ! Auth::guard('web')->check()) {
            $request->session()->put('url.intended', $request->fullUrl());

            return redirect()->route('filament.admin.auth.login');
        }

        if ($share->share_type === 'client' && ! Auth::guard('supplier')->check()) {
            $request->session()->put('url.intended', $request->fullUrl());

            return redirect()->route('supplier.login');
        }

        return null;
    }

    protected function shareCanBeAccessed(FileShare $share): bool
    {
        return match ($share->share_type) {
            'public' => true,
            'staff' => $share->canBeAccessedByStaff(Auth::guard('web')->user()),
            'client' => $share->canBeAccessedByClientEmail(Auth::guard('supplier')->user()?->email),
            default => false,
        };
    }

    protected function ensureUnlocked(Request $request, FileShare $share): void
    {
        if (! $share->password) {
            return;
        }

        if (! $this->isUnlocked($request, $share)) {
            $this->deny($request, $share, Response::HTTP_FORBIDDEN, 'This share is password protected.');
        }
    }

    protected function isUnlocked(Request $request, FileShare $share): bool
    {
        return ! $share->password || (bool) $request->session()->get($share->passwordSessionKey(), false);
    }

    protected function fileExists(FileShare $share): bool
    {
        return $share->file !== null
            && filled($share->file->path)
            && Storage::disk($share->file->disk)->exists($share->file->path);
    }

    protected function logAccess(Request $request, FileShare $share, string $action, ?string $notes = null): void
    {
        FileAccessLog::create([
            'shared_file_id' => $share->shared_file_id,
            'file_share_id' => $share->id,
            'user_id' => auth()->id(),
            'action' => $action,
            'ip_address' => $request->ip(),
            'user_agent' => $request->userAgent(),
            'notes' => $notes,
            'accessed_at' => now(),
        ]);
    }

    protected function deny(Request $request, FileShare $share, int $status, string $message): never
    {
        $this->logAccess($request, $share, 'previewed', 'Denied access: '.$message);

        abort($status, $message);
    }
}
