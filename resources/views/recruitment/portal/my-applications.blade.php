@extends('recruitment.layouts.portal')

@section('title', 'My Applications')

@section('content')
<div style="margin-bottom: 2rem;">
    <a href="{{ route('candidate.campaigns') }}" style="color: var(--teal); text-decoration: none; font-size: .9rem;">&larr; Back to Positions</a>
    <h1 style="font-size: 1.8rem; font-weight: 800; color: var(--navy); margin-top: .5rem; margin-bottom: .35rem;">My Applications</h1>
</div>

@if($applications->isEmpty())
    <div style="text-align: center; padding: 4rem 2rem; background: #fff; border-radius: 16px; border: 1px solid var(--border);">
        <p style="color: var(--muted); font-size: .9rem;">You haven't applied to any positions yet.</p>
        <a href="{{ route('candidate.campaigns') }}" style="display: inline-block; margin-top: 1rem; background: var(--teal); color: #fff; padding: .5rem 1rem; border-radius: 6px; text-decoration: none;">Browse Open Positions</a>
    </div>
@else
    <div style="display: flex; flex-direction: column; gap: 1rem;">
        @foreach($applications as $application)
            <a href="{{ route('candidate.my-applications.show', $application) }}"
               style="background: #fff; border: 1px solid var(--border); border-radius: 12px; padding: 1.5rem; display: flex; justify-content: space-between; align-items: center; text-decoration: none; transition: border-color .15s, box-shadow .15s;"
               onmouseover="this.style.borderColor='var(--teal)'; this.style.boxShadow='0 2px 8px rgba(13,148,136,.1)'"
               onmouseout="this.style.borderColor='var(--border)'; this.style.boxShadow='none'">
                <div>
                    <h3 style="color: var(--navy); margin: 0 0 .25rem 0;">{{ $application->campaign->title }}</h3>
                    <p style="color: var(--muted); margin: 0; font-size: .85rem;">Applied on {{ $application->created_at->format('M d, Y') }}</p>
                </div>
                <div style="display: flex; align-items: center; gap: .75rem;">
                    <span style="background: #f1f5f9; color: #475569; padding: .35rem .75rem; border-radius: 99px; font-size: .8rem; font-weight: 600; text-transform: capitalize;">
                        {{ str_replace('_', ' ', $application->status) }}
                    </span>
                    <svg style="width: 16px; height: 16px; color: var(--muted);" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M8.25 4.5l7.5 7.5-7.5 7.5"/></svg>
                </div>
            </a>
        @endforeach
    </div>
@endif
@endsection
