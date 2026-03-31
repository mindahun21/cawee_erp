@extends('recruitment.layouts.portal')

@section('title', 'My Offers')

@section('content')
<div style="margin-bottom: 2rem;">
    <a href="{{ route('candidate.campaigns') }}" style="color: var(--teal); text-decoration: none; font-size: .9rem;">&larr; Back to Positions</a>
    <h1 style="font-size: 1.8rem; font-weight: 800; color: var(--navy); margin-top: .5rem; margin-bottom: .35rem;">My Offers</h1>
    <p style="color: var(--muted); font-size: .9rem;">Review and respond to your employment offers below.</p>
</div>

@if(session('success'))
<div style="background: #dcf5ea; border: 1px solid #a7d9bc; color: #0d5c38; padding: 1rem 1.25rem; border-radius: 10px; margin-bottom: 1.5rem;">
    {{ session('success') }}
</div>
@endif

@if($offers->isEmpty())
<div style="text-align: center; padding: 4rem 2rem; background: #fff; border-radius: 16px; border: 1px solid var(--border);">
    <div style="font-size: 3rem; margin-bottom: 1rem;">📄</div>
    <h3 style="color: var(--navy); margin-bottom: .5rem;">No Offers Yet</h3>
    <p style="color: var(--muted); font-size: .9rem;">You don't have any employment offers at this time. Check back later!</p>
    <a href="{{ route('candidate.my-applications') }}" style="display: inline-block; margin-top: 1.5rem; color: var(--teal); text-decoration: none; font-size: .9rem;">View My Applications &rarr;</a>
</div>
@else
<div style="display: flex; flex-direction: column; gap: 1rem;">
    @foreach($offers as $offer)
    @php
        $badgeColor = match($offer->status) {
            'approved'  => '#dcf5ea', 'draft' => '#f1f5f9',
            'submitted' => '#fef3c7', 'accepted' => '#dcf5ea',
            'declined', 'expired', 'withdrawn' => '#fee2e2',
            default => '#f1f5f9',
        };
        $textColor = match($offer->status) {
            'approved', 'accepted' => '#0d5c38', 'submitted' => '#92400e',
            'declined', 'expired', 'withdrawn' => '#991b1b',
            default => '#475569',
        };
        $isActive = $offer->status === 'approved';
    @endphp
    <a href="{{ route('candidate.my-offers.show', $offer) }}"
       style="background: #fff; border: {{ $isActive ? '2px solid var(--teal)' : '1px solid var(--border)' }}; border-radius: 12px; padding: 1.5rem; display: flex; justify-content: space-between; align-items: center; text-decoration: none; transition: border-color .15s, box-shadow .15s;"
       onmouseover="this.style.boxShadow='0 2px 8px rgba(13,148,136,.1)'"
       onmouseout="this.style.boxShadow='none'">
        <div>
            <h3 style="color: var(--navy); margin: 0 0 .25rem 0;">{{ $offer->application?->campaign?->title ?? '—' }}</h3>
            <p style="color: var(--muted); margin: 0 0 .4rem; font-size: .85rem;">{{ $offer->application?->campaign?->jobPosition?->title ?? '—' }}</p>
            @if($offer->offered_salary)
            <p style="color: var(--teal2); font-weight: 700; margin: 0; font-size: .9rem;">ETB {{ number_format($offer->offered_salary, 2) }}</p>
            @endif
            @if($offer->offer_expiry_date && $offer->status === 'approved')
            <p style="color: #dc2626; font-size: .8rem; margin: .3rem 0 0;">Respond by: {{ $offer->offer_expiry_date->format('M d, Y') }}</p>
            @endif
        </div>
        <div style="display: flex; align-items: center; gap: .75rem;">
            <span style="background: {{ $badgeColor }}; color: {{ $textColor }}; padding: .35rem .75rem; border-radius: 99px; font-size: .8rem; font-weight: 600; text-transform: capitalize;">
                {{ str_replace('_', ' ', $offer->status) }}
            </span>
            @if($isActive)
            <span style="background: #003366; color: #fff; padding: .35rem .75rem; border-radius: 99px; font-size: .8rem; font-weight: 600;">Action Required</span>
            @endif
            <svg style="width: 16px; height: 16px; color: var(--muted);" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M8.25 4.5l7.5 7.5-7.5 7.5"/></svg>
        </div>
    </a>
    @endforeach
</div>
@endif
@endsection
