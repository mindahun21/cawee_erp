@extends('supplier.layouts.portal')
@section('title', 'Dashboard')
@section('content')
<div class="sp-page">

    <div class="sp-page-header">
        <h1>Welcome, {{ $supplier->name }}</h1>
        <p>Vendor code: <strong>{{ $supplier->code ?? $supplier->vendor_code ?? 'Pending' }}</strong> &nbsp;|&nbsp; Status: <span class="badge badge-{{ $supplier->status==='Active'?'success':'warn' }}">{{ $supplier->status }}</span></p>
    </div>

    {{-- Stats --}}
    <div class="sp-grid-4" style="margin-bottom:2rem;">
        <div class="sp-stat">
            <div class="sp-stat-label">My Bids</div>
            <div class="sp-stat-value">{{ $myBids->count() }}</div>
            <div class="sp-stat-sub">Total submitted</div>
        </div>
        <div class="sp-stat" style="border-left-color:#0d7a4e;">
            <div class="sp-stat-label">Awarded</div>
            <div class="sp-stat-value" style="color:#0d7a4e;">{{ $myBids->where('status','Awarded')->count() }}</div>
            <div class="sp-stat-sub">Bid(s) awarded</div>
        </div>
        <div class="sp-stat" style="border-left-color:#b45309;">
            <div class="sp-stat-label">Under Review</div>
            <div class="sp-stat-value" style="color:#b45309;">{{ $myBids->whereIn('status',['Submitted','Under Review','Shortlisted'])->count() }}</div>
            <div class="sp-stat-sub">Being evaluated</div>
        </div>
        <div class="sp-stat" style="border-left-color:#003366;">
            <div class="sp-stat-label">Open Tenders</div>
            <div class="sp-stat-value" style="color:#003366;">{{ $openTenders->count() }}</div>
            <div class="sp-stat-sub">Available to bid</div>
        </div>
    </div>

    <div style="display:grid;grid-template-columns:3fr 2fr;gap:1.5rem;align-items:start;">

        {{-- Latest bids --}}
        <div class="sp-card">
            <div class="sp-card-header">
                <div>
                    <div class="sp-card-title">Recent Bid Submissions</div>
                    <div class="sp-card-sub">Your most recently submitted bids</div>
                </div>
                <a href="{{ route('supplier.my-bids') }}" class="sp-btn sp-btn-outline sp-btn-sm">All Bids</a>
            </div>
            @if($myBids->isEmpty())
                <p style="color:var(--muted);text-align:center;padding:1.5rem 0;font-size:.9rem;">No bids submitted yet. <a href="{{ route('supplier.tenders') }}" style="color:var(--cyan2);">Browse open tenders →</a></p>
            @else
            <div class="sp-table-wrap">
                <table class="sp-table">
                    <thead><tr>
                        <th>Tender</th><th>Amount</th><th>Submitted</th><th>Status</th>
                    </tr></thead>
                    <tbody>
                        @foreach($myBids as $bid)
                        <tr>
                            <td>
                                <div style="font-weight:600;font-size:.85rem;">{{ $bid->tender->tender_number ?? '—' }}</div>
                                <div style="font-size:.78rem;color:var(--muted);">{{ Str::limit($bid->tender->title ?? '', 40) }}</div>
                            </td>
                            <td style="font-weight:600;white-space:nowrap;">{{ $bid->currency }} {{ number_format($bid->bid_amount,0) }}</td>
                            <td style="font-size:.82rem;color:var(--muted);">{{ \Carbon\Carbon::parse($bid->submission_date)->format('d M Y') }}</td>
                            <td>
                                @php $color = match($bid->status) { 'Awarded'=>'success','Shortlisted'=>'cyan','Rejected'=>'danger','Under Review'=>'warn', default=>'gray' }; @endphp
                                <span class="badge badge-{{ $color }}">{{ $bid->status }}</span>
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            @endif
        </div>

        {{-- Open tenders --}}
        <div>
            <div class="sp-card">
                <div class="sp-card-header">
                    <div>
                        <div class="sp-card-title">Open Tenders</div>
                        <div class="sp-card-sub">Available for bid submission</div>
                    </div>
                    <a href="{{ route('supplier.tenders') }}" class="sp-btn sp-btn-outline sp-btn-sm">View All</a>
                </div>
                @foreach($openTenders as $t)
                <div style="padding:.875rem 0;border-bottom:1px solid var(--border);">
                    <div style="font-size:.78rem;color:var(--cyan2);font-weight:600;">{{ $t->tender_number }}</div>
                    <div style="font-weight:600;font-size:.9rem;margin:.2rem 0;">{{ Str::limit($t->title, 55) }}</div>
                    <div style="display:flex;justify-content:space-between;align-items:center;margin-top:.4rem;">
                        <span style="font-size:.78rem;color:var(--muted);">Deadline {{ \Carbon\Carbon::parse($t->submission_deadline)->format('d M') }}</span>
                        <a href="{{ route('supplier.tenders.show', $t) }}" class="sp-btn sp-btn-outline sp-btn-sm">Apply</a>
                    </div>
                </div>
                @endforeach
                @if($openTenders->isEmpty())
                    <p style="color:var(--muted);font-size:.875rem;text-align:center;padding:1rem 0;">No open tenders currently.</p>
                @endif
            </div>

            {{-- Profile completeness --}}
            @php
                $fields = ['phone','city','country','bank_name','bank_account','tin_number'];
                $filled = collect($fields)->filter(fn($f) => !empty($supplier->$f))->count();
                $pct = round($filled / count($fields) * 100);
            @endphp
            <div class="sp-card" style="margin-top:1rem;">
                <div class="sp-card-title" style="margin-bottom:.75rem;">Profile Completeness</div>
                <div style="background:#f0f4f8;border-radius:20px;height:10px;overflow:hidden;margin-bottom:.5rem;">
                    <div style="background:{{ $pct>=80?'#0d7a4e':($pct>=50?'#b45309':'#c0392b') }};height:100%;width:{{ $pct }}%;border-radius:20px;transition:width .5s;"></div>
                </div>
                <div style="font-size:.8rem;color:var(--muted);">{{ $pct }}% complete</div>
                @if($pct < 100)
                <a href="{{ route('supplier.profile') }}" class="sp-btn sp-btn-outline sp-btn-sm" style="margin-top:.75rem;display:inline-flex;">Complete Profile →</a>
                @endif
            </div>
        </div>
    </div>
</div>
@endsection
