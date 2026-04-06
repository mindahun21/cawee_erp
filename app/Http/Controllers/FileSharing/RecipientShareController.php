<?php

namespace App\Http\Controllers\FileSharing;

use App\Http\Controllers\Controller;
use App\Models\FileAccessLog;
use App\Models\FileShare;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;

class RecipientShareController extends Controller
{
    public function staffIndex(Request $request): View
    {
        $shares = FileShare::query()
            ->with('file')
            ->where('share_type', 'staff')
            ->where('shared_with_user_id', auth()->id())
            ->where('is_active', true)
            ->latest()
            ->paginate(12);

        return view('file-sharing.staff-index', ['shares' => $shares]);
    }

    public function supplierIndex(Request $request): View
    {
        $supplier = auth('supplier')->user();

        $shares = FileShare::query()
            ->with('file')
            ->where('share_type', 'client')
            ->whereRaw('LOWER(shared_with_email) = ?', [strtolower((string) $supplier->email)])
            ->where('is_active', true)
            ->latest()
            ->paginate(12);

        return view('file-sharing.supplier-index', ['shares' => $shares]);
    }

    public function staffPreview(Request $request, string $token): StreamedResponse
    {
        $share = $this->resolveStaffShare($token, (int) auth()->id());
        abort_unless($share->allowsPreview(), Response::HTTP_FORBIDDEN, 'This share does not allow preview.');

        return $this->streamPreview($request, $share);
    }

    public function staffDownload(Request $request, string $token): StreamedResponse
    {
        $share = $this->resolveStaffShare($token, (int) auth()->id());
        abort_unless($share->allowsDownload(), Response::HTTP_FORBIDDEN, 'This share does not allow downloads.');

        return $this->streamDownload($request, $share);
    }

    public function supplierPreview(Request $request, string $token): StreamedResponse
    {
        $email = (string) auth('supplier')->user()?->email;
        $share = $this->resolveSupplierShare($token, $email);
        abort_unless($share->allowsPreview(), Response::HTTP_FORBIDDEN, 'This share does not allow preview.');

        return $this->streamPreview($request, $share);
    }

    public function supplierDownload(Request $request, string $token): StreamedResponse
    {
        $email = (string) auth('supplier')->user()?->email;
        $share = $this->resolveSupplierShare($token, $email);
        abort_unless($share->allowsDownload(), Response::HTTP_FORBIDDEN, 'This share does not allow downloads.');

        return $this->streamDownload($request, $share);
    }

    protected function resolveStaffShare(string $token, int $userId): FileShare
    {
        return $this->resolveShare($token, fn (Builder $query) => $query
            ->where('share_type', 'staff')
            ->where('shared_with_user_id', $userId)
        );
    }

    protected function resolveSupplierShare(string $token, string $email): FileShare
    {
        return $this->resolveShare($token, fn (Builder $query) => $query
            ->where('share_type', 'client')
            ->whereRaw('LOWER(shared_with_email) = ?', [strtolower($email)])
        );
    }

    protected function resolveShare(string $token, \Closure $scope): FileShare
    {
        $query = FileShare::query()
            ->with('file')
            ->where('share_token', $token)
            ->where('is_active', true);

        $scope($query);

        $share = $query->firstOrFail();

        abort_unless($share->shared_file_id !== null && $share->file !== null, Response::HTTP_NOT_FOUND, 'This share does not point to a file.');
        abort_unless(! $share->isExpired(), Response::HTTP_FORBIDDEN, 'This share has expired.');
        abort_unless(Storage::disk($share->file->disk)->exists($share->file->path), Response::HTTP_NOT_FOUND, 'The shared file is not available.');

        return $share;
    }

    protected function streamPreview(Request $request, FileShare $share): StreamedResponse
    {
        $this->logAccess($request, $share, 'previewed');

        return Storage::disk($share->file->disk)->response(
            $share->file->path,
            $share->file->original_name ?: $share->file->display_name
        );
    }

    protected function streamDownload(Request $request, FileShare $share): StreamedResponse
    {
        if ($share->max_downloads !== null && $share->download_count >= $share->max_downloads) {
            abort(Response::HTTP_FORBIDDEN, 'This share has reached its download limit.');
        }

        $share->increment('download_count');
        $this->logAccess($request, $share, 'downloaded');

        return Storage::disk($share->file->disk)->download(
            $share->file->path,
            $share->file->original_name ?: $share->file->display_name
        );
    }

    protected function logAccess(Request $request, FileShare $share, string $action): void
    {
        FileAccessLog::query()->create([
            'shared_file_id' => $share->shared_file_id,
            'file_share_id' => $share->id,
            'user_id' => auth()->id(),
            'action' => $action,
            'ip_address' => $request->ip(),
            'user_agent' => $request->userAgent(),
            'accessed_at' => now(),
        ]);
    }
}
