@extends('supplier.layouts.portal')
@section('title', $tender->title)
@section('content')
<div class="sp-page">
    <div class="sp-breadcrumb">
        <a href="{{ route('supplier.tenders') }}">Open Tenders</a>
        <span>/</span>
        <span>{{ $tender->tender_number }}</span>
    </div>

    <div style="background:linear-gradient(135deg,#003366,#00264d);border-radius:14px;padding:2rem 2.5rem;color:#fff;margin-bottom:1.75rem;display:flex;justify-content:space-between;align-items:flex-start;gap:1rem;flex-wrap:wrap;">
        <div>
            <div style="font-size:.78rem;color:var(--cyan);font-weight:600;letter-spacing:.08em;text-transform:uppercase;margin-bottom:.4rem;">{{ $tender->tender_number }}</div>
            <h1 style="font-size:1.6rem;font-weight:700;line-height:1.3;">{{ $tender->title }}</h1>
            <div style="display:flex;gap:1rem;margin-top:.75rem;flex-wrap:wrap;">
                <span class="badge badge-cyan">{{ $tender->method }}</span>
                <span style="font-size:.85rem;opacity:.8;">{{ $tender->currency }} {{ number_format($tender->estimated_value,0) }} estimated</span>
            </div>
        </div>
        @if($myBid)
            <div style="text-align:center;">
                <span class="badge badge-success" style="padding:.65rem 1.1rem;font-size:.875rem;display:inline-block;">✓ Bid Submitted</span>
                <div style="font-size:.75rem;opacity:.7;margin-top:.35rem;">{{ \Carbon\Carbon::parse($myBid->submission_date)->format('d M Y') }}</div>
            </div>
        @else
            @php $daysLeft = (int) now()->diffInDays($tender->submission_deadline, false); @endphp
            @if($daysLeft > 0)
                <a href="{{ route('supplier.bids.create', $tender) }}" class="sp-btn sp-btn-primary sp-btn-lg" style="white-space:nowrap;">Submit Your Bid →</a>
            @else
                <span class="badge badge-danger" style="padding:.65rem 1.1rem;font-size:.875rem;">Deadline Passed</span>
            @endif
        @endif
    </div>

    <div style="display:grid;grid-template-columns:2fr 1fr;gap:1.5rem;align-items:start;">
        <div>
            <div class="sp-card"><div class="sp-card-header"><div class="sp-card-title">Description</div></div><p style="font-size:.9rem;line-height:1.7;">{{ $tender->description }}</p></div>
            @if($tender->terms_and_conditions)
            <div class="sp-card"><div class="sp-card-header"><div class="sp-card-title">Terms &amp; Conditions</div></div><p style="font-size:.87rem;line-height:1.7;color:var(--muted);">{{ $tender->terms_and_conditions }}</p></div>
            @endif
            @if($tender->evaluationCriteria->isNotEmpty())
            <div class="sp-card">
                <div class="sp-card-header"><div class="sp-card-title">Evaluation Criteria</div><div class="sp-card-sub">Your bid will be scored on these weighted criteria</div></div>
                <div class="sp-table-wrap">
                    <table class="sp-table">
                        <thead><tr><th>Criterion</th><th>Weight</th><th>Description</th></tr></thead>
                        <tbody>
                            @foreach($tender->evaluationCriteria as $c)
                            <tr>
                                <td><strong>{{ $c->name }}</strong></td>
                                <td><span class="badge badge-cyan">{{ $c->weight }}%</span></td>
                                <td style="color:var(--muted);font-size:.85rem;">{{ $c->description ?? '—' }}</td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
            @endif

            @if($myBid)
            <div class="sp-card" style="border-left:4px solid var(--success);">
                <div class="sp-card-title" style="margin-bottom:1rem;">Your Submitted Bid</div>
                <div style="display:grid;grid-template-columns:repeat(3,1fr);gap:1rem;">
                    @foreach([
                        ['Bid Amount', $myBid->currency.' '.number_format($myBid->bid_amount,0)],
                        ['Delivery', $myBid->delivery_days.' days'],
                        ['Valid Until', \Carbon\Carbon::parse($myBid->validity_date)->format('d M Y')],
                        ['Status', $myBid->status],
                        ['Ref #', $myBid->reference_number ?? '—'],
                        ['Score', $myBid->composite_score ? number_format($myBid->composite_score,1).'/100' : 'Pending'],
                    ] as [$l,$v])
                    <div style="font-size:.82rem;"><span style="color:var(--muted);display:block;margin-bottom:.2rem;font-weight:600;font-size:.7rem;text-transform:uppercase;letter-spacing:.04em;">{{ $l }}</span><strong>{{ $v }}</strong></div>
                    @endforeach
                </div>
            </div>
            @endif
        </div>

        <div>
            <div class="sp-card">
                <div class="sp-card-title" style="margin-bottom:1rem;">Key Dates</div>
                @foreach([
                    ['Issue Date', $tender->issue_date],
                    ['Bid Deadline', $tender->submission_deadline],
                    ['Opening Date', $tender->opening_date],
                ] as [$label, $date])
                <div style="display:flex;justify-content:space-between;padding:.6rem 0;border-bottom:1px solid var(--border);font-size:.85rem;">
                    <span style="color:var(--muted);">{{ $label }}</span>
                    <strong>{{ \Carbon\Carbon::parse($date)->format('d M Y') }}</strong>
                </div>
                @endforeach
            </div>

            @php $daysLeft = (int) now()->diffInDays($tender->submission_deadline, false); @endphp
            <div class="sp-card" style="margin-top:1rem;border-left:4px solid {{ $daysLeft>7?'var(--cyan)':($daysLeft>0?'var(--warn)':'var(--danger)') }};">
                <div style="font-size:.72rem;font-weight:700;text-transform:uppercase;letter-spacing:.05em;color:var(--muted);">Time Remaining</div>
                <div style="font-size:1.4rem;font-weight:800;color:{{ $daysLeft>7?'var(--cyan2)':($daysLeft>0?'var(--warn)':'var(--danger)') }};margin-top:.25rem;">
                    {{ $daysLeft > 0 ? $daysLeft.' days left' : 'Closed' }}
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
