@extends('recruitment.layouts.portal')

@section('title', 'Offer Details')

@section('content')
<div style="margin-bottom: 2rem;">
    <a href="{{ route('candidate.my-offers') }}" style="color: var(--teal); text-decoration: none; font-size: .9rem;">&larr; Back to My Offers</a>
    <h1 style="font-size: 1.8rem; font-weight: 800; color: var(--navy); margin-top: .5rem; margin-bottom: .1rem;">Employment Offer</h1>
    <p style="color: var(--muted); font-size: .9rem;">{{ $offer->application?->campaign?->title ?? '—' }}</p>
</div>

@if(session('success'))
<div style="background: #dcf5ea; border: 1px solid #a7d9bc; color: #0d5c38; padding: 1rem 1.25rem; border-radius: 10px; margin-bottom: 1.5rem;">
    {{ session('success') }}
</div>
@endif

@if(session('error'))
<div style="background: #fee2e2; border: 1px solid #fca5a5; color: #991b1b; padding: 1rem 1.25rem; border-radius: 10px; margin-bottom: 1.5rem;">
    {{ session('error') }}
</div>
@endif

{{-- Status Banner --}}
@php
    $bannerColor = match($offer->status) {
        'approved'  => '#dcf5ea', 'accepted' => '#dcf5ea',
        'submitted' => '#fef3c7', 'declined', 'expired', 'withdrawn' => '#fee2e2',
        default => '#f1f5f9',
    };
    $bannerText = match($offer->status) {
        'approved'  => '✅ This offer is awaiting your response.',
        'accepted'  => '🎉 You accepted this offer! The HR team will be in touch.',
        'declined'  => 'You have declined this offer.',
        'expired'   => '⏰ This offer has expired.',
        'submitted' => 'This offer is under internal approval.',
        'draft'     => 'This offer is being prepared.',
        default     => ucfirst($offer->status),
    };
@endphp
<div style="background: {{ $bannerColor }}; border-radius: 10px; padding: 1rem 1.25rem; margin-bottom: 1.5rem; font-weight: 600; font-size: .95rem;">
    {{ $bannerText }}
</div>

{{-- Offer Detail Card --}}
<div style="background: #fff; border: 1px solid var(--border); border-radius: 16px; padding: 2rem; margin-bottom: 1.5rem;">
    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 1.25rem;">
        <h2 style="color: var(--navy); margin: 0; font-size: 1.1rem;">Offer Details</h2>
        @php
            $badgeBg = match($offer->status) {
                'approved', 'accepted' => '#dcf5ea',
                'declined', 'expired', 'withdrawn' => '#fee2e2',
                'submitted' => '#fef3c7',
                 default => '#f1f5f9',
            };
            $badgeText = match($offer->status) {
                'approved', 'accepted' => '#0d5c38',
                'declined', 'expired', 'withdrawn' => '#991b1b',
                'submitted' => '#92400e',
                 default => '#475569',
            };
        @endphp
        <span style="background: {{ $badgeBg }}; color: {{ $badgeText }}; font-size: 0.75rem; font-weight: 700; padding: 0.35rem 0.85rem; border-radius: 9999px; text-transform: uppercase;">
            {{ $offer->status === 'approved' ? 'AWAITING RESPONSE' : $offer->status }}
        </span>
    </div>

    <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 1.25rem;">
        <div>
            <span style="display: block; font-size: .8rem; color: var(--muted); font-weight: 500; margin-bottom: .25rem;">Position</span>
            <span style="color: var(--navy); font-weight: 700;">{{ $offer->application?->campaign?->jobPosition?->title ?? '—' }}</span>
        </div>
        <div>
            <span style="display: block; font-size: .8rem; color: var(--muted); font-weight: 500; margin-bottom: .25rem;">Campaign</span>
            <span style="color: var(--navy); font-weight: 700;">{{ $offer->application?->campaign?->title ?? '—' }}</span>
        </div>
        @if($offer->offered_salary)
        <div>
            <span style="display: block; font-size: .8rem; color: var(--muted); font-weight: 500; margin-bottom: .25rem;">Offered Salary</span>
            <span style="color: var(--teal2); font-weight: 800; font-size: 1.1rem;">ETB {{ number_format($offer->offered_salary, 2) }}</span>
        </div>
        @endif
        <div>
            <span style="display: block; font-size: .8rem; color: var(--muted); font-weight: 500; margin-bottom: .25rem;">Offer Date</span>
            <span style="color: var(--navy); font-weight: 700;">{{ $offer->offer_date?->format('M d, Y') ?? '—' }}</span>
        </div>
        @if($offer->offer_expiry_date)
        <div>
            <span style="display: block; font-size: .8rem; color: var(--muted); font-weight: 500; margin-bottom: .25rem;">
                Respond By
                @if($offer->status === 'approved' && $offer->offer_expiry_date->isPast())
                <span style="color: #dc2626;">(Expired)</span>
                @endif
            </span>
            <span style="color: {{ $offer->status === 'approved' && $offer->offer_expiry_date->isPast() ? '#dc2626' : 'var(--navy)' }}; font-weight: 700;">
                {{ $offer->offer_expiry_date->format('M d, Y') }}
            </span>
        </div>
        @endif
        @if($offer->issuer)
        <div>
            <span style="display: block; font-size: .8rem; color: var(--muted); font-weight: 500; margin-bottom: .25rem;">Issued By</span>
            <span style="color: var(--navy); font-weight: 700;">{{ $offer->issuer->name }}</span>
        </div>
        @endif
        @if($offer->responded_at)
        <div>
            <span style="display: block; font-size: .8rem; color: var(--muted); font-weight: 500; margin-bottom: .25rem;">Response Date</span>
            <span style="color: var(--navy); font-weight: 700;">{{ $offer->responded_at->format('M d, Y') }}</span>
        </div>
        @endif
    </div>

    @if($offer->notes)
    <div style="margin-top: 1.5rem; padding-top: 1.25rem; border-top: 1px solid var(--border);">
        <span style="font-size: .8rem; color: var(--muted); font-weight: 500; display: block; margin-bottom: .5rem;">Message from HR </span>
        <div style="color: var(--navy); font-size: .95rem; line-height: 1.5;">
            {!! $offer->notes !!}
        </div>
    </div>
    @endif

    @if($offer->getSignedLetterUrl())
    <div style="margin-top: 1.5rem; padding-top: 1.25rem; border-top: 1px solid var(--border); display: flex; align-items: center; justify-content: space-between;">
        <span style="font-size: .9rem; color: var(--navy); font-weight: 500;">Offer Letter Document</span>
        <a href="{{ $offer->getSignedLetterUrl() }}" target="_blank" download style="display: inline-flex; align-items: center; gap: .5rem; background: var(--navy); color: #fff; padding: .6rem 1.2rem; border-radius: 8px; font-size: .85rem; font-weight: 600; text-decoration: none; transition: background .2s;">
            <svg style="width: 16px; height: 16px;" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"></path></svg>
            Download Letter
        </a>
    </div>
    @endif

    @if($offer->decline_reason)
    <div style="margin-top: 1.5rem; padding-top: 1.25rem; border-top: 1px solid var(--border);">
        <span style="font-size: .8rem; color: var(--muted); font-weight: 500; display: block; margin-bottom: .35rem;">Your Decline Reason</span>
        <p style="color: var(--navy); margin: 0;">{{ $offer->decline_reason }}</p>
    </div>
    @endif
</div>

{{-- Accept / Decline Actions --}}
@if($offer->status === 'approved')
<div x-data="{ showDeclineForm: false }" style="display: flex; flex-direction: column; gap: 1rem;">

    <form action="{{ route('candidate.my-offers.accept', $offer) }}" method="POST"
          onsubmit="return confirm('Are you sure you want to accept this offer? This action cannot be undone.');">
        @csrf
        <button type="submit"
                style="width: 100%; background: #16a34a; color: #fff; border: none; padding: 1rem 2rem; border-radius: 10px; font-size: 1rem; font-weight: 700; cursor: pointer; transition: background .2s;"
                onmouseover="this.style.background='#15803d'"
                onmouseout="this.style.background='#16a34a'">
            ✅ Accept This Offer
        </button>
    </form>

    <button @click="showDeclineForm = !showDeclineForm"
            style="width: 100%; background: #fff; color: #dc2626; border: 2px solid #dc2626; padding: 1rem 2rem; border-radius: 10px; font-size: 1rem; font-weight: 700; cursor: pointer; transition: all .2s;"
            onmouseover="this.style.background='#fee2e2'"
            onmouseout="this.style.background='#fff'">
        ✕ Decline This Offer
    </button>

    <div x-show="showDeclineForm" x-transition style="background: #fff; border: 1px solid var(--border); border-radius: 12px; padding: 1.5rem;">
        <h3 style="color: var(--navy); margin: 0 0 1rem; font-size: 1rem;">Reason for declining (optional)</h3>
        <form action="{{ route('candidate.my-offers.decline', $offer) }}" method="POST">
            @csrf
            <textarea name="decline_reason" rows="4"
                      placeholder="Let us know why you're declining this offer..."
                      style="width: 100%; padding: .75rem; border: 1px solid var(--border); border-radius: 8px; font-size: .9rem; resize: vertical; box-sizing: border-box; margin-bottom: 1rem;"></textarea>
            <button type="submit"
                    style="background: #dc2626; color: #fff; border: none; padding: .75rem 2rem; border-radius: 8px; font-size: .9rem; font-weight: 700; cursor: pointer;">
                Confirm Decline
            </button>
        </form>
    </div>
</div>
@endif

<div style="margin-top: 1.5rem; text-align: center;">
    <a href="{{ route('candidate.my-applications.show', $offer->application_id) }}"
       style="color: var(--teal); font-size: .85rem; text-decoration: none;">View associated application &rarr;</a>
</div>
@endsection
