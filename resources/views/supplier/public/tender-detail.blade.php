@extends('supplier.layouts.portal')
@section('title', $tender->title)
@section('content')
<div class="sp-page">

    <div class="sp-breadcrumb">
        <a href="{{ route('supplier.public.tenders') }}">Tenders</a>
        <span>/</span>
        <span>{{ $tender->tender_number }}</span>
    </div>

    {{-- Header --}}
    <div style="background:linear-gradient(135deg,#003366,#00264d);border-radius:14px;padding:2rem 2.5rem;color:#fff;margin-bottom:1.75rem;display:flex;justify-content:space-between;align-items:flex-start;gap:1rem;flex-wrap:wrap;">
        <div>
            <div style="font-size:.78rem;color:var(--cyan);font-weight:600;letter-spacing:.08em;text-transform:uppercase;margin-bottom:.4rem;">{{ $tender->tender_number }}</div>
            <h1 style="font-size:1.6rem;font-weight:700;line-height:1.3;">{{ $tender->title }}</h1>
            <div style="display:flex;gap:1rem;margin-top:.75rem;flex-wrap:wrap;">
                <span class="badge badge-cyan">{{ $tender->method }}</span>
                <span style="font-size:.85rem;opacity:.8;">{{ $tender->currency }} {{ number_format($tender->estimated_value, 0) }} estimated</span>
            </div>
        </div>
        @auth('supplier')
            @if(!isset($myBid))
                <a href="{{ route('supplier.bids.create', $tender) }}" class="sp-btn sp-btn-primary sp-btn-lg" style="white-space:nowrap;">Submit Bid →</a>
            @else
                <span class="badge badge-success" style="padding:.6rem 1rem;font-size:.85rem;">✓ Bid Submitted</span>
            @endif
        @else
            <a href="{{ route('supplier.login') }}" class="sp-btn sp-btn-primary sp-btn-lg" style="white-space:nowrap;">Login to Bid →</a>
        @endauth
    </div>

    <div style="display:grid;grid-template-columns:2fr 1fr;gap:1.5rem;align-items:start;">
        <div>
            {{-- Description --}}
            <div class="sp-card">
                <div class="sp-card-header"><div class="sp-card-title">Tender Description</div></div>
                <p style="font-size:.9rem;line-height:1.7;color:var(--text);">{{ $tender->description }}</p>
            </div>

            {{-- Terms --}}
            @if($tender->terms_and_conditions)
            <div class="sp-card">
                <div class="sp-card-header"><div class="sp-card-title">Terms &amp; Conditions</div></div>
                <p style="font-size:.87rem;line-height:1.7;color:var(--muted);">{{ $tender->terms_and_conditions }}</p>
            </div>
            @endif

            {{-- Evaluation Criteria --}}
            @if($tender->evaluationCriteria->isNotEmpty())
            <div class="sp-card">
                <div class="sp-card-header"><div class="sp-card-title">Evaluation Criteria</div></div>
                <div class="sp-table-wrap">
                    <table class="sp-table">
                        <thead><tr>
                            <th>Criterion</th>
                            <th>Weight</th>
                            <th>Description</th>
                        </tr></thead>
                        <tbody>
                            @foreach($tender->evaluationCriteria as $c)
                            <tr>
                                <td><strong>{{ $c->name }}</strong></td>
                                <td><span class="badge badge-cyan">{{ $c->weight }}%</span></td>
                                <td style="color:var(--muted);">{{ $c->description ?? '—' }}</td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
            @endif
        </div>

        <div>
            {{-- Key Dates --}}
            <div class="sp-card">
                <div class="sp-card-title" style="margin-bottom:1rem;">Key Dates</div>
                @foreach([
                    ['Issue Date', $tender->issue_date],
                    ['Bid Deadline', $tender->submission_deadline],
                    ['Opening Date', $tender->opening_date],
                    ['Award Date', $tender->award_date],
                ] as [$label, $date])
                <div style="display:flex;justify-content:space-between;padding:.6rem 0;border-bottom:1px solid var(--border);font-size:.85rem;">
                    <span style="color:var(--muted);">{{ $label }}</span>
                    <strong>{{ \Carbon\Carbon::parse($date)->format('d M Y') }}</strong>
                </div>
                @endforeach
            </div>

            {{-- Deadline alert --}}
            @php $daysLeft = (int) now()->diffInDays($tender->submission_deadline, false); @endphp
            <div class="sp-card" style="border-left:4px solid {{ $daysLeft > 7 ? 'var(--cyan)' : ($daysLeft > 0 ? 'var(--warn)' : 'var(--danger)') }};margin-top:1rem;">
                <div style="font-size:.75rem;font-weight:700;text-transform:uppercase;letter-spacing:.05em;color:var(--muted);">Submission Deadline</div>
                <div style="font-size:1.3rem;font-weight:800;color:{{ $daysLeft > 7 ? 'var(--cyan2)' : ($daysLeft > 0 ? 'var(--warn)' : 'var(--danger)') }};margin-top:.25rem;">
                    {{ $daysLeft > 0 ? $daysLeft.' days left' : 'Closed' }}
                </div>
                <div style="font-size:.8rem;color:var(--muted);margin-top:.2rem;">{{ \Carbon\Carbon::parse($tender->submission_deadline)->format('l, d F Y') }}</div>
            </div>

            @guest('supplier')
            <div class="sp-card" style="margin-top:1rem;background:#f8fafc;text-align:center;">
                <p style="font-size:.875rem;color:var(--muted);margin-bottom:1rem;">Sign in or register to submit a bid for this tender.</p>
                <a href="{{ route('supplier.login') }}" class="sp-btn sp-btn-navy" style="width:100%;justify-content:center;display:flex;">Sign In to Bid</a>
                <a href="{{ route('supplier.register') }}" style="display:block;text-align:center;margin-top:.75rem;font-size:.8rem;color:var(--cyan2);text-decoration:none;">Register as a Vendor →</a>
            </div>
            @endguest
        </div>
    </div>
</div>
@endsection
