@extends('supplier.layouts.portal')
@section('title', 'Open Tenders')
@section('content')
<div class="sp-page">

    <div class="sp-page-header">
        <h1>Open Tenders &amp; RFQs</h1>
        <p>Browse active procurement opportunities. Login required to submit a bid.</p>
    </div>

    {{-- Search / filter --}}
    <form method="GET" style="margin-bottom:1.5rem;display:flex;gap:.75rem;flex-wrap:wrap;align-items:center;">
        <input class="sp-input" name="search" value="{{ request('search') }}" placeholder="Search tender title or number…" style="max-width:320px;">
        <button class="sp-btn sp-btn-primary" type="submit">Search</button>
        @if(request('search'))
            <a href="{{ route('supplier.public.tenders') }}" class="sp-btn sp-btn-outline">Clear</a>
        @endif
    </form>

    @if($tenders->isEmpty())
        <div class="sp-card" style="text-align:center;padding:3rem;">
            <p style="font-size:1.1rem;color:var(--muted);">No open tenders at this time.</p>
            <p style="margin-top:.5rem;color:var(--muted);font-size:.875rem;">Check back soon or <a href="{{ route('supplier.register') }}" style="color:var(--cyan2);">register</a> to receive notifications.</p>
        </div>
    @else
        <div class="sp-grid-3" style="margin-bottom:2rem;">
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
                            {{ $t->currency }} {{ number_format($t->estimated_value, 0) }}
                        </div>
                    </div>
                    <p style="font-size:.82rem;color:var(--muted);line-height:1.55;">{{ Str::limit($t->description, 120) }}</p>
                </div>
                <div class="tender-card-footer">
                    <div class="tender-deadline">
                        Deadline: <strong>{{ \Carbon\Carbon::parse($t->submission_deadline)->format('d M Y') }}</strong>
                    </div>
                    <a href="{{ route('supplier.public.tender', $t) }}" class="sp-btn sp-btn-primary sp-btn-sm">View Details</a>
                </div>
            </div>
            @endforeach
        </div>

        {{-- Pagination --}}
        <div class="sp-pagination">
            @if($tenders->onFirstPage())
                <span class="disabled">← Prev</span>
            @else
                <a href="{{ $tenders->previousPageUrl() }}">← Prev</a>
            @endif
            @foreach($tenders->getUrlRange(1, $tenders->lastPage()) as $page => $url)
                <a href="{{ $url }}" class="{{ $page == $tenders->currentPage() ? 'active' : '' }}">{{ $page }}</a>
            @endforeach
            @if($tenders->hasMorePages())
                <a href="{{ $tenders->nextPageUrl() }}">Next →</a>
            @else
                <span class="disabled">Next →</span>
            @endif
        </div>
    @endif

    {{-- CTA --}}
    @guest('supplier')
    <div class="sp-card" style="margin-top:2rem;text-align:center;background:linear-gradient(135deg,#003366,#00264d);border:none;color:#fff;padding:2.5rem;">
        <h2 style="font-size:1.4rem;font-weight:700;margin-bottom:.5rem;">Ready to submit a bid?</h2>
        <p style="color:rgba(255,255,255,.75);margin-bottom:1.5rem;">Register as a vendor or sign in to access full tender details and submit proposals.</p>
        <div style="display:flex;gap:1rem;justify-content:center;flex-wrap:wrap;">
            <a href="{{ route('supplier.register') }}" class="sp-btn sp-btn-primary sp-btn-lg">Register as Vendor</a>
            <a href="{{ route('supplier.login') }}" class="sp-btn" style="background:rgba(255,255,255,.15);color:#fff;font-size:1rem;padding:.75rem 1.75rem;border-radius:8px;text-decoration:none;font-weight:600;border:1.5px solid rgba(255,255,255,.3);">Sign In</a>
        </div>
    </div>
    @endguest
</div>
@endsection
