<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>My Shared Files</title>
    <style>
        body { font-family: "Segoe UI", Tahoma, sans-serif; margin: 0; background: #f6f8fb; color: #1f2937; }
        .wrap { max-width: 980px; margin: 2rem auto; padding: 0 1rem; }
        .card { background: #fff; border: 1px solid #e5e7eb; border-radius: 14px; overflow: hidden; }
        .head { padding: 1.25rem 1.5rem; border-bottom: 1px solid #e5e7eb; }
        .back { display: inline-flex; align-items: center; gap: .4rem; margin-bottom: .85rem; color: #475569; text-decoration: none; font-weight: 600; }
        .back:hover { color: #0f172a; }
        .title { margin: 0; font-size: 1.35rem; }
        .sub { margin: .4rem 0 0; color: #6b7280; font-size: .95rem; }
        table { width: 100%; border-collapse: collapse; }
        th, td { padding: .9rem 1rem; border-bottom: 1px solid #f1f5f9; text-align: left; vertical-align: top; }
        th { background: #f8fafc; font-size: .82rem; text-transform: uppercase; color: #64748b; letter-spacing: .03em; }
        .muted { color: #6b7280; }
        .badge { display: inline-block; padding: .2rem .55rem; border-radius: 999px; font-size: .78rem; font-weight: 600; }
        .ok { background: #ecfdf3; color: #166534; }
        .warn { background: #fff7ed; color: #9a3412; }
        .dead { background: #f3f4f6; color: #6b7280; }
        .btn { display: inline-block; text-decoration: none; border-radius: 8px; padding: .36rem .7rem; font-size: .85rem; font-weight: 600; margin-right: .4rem; }
        .btn-preview { background: #e2e8f0; color: #1f2937; }
        .btn-download { background: #0f766e; color: #fff; }
        .disabled { opacity: .45; pointer-events: none; }
        .pager { padding: 1rem; }
    </style>
</head>
<body>
<div class="wrap">
    <a class="back" href="{{ \App\Filament\Resources\FileSharing\FileShareResource::getUrl() }}">&#8592; Back to Shares</a>
    <div class="card">
        <div class="head">
            <h1 class="title">My Shared Files</h1>
            <p class="sub">Files that were shared directly with your employee account.</p>
        </div>

        <table>
            <thead>
            <tr>
                <th>File</th>
                <th>Access</th>
                <th>Expires</th>
                <th>Status</th>
                <th>Actions</th>
            </tr>
            </thead>
            <tbody>
            @forelse($shares as $share)
                @php
                    $expired = $share->isExpired();
                    $statusClass = $expired ? 'dead' : 'ok';
                    $statusText = $expired ? 'Expired' : 'Active';
                @endphp
                <tr>
                    <td>
                        <strong>{{ $share->file?->display_name ?? 'Shared file' }}</strong><br>
                        <span class="muted">{{ $share->file?->original_name ?? '-' }}</span>
                    </td>
                    <td><span class="badge warn">{{ ucfirst($share->access_level) }}</span></td>
                    <td>{{ $share->expires_at?->format('M d, Y h:i A') ?? 'No expiry' }}</td>
                    <td><span class="badge {{ $statusClass }}">{{ $statusText }}</span></td>
                    <td>
                        <a class="btn btn-preview {{ $expired || ! $share->allowsPreview() ? 'disabled' : '' }}" href="{{ route('recipient-shares.preview', $share->share_token) }}">Preview</a>
                        <a class="btn btn-download {{ $expired || ! $share->allowsDownload() ? 'disabled' : '' }}" href="{{ route('recipient-shares.download', $share->share_token) }}">Download</a>
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="5" class="muted">No files have been shared with your account yet.</td>
                </tr>
            @endforelse
            </tbody>
        </table>

        <div class="pager">
            {{ $shares->links() }}
        </div>
    </div>
</div>
</body>
</html>
