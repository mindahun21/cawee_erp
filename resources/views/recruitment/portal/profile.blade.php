@php
    /** @var \App\Models\Recruitment\RecruitmentCandidate $candidate */
@endphp
@extends('recruitment.layouts.portal')

@section('title', 'My Profile')

@section('content')
<div style="margin-bottom: 2rem;">
    <a href="{{ route('candidate.campaigns') }}" style="color: var(--teal); text-decoration: none; font-size: .9rem;">&larr; Back to Open Positions</a>
    <h1 style="font-size: 1.8rem; font-weight: 800; color: var(--navy); margin-top: .5rem; margin-bottom: .35rem;">My Profile</h1>
    <p style="color: var(--muted); font-size: .95rem;">View and update your personal information.</p>
</div>

<div style="background: #fff; border: 1px solid var(--border); border-radius: 12px; padding: 2rem;">
    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1.5rem;">
        <div>
            <span style="font-size: .85rem; color: var(--muted); font-weight: 600; text-transform: uppercase;">First Name</span>
            <div style="font-size: 1.05rem; font-weight: 500; color: var(--navy); margin-top: .25rem;">{{ $candidate->first_name }}</div>
        </div>
        <div>
            <span style="font-size: .85rem; color: var(--muted); font-weight: 600; text-transform: uppercase;">Last Name</span>
            <div style="font-size: 1.05rem; font-weight: 500; color: var(--navy); margin-top: .25rem;">{{ $candidate->last_name ?? '—' }}</div>
        </div>
        <div>
            <span style="font-size: .85rem; color: var(--muted); font-weight: 600; text-transform: uppercase;">Email Address</span>
            <div style="font-size: 1.05rem; font-weight: 500; color: var(--navy); margin-top: .25rem;">{{ $candidate->email }}</div>
        </div>
        <div>
            <span style="font-size: .85rem; color: var(--muted); font-weight: 600; text-transform: uppercase;">Phone Number</span>
            <div style="font-size: 1.05rem; font-weight: 500; color: var(--navy); margin-top: .25rem;">{{ $candidate->phone ?? '—' }}</div>
        </div>
    </div>
    
    <div style="margin-top: 2rem; padding-top: 1.5rem; border-top: 1px solid var(--border);">
        <p style="color: var(--muted); font-size: .9rem;">To edit your fully detailed profile and update your resume or skills, please contact human resources or update it during your next application.</p>
    </div>
</div>
@endsection
