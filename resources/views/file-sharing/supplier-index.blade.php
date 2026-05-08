@extends('supplier.layouts.portal')

@section('title', 'Shared Documents')
@section('description', 'Documents shared with your company account through Cawee ERP.')

@section('content')
<div class="sp-page">
    <div class="sp-page-header">
        <div class="sp-breadcrumb">
            <a href="{{ route('supplier.dashboard') }}">Dashboard</a>
            <span>/</span>
            <span>Shared Documents</span>
        </div>
        <h1>Shared Documents</h1>
        <p>Review files shared with your company, track expiry windows, and open preview or download actions from one place.</p>
    </div>

    <div class="sp-grid-3" style="margin-bottom: 1.5rem;">
        <div class="sp-stat">
            <div class="sp-stat-label">Active Shares</div>
            <div class="sp-stat-value">{{ $shares->getCollection()->filter(fn ($share) => ! $share->isExpired())->count() }}</div>
            <div class="sp-stat-sub">Currently accessible documents</div>
        </div>
        <div class="sp-stat" style="border-left-color:#b45309;">
            <div class="sp-stat-label">View Only</div>
            <div class="sp-stat-value" style="color:#b45309;">{{ $shares->getCollection()->where('access_level', 'view')->count() }}</div>
            <div class="sp-stat-sub">Preview allowed, download blocked</div>
        </div>
        <div class="sp-stat" style="border-left-color:#362A72;">
            <div class="sp-stat-label">Downloads Allowed</div>
            <div class="sp-stat-value" style="color:#362A72;">{{ $shares->getCollection()->filter(fn ($share) => $share->allowsDownload())->count() }}</div>
            <div class="sp-stat-sub">Files you can download</div>
        </div>
    </div>

    <div class="sp-card">
        <div class="sp-card-header">
            <div>
                <div class="sp-card-title">Company Share Inbox</div>
                <div class="sp-card-sub">Only shares addressed to {{ auth('supplier')->user()?->email }}</div>
            </div>
        </div>

        @if($shares->isEmpty())
            <div class="sp-alert sp-alert-info">No files have been shared with your company yet.</div>
        @else
            <div class="sp-table-wrap">
                <table class="sp-table">
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
                        @foreach($shares as $share)
                            @php
                                $expired = $share->isExpired();
                                $statusClass = $expired ? 'badge-gray' : 'badge-success';
                                $statusText = $expired ? 'Expired' : 'Active';
                            @endphp
                            <tr>
                                <td>
                                    <div style="font-weight:700;">{{ $share->file?->display_name ?? 'Shared file' }}</div>
                                    <div style="font-size:.8rem;color:var(--muted);">{{ $share->file?->original_name ?? 'Original name unavailable' }}</div>
                                </td>
                                <td>
                                    <span class="badge badge-warn">{{ ucfirst($share->access_level) }}</span>
                                </td>
                                <td>{{ $share->expires_at?->format('M d, Y h:i A') ?? 'No expiry' }}</td>
                                <td><span class="badge {{ $statusClass }}">{{ $statusText }}</span></td>
                                <td>
                                    <div style="display:flex;gap:.5rem;flex-wrap:wrap;">
                                        <a class="sp-btn sp-btn-outline sp-btn-sm {{ $expired || ! $share->allowsPreview() ? 'disabled' : '' }}" href="{{ route('supplier.shares.preview', $share->share_token) }}">Preview</a>
                                        <a class="sp-btn sp-btn-primary sp-btn-sm {{ $expired || ! $share->allowsDownload() ? 'disabled' : '' }}" href="{{ route('supplier.shares.download', $share->share_token) }}">Download</a>
                                    </div>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            <div style="margin-top: 1rem;">
                {{ $shares->links() }}
            </div>
        @endif
    </div>
</div>
@endsection
