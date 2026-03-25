@php
    /** @var \App\Models\Recruitment\RecruitmentCampaign $campaign */
    use Illuminate\Support\Str;
    $candidate    = auth('candidate')->user();
    $alreadyApplied = $candidate
        ? \App\Models\Recruitment\RecruitmentApplication::where('candidate_id', $candidate->id)
              ->where('campaign_id', $campaign->id)->exists()
        : false;
    $applyLabel   = $campaign->channel?->submit_button_text ?: 'Apply Now';
@endphp
@extends('recruitment.layouts.portal')

@section('title', $campaign->title)
@section('description', Str::limit(strip_tags($campaign->description ?? ''), 160))

@push('styles')
<style>
    .rp-prose { font-size: .9rem; color: var(--text); line-height: 1.8; }
    .rp-prose h1,.rp-prose h2,.rp-prose h3 { color: var(--navy); margin: 1rem 0 .4rem; }
    .rp-prose ul,.rp-prose ol { padding-left: 1.4rem; margin: .5rem 0; }
    .rp-prose li { margin-bottom: .25rem; }
    .rp-prose strong { color: var(--navy); }
    .rp-prose p { margin-bottom: .8rem; }

    .collapsible-section details summary {
        list-style: none;
        cursor: pointer;
        display: flex;
        justify-content: space-between;
        align-items: center;
        padding: .85rem 0;
        font-size: .9rem;
        font-weight: 700;
        color: var(--navy);
        text-transform: uppercase;
        letter-spacing: .06em;
        border-bottom: 1px solid var(--border);
    }
    .collapsible-section details summary::-webkit-details-marker { display: none; }
    .collapsible-section details summary::after {
        content: '▾';
        font-size: 1rem;
        color: var(--muted);
        transition: transform .2s;
    }
    .collapsible-section details[open] summary::after {
        transform: rotate(-180deg);
    }
    .collapsible-section details .section-body {
        padding: 1rem 0 1.25rem;
    }

    .skill-pill {
        display: inline-flex;
        align-items: center;
        gap: .3rem;
        padding: .3rem .75rem;
        border-radius: 20px;
        font-size: .78rem;
        font-weight: 600;
        background: #e8f7f5;
        color: var(--teal2);
        border: 1px solid #99e6dc;
        margin: .2rem;
    }
    .skill-pill.required { background: #fff0e8; color: #c2440e; border-color: #f8b49c; }

    @media (max-width: 850px) {
        .rp-detail-grid { grid-template-columns: 1fr !important; }
        .rp-apply-sticky { position: static !important; }
    }
</style>
@endpush

@section('content')
{{-- Back link --}}
<div style="margin-bottom: 1.5rem;">
    <a href="{{ route('candidate.campaigns') }}" style="display: inline-flex; align-items: center; gap: .3rem; font-size: .85rem; color: var(--teal2); text-decoration: none; font-weight: 500;">
        <svg style="width: 14px; height: 14px;" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M10.5 19.5L3 12m0 0l7.5-7.5M3 12h18"/></svg>
        Back to all positions
    </a>
</div>

{{-- Success/info flash --}}
@if(session('application_success'))
    <div style="background: #d4f7e3; color: #0a6637; border: 1px solid #6fcf97; border-radius: 10px; padding: 1rem 1.5rem; margin-bottom: 1.5rem; font-size: .9rem; font-weight: 500;">
        ✅ {{ session('application_success') }}
    </div>
@endif
@if(session('info'))
    <div style="background: #e8f2ff; color: #1a56a8; border: 1px solid #93c5fd; border-radius: 10px; padding: 1rem 1.5rem; margin-bottom: 1.5rem; font-size: .9rem; font-weight: 500;">
        ℹ️ {{ session('info') }}
    </div>
@endif

<div class="rp-detail-grid" style="display: grid; grid-template-columns: 1fr 340px; gap: 1.75rem; align-items: start;">

    {{-- ── Main Content ── --}}
    <div>
        {{-- Hero Card --}}
        <div style="background: #fff; border: 1px solid var(--border); border-radius: 14px; overflow: hidden; margin-bottom: 1.25rem;">
            <div style="background: linear-gradient(135deg, var(--navy), #004d99); padding: 2rem 2rem 1.75rem;">
                <div style="display: flex; align-items: center; gap: .5rem; margin-bottom: .75rem; flex-wrap: wrap;">
                    <span class="badge badge-teal">{{ ucfirst(str_replace('_', ' ', $campaign->employment_type)) }}</span>
                    @if($campaign->channel)
                        <span class="badge" style="background: rgba(255,255,255,.15); color: #fff;">via {{ $campaign->channel->name }}</span>
                    @endif
                    @if($campaign->end_date && $campaign->end_date->isFuture())
                        <span class="badge" style="background: rgba(255,255,255,.1); color: rgba(255,255,255,.85);">Closes {{ $campaign->end_date->diffForHumans() }}</span>
                    @endif
                </div>
                <h1 style="color: #fff; font-size: 1.7rem; font-weight: 800; line-height: 1.3; margin: 0;">{{ $campaign->title }}</h1>
                @if($campaign->jobPosition)
                    <p style="color: rgba(255,255,255,.65); font-size: .9rem; margin-top: .4rem;">{{ $campaign->jobPosition->title }}</p>
                @endif
            </div>
        </div>

        {{-- Rich Content Sections (collapsible) --}}
        <div style="background: #fff; border: 1px solid var(--border); border-radius: 14px; padding: 1.75rem;" class="collapsible-section">

            {{-- About this role --}}
            @if($campaign->description)
            <details open>
                <summary>About This Role</summary>
                <div class="section-body rp-prose">
                    {!! $campaign->description !!}
                </div>
            </details>
            @endif

            {{-- Requirements --}}
            @if($campaign->requirements)
            <details open>
                <summary>Requirements</summary>
                <div class="section-body rp-prose">
                    {!! $campaign->requirements !!}
                </div>
            </details>
            @endif

            {{-- Why we're hiring --}}
            @if($campaign->reason_for_recruitment)
            <details>
                <summary>Why We're Hiring</summary>
                <div class="section-body rp-prose">
                    {!! $campaign->reason_for_recruitment !!}
                </div>
            </details>
            @endif

            {{-- Required Skills --}}
            @if($campaign->skills && $campaign->skills->count())
            <details open>
                <summary>Skills Required</summary>
                <div class="section-body">
                    @foreach($campaign->skills as $skill)
                        <span class="skill-pill {{ $skill->pivot->is_required ? 'required' : '' }}">
                            @if($skill->pivot->is_required)
                                <svg style="width:11px;height:11px;" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"/></svg>
                            @endif
                            {{ $skill->name }}
                            @if($skill->pivot->min_proficiency)
                                <span style="opacity:.65;font-size:.68rem;">({{ ucfirst($skill->pivot->min_proficiency) }})</span>
                            @endif
                        </span>
                    @endforeach
                    @if($campaign->skills->where('pivot.is_required', true)->count())
                        <p style="font-size:.75rem;color:var(--muted);margin-top:.75rem;">
                            <span style="color:#c2440e;font-weight:600;">■</span> Required &nbsp;
                            <span style="color:var(--teal2);font-weight:600;">■</span> Preferred
                        </p>
                    @endif
                </div>
            </details>
            @endif

        </div>
    </div>

    {{-- ── Sidebar ── --}}
    <div class="rp-apply-sticky" style="position: sticky; top: 80px; display: flex; flex-direction: column; gap: 1.25rem;">

        {{-- Apply card --}}
        <div style="background: linear-gradient(135deg, var(--navy), #004d99); border-radius: 14px; padding: 1.5rem; color: #fff; text-align: center;">
            <h3 style="font-size: 1rem; font-weight: 700; margin-bottom: .4rem;">Interested in this role?</h3>
            <p style="font-size: .82rem; color: rgba(255,255,255,.7); margin-bottom: 1.2rem; line-height:1.5;">
                Apply now and take the next step in your career.
            </p>

            @if($alreadyApplied)
                <div style="background: rgba(255,255,255,.12); border-radius: 10px; padding: .85rem 1rem; font-size: .88rem; color: rgba(255,255,255,.9); font-weight: 600;">
                    ✅ Application submitted
                </div>
                <a href="{{ route('candidate.my-applications') }}"
                   style="display: inline-block; margin-top: .75rem; font-size: .8rem; color: rgba(255,255,255,.7); text-decoration: underline;">
                    View my applications
                </a>
            @elseif($candidate)
                <a href="{{ route('candidate.campaigns.apply', $campaign) }}"
                   id="apply-btn"
                   style="display: inline-flex; align-items: center; justify-content: center; gap: .4rem; width: 100%;
                          padding: .75rem 1.5rem; border-radius: 10px; font-size: .95rem; font-weight: 700;
                          cursor: pointer; text-decoration: none; transition: all .15s;
                          background: var(--teal); color: #fff;"
                   onmouseover="this.style.background='#14b8a6'"
                   onmouseout="this.style.background='var(--teal)'">
                    {{ $applyLabel }}
                    <svg style="width: 15px; height: 15px;" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M13.5 4.5L21 12m0 0l-7.5 7.5M21 12H3"/></svg>
                </a>
            @else
                <a href="{{ route('candidate.login', ['redirect' => route('candidate.campaigns.apply', $campaign)]) }}"
                   style="display: inline-flex; align-items: center; justify-content: center; gap: .4rem; width: 100%;
                          padding: .75rem 1.5rem; border-radius: 10px; font-size: .95rem; font-weight: 700;
                          cursor: pointer; text-decoration: none; transition: all .15s;
                          background: var(--teal); color: #fff;"
                   onmouseover="this.style.background='#14b8a6'"
                   onmouseout="this.style.background='var(--teal)'">
                    Sign In to Apply
                    <svg style="width: 15px; height: 15px;" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M13.5 4.5L21 12m0 0l-7.5 7.5M21 12H3"/></svg>
                </a>
                <p style="font-size:.75rem; color:rgba(255,255,255,.55); margin-top:.6rem;">No account? <a href="{{ route('candidate.register') }}" style="color:rgba(255,255,255,.8);">Register free</a></p>
            @endif
        </div>

        {{-- Key Details --}}
        <div style="background: #fff; border: 1px solid var(--border); border-radius: 14px; padding: 1.5rem;">
            <h3 style="font-size: .8rem; font-weight: 700; text-transform: uppercase; letter-spacing: .06em; color: var(--muted); margin-bottom: 1rem;">Key Details</h3>
            <div style="display: flex; flex-direction: column; gap: .85rem;">

                @if($campaign->location)
                <div style="display: flex; align-items: flex-start; gap: .5rem;">
                    <svg style="width:16px;height:16px;color:var(--teal);flex-shrink:0;margin-top:2px;" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M15 10.5a3 3 0 11-6 0 3 3 0 016 0z"/><path stroke-linecap="round" stroke-linejoin="round" d="M19.5 10.5c0 7.142-7.5 11.25-7.5 11.25S4.5 17.642 4.5 10.5a7.5 7.5 0 0115 0z"/></svg>
                    <div>
                        <div style="font-size:.72rem;font-weight:600;color:var(--muted);text-transform:uppercase;letter-spacing:.04em;">Location</div>
                        <div style="font-size:.88rem;font-weight:500;color:var(--text);">{{ $campaign->location }}</div>
                    </div>
                </div>
                @endif

                <div style="display: flex; align-items: flex-start; gap: .5rem;">
                    <svg style="width:16px;height:16px;color:var(--teal);flex-shrink:0;margin-top:2px;" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 6v6h4.5m4.5 0a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                    <div>
                        <div style="font-size:.72rem;font-weight:600;color:var(--muted);text-transform:uppercase;letter-spacing:.04em;">Employment</div>
                        <div style="font-size:.88rem;font-weight:500;color:var(--text);">{{ ucfirst(str_replace('_', ' ', $campaign->employment_type)) }}</div>
                    </div>
                </div>

                @if($campaign->vacancies_needed > 0)
                <div style="display: flex; align-items: flex-start; gap: .5rem;">
                    <svg style="width:16px;height:16px;color:var(--teal);flex-shrink:0;margin-top:2px;" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M15 19.128a9.38 9.38 0 002.625.372 9.337 9.337 0 004.121-.952 4.125 4.125 0 00-7.533-2.493M15 19.128v-.003c0-1.113-.285-2.16-.786-3.07M15 19.128v.106A12.318 12.318 0 018.624 21c-2.331 0-4.512-.645-6.374-1.766l-.001-.109a6.375 6.375 0 0111.964-3.07M12 6.375a3.375 3.375 0 11-6.75 0 3.375 3.375 0 016.75 0zm8.25 2.25a2.625 2.625 0 11-5.25 0 2.625 2.625 0 015.25 0z"/></svg>
                    <div>
                        <div style="font-size:.72rem;font-weight:600;color:var(--muted);text-transform:uppercase;letter-spacing:.04em;">Openings</div>
                        <div style="font-size:.88rem;font-weight:500;color:var(--text);">{{ $campaign->vacancies_needed }} {{ Str::plural('position', $campaign->vacancies_needed) }}</div>
                    </div>
                </div>
                @endif

                @if($campaign->display_salary && ($campaign->salary_min || $campaign->salary_max))
                <div style="display: flex; align-items: flex-start; gap: .5rem;">
                    <svg style="width:16px;height:16px;color:var(--teal);flex-shrink:0;margin-top:2px;" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M2.25 18.75a60.07 60.07 0 0115.797 2.101c.727.198 1.453-.342 1.453-1.096V18.75M3.75 4.5v.75A.75.75 0 013 6h-.75m0 0v-.375c0-.621.504-1.125 1.125-1.125H20.25M2.25 6v9m18-10.5v.75c0 .414.336.75.75.75h.75m-1.5-1.5h.375c.621 0 1.125.504 1.125 1.125v9.75c0 .621-.504 1.125-1.125 1.125h-.375m1.5-1.5H21a.75.75 0 00-.75.75v.75m0 0H3.75m0 0h-.375a1.125 1.125 0 01-1.125-1.125V15m1.5 1.5v-.75A.75.75 0 003 15h-.75M15 10.5a3 3 0 11-6 0 3 3 0 016 0zm3 0h.008v.008H18V10.5zm-12 0h.008v.008H6V10.5z"/></svg>
                    <div>
                        <div style="font-size:.72rem;font-weight:600;color:var(--muted);text-transform:uppercase;letter-spacing:.04em;">Salary</div>
                        <div style="font-size:.88rem;font-weight:600;color:var(--teal2);">
                            @if($campaign->salary_min && $campaign->salary_max)
                                {{ $campaign->currency }} {{ number_format($campaign->salary_min) }} – {{ number_format($campaign->salary_max) }}
                            @elseif($campaign->salary_min)
                                From {{ $campaign->currency }} {{ number_format($campaign->salary_min) }}
                            @else
                                Up to {{ $campaign->currency }} {{ number_format($campaign->salary_max) }}
                            @endif
                        </div>
                    </div>
                </div>
                @endif

                @if($campaign->start_date)
                <div style="display: flex; align-items: flex-start; gap: .5rem;">
                    <svg style="width:16px;height:16px;color:var(--teal);flex-shrink:0;margin-top:2px;" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M6.75 3v2.25M17.25 3v2.25M3 18.75V7.5a2.25 2.25 0 012.25-2.25h13.5A2.25 2.25 0 0121 7.5v11.25m-18 0A2.25 2.25 0 005.25 21h13.5A2.25 2.25 0 0021 18.75m-18 0v-7.5A2.25 2.25 0 015.25 9h13.5A2.25 2.25 0 0121 11.25v7.5"/></svg>
                    <div>
                        <div style="font-size:.72rem;font-weight:600;color:var(--muted);text-transform:uppercase;letter-spacing:.04em;">Start Date</div>
                        <div style="font-size:.88rem;font-weight:500;color:var(--text);">{{ $campaign->start_date->format('M d, Y') }}</div>
                    </div>
                </div>
                @endif

                @if($campaign->end_date)
                <div style="display: flex; align-items: flex-start; gap: .5rem;">
                    <svg style="width:16px;height:16px;color:var(--teal);flex-shrink:0;margin-top:2px;" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m9-.75a9 9 0 11-18 0 9 9 0 0118 0zm-9 3.75h.008v.008H12v-.008z"/></svg>
                    <div>
                        <div style="font-size:.72rem;font-weight:600;color:var(--muted);text-transform:uppercase;letter-spacing:.04em;">Deadline</div>
                        <div style="font-size:.88rem;font-weight:500;color:{{ $campaign->end_date->isPast() ? 'var(--danger)' : 'var(--text)' }};">
                            {{ $campaign->end_date->format('M d, Y') }}
                        </div>
                    </div>
                </div>
                @endif

                @if($campaign->candidate_age_from || $campaign->candidate_age_to)
                <div style="display: flex; align-items: flex-start; gap: .5rem;">
                    <svg style="width:16px;height:16px;color:var(--teal);flex-shrink:0;margin-top:2px;" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M15.75 6a3.75 3.75 0 11-7.5 0 3.75 3.75 0 017.5 0zM4.501 20.118a7.5 7.5 0 0114.998 0A17.933 17.933 0 0112 21.75c-2.676 0-5.216-.584-7.499-1.632z"/></svg>
                    <div>
                        <div style="font-size:.72rem;font-weight:600;color:var(--muted);text-transform:uppercase;letter-spacing:.04em;">Age Range</div>
                        <div style="font-size:.88rem;font-weight:500;color:var(--text);">
                            {{ $campaign->candidate_age_from ?? '—' }} – {{ $campaign->candidate_age_to ?? '—' }} years
                        </div>
                    </div>
                </div>
                @endif

            </div>
        </div>
    </div>
</div>
@endsection
