@extends('supplier.layouts.portal')
@section('title', 'Open Tenders')
@section('content')
<div class="sp-page">

    <div class="sp-page-header">
        <h1>Open Tenders — Apply Now</h1>
        <p>Browse active procurement opportunities and submit your bids.</p>
    </div>

    <form method="GET" style="display:flex;gap:.75rem;flex-wrap:wrap;margin-bottom:1.5rem;align-items:center;">
        <input class="sp-input" name="search" value="{{ request('search') }}" placeholder="Search tender title or number…" style="max-width:300px;">
        <select name="method" class="sp-select" style="max-width:180px;">
            <option value="">All Methods</option>
            @foreach(['Open Tender','Restricted Tender','RFQ','Direct Purchase'] as $m)
                <option value="{{ $m }}" {{ request('method')==$m?'selected':'' }}>{{ $m }}</option>
            @endforeach
        </select>
        <button class="sp-btn sp-btn-primary" type="submit">Filter</button>
        @if(request()->hasAny(['search','method']))
            <a href="{{ route('supplier.tenders') }}" class="sp-btn sp-btn-outline">Clear</a>
        @endif
    </form>

    @if($tenders->isEmpty())
    <div class="sp-card" style="text-align:center;padding:3rem;">
        <p style="font-size:1.1rem;color:var(--muted);">No open tenders match your search.</p>
    </div>
    @else
    <div class="sp-grid-3">
        @foreach($tenders as $t)
        <div class="tender-card">
            <div class="tender-card-top">
                <div class="tender-card-num">{{ $t->tender_number }}</div>
                <div class="tender-card-title">{{ $t->title }}</div>
            </div>
            <div class="tender-card-body">
                <div class="tender-card-meta">
                    <div class="tender-meta-item">
                        <svg class="tender-meta-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 7h.01M7 3h5c.512 0 1.024.195 1.414.586l7 7a2 2 0 010 2.828l-7 7a2 2 0 01-2.828 0l-7-7A2 2 0 013 12V7a2 2 0 012-2z"/></svg>
                        {{ $t->method }}
                    </div>
                    <div class="tender-meta-item">
                        <svg class="tender-meta-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                        {{ $t->currency }} {{ number_format($t->estimated_value,0) }}
                    </div>
                    @if($t->evaluationCriteria->isNotEmpty())
                    <div class="tender-meta-item">
                        <svg class="tender-meta-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/></svg>
                        {{ $t->evaluationCriteria->count() }} criteria
                    </div>
                    @endif
                </div>
                <p style="font-size:.82rem;color:var(--muted);line-height:1.55;">{{ Str::limit($t->description,110) }}</p>
            </div>
            <div class="tender-card-footer">
                <div class="tender-deadline">
                    @php $days = (int) now()->diffInDays($t->submission_deadline, false); @endphp
                    @if($days > 0)
                        <strong>{{ $days }}d left</strong> &mdash; {{ \Carbon\Carbon::parse($t->submission_deadline)->format('d M Y') }}
                    @else
                        <strong style="color:var(--danger);">Closed</strong>
                    @endif
                </div>
                <a href="{{ route('supplier.tenders.show', $t) }}" class="sp-btn sp-btn-primary sp-btn-sm">View &amp; Apply</a>
            </div>
        </div>
        @endforeach
    </div>
    <div class="sp-pagination" style="margin-top:2rem;">
        @if($tenders->onFirstPage())<span class="disabled">← Prev</span>@else<a href="{{ $tenders->previousPageUrl() }}">← Prev</a>@endif
        @foreach($tenders->getUrlRange(1,$tenders->lastPage()) as $p => $url)
            <a href="{{ $url }}" class="{{ $p==$tenders->currentPage()?'active':'' }}">{{ $p }}</a>
        @endforeach
        @if($tenders->hasMorePages())<a href="{{ $tenders->nextPageUrl() }}">Next →</a>@else<span class="disabled">Next →</span>@endif
    </div>
    @endif
</div>
@endsection
