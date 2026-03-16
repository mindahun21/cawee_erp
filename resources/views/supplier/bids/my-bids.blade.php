@extends('supplier.layouts.portal')
@section('title', 'My Bid Submissions')
@section('content')
<div class="sp-page">

    <div class="sp-page-header">
        <h1>My Bid Submissions</h1>
        <p>Track the status of all your submitted bids.</p>
    </div>

    @if($bids->isEmpty())
    <div class="sp-card" style="text-align:center;padding:3rem;">
        <p style="font-size:1.1rem;color:var(--muted);">You have not submitted any bids yet.</p>
        <a href="{{ route('supplier.tenders') }}" class="sp-btn sp-btn-primary" style="margin-top:1rem;display:inline-flex;">Browse Open Tenders →</a>
    </div>
    @else
    <div class="sp-card">
        <div class="sp-table-wrap">
            <table class="sp-table">
                <thead><tr>
                    <th>Tender</th>
                    <th>Bid Amount</th>
                    <th>Submitted</th>
                    <th>Valid Until</th>
                    <th>Composite Score</th>
                    <th>Status</th>
                </tr></thead>
                <tbody>
                    @foreach($bids as $bid)
                    <tr>
                        <td>
                            <div style="font-weight:700;font-size:.875rem;">{{ $bid->tender->tender_number ?? '—' }}</div>
                            <div style="font-size:.8rem;color:var(--muted);max-width:240px;">{{ Str::limit($bid->tender->title ?? '', 55) }}</div>
                        </td>
                        <td style="font-weight:600;white-space:nowrap;">
                            {{ $bid->currency }} {{ number_format($bid->bid_amount, 0) }}
                        </td>
                        <td style="font-size:.85rem;">
                            {{ \Carbon\Carbon::parse($bid->submission_date)->format('d M Y') }}
                        </td>
                        <td style="font-size:.85rem;color:var(--muted);">
                            {{ \Carbon\Carbon::parse($bid->validity_date)->format('d M Y') }}
                        </td>
                        <td>
                            @if($bid->composite_score !== null)
                                @php $s = (float)$bid->composite_score; $sc = $s>=80?'success':($s>=60?'warn':'danger'); @endphp
                                <span class="badge badge-{{ $sc }}">{{ number_format($s,1) }}/100</span>
                            @else
                                <span style="color:var(--muted);font-size:.82rem;">Pending</span>
                            @endif
                        </td>
                        <td>
                            @php $color = match($bid->status) {
                                'Awarded'      => 'success',
                                'Shortlisted'  => 'cyan',
                                'Rejected'     => 'danger',
                                'Under Review','Pending Approval' => 'warn',
                                default        => 'gray'
                            }; @endphp
                            <span class="badge badge-{{ $color }}">{{ $bid->status }}</span>
                            @if($bid->status === 'Awarded')
                                <div style="font-size:.72rem;color:var(--success);margin-top:.25rem;font-weight:600;">🏆 Congratulations!</div>
                            @endif
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>

    {{-- Pagination --}}
    @if($bids->hasPages())
    <div class="sp-pagination">
        @if($bids->onFirstPage())<span class="disabled">← Prev</span>@else<a href="{{ $bids->previousPageUrl() }}">← Prev</a>@endif
        @foreach($bids->getUrlRange(1,$bids->lastPage()) as $p => $url)
            <a href="{{ $url }}" class="{{ $p==$bids->currentPage()?'active':'' }}">{{ $p }}</a>
        @endforeach
        @if($bids->hasMorePages())<a href="{{ $bids->nextPageUrl() }}">Next →</a>@else<span class="disabled">Next →</span>@endif
    </div>
    @endif
    @endif
</div>
@endsection
