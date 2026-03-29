@php
    /** @var \App\Models\Recruitment\RecruitmentApplication $application */
    /** @var \App\Models\Recruitment\RecruitmentCandidate $candidate */
    $campaign  = $application->campaign;
    $position  = $campaign?->jobPosition;
    $dept      = $position?->department;
    $appCandidate = $application->candidate;
@endphp
@extends('recruitment.layouts.portal')

@section('title', 'Application — ' . ($campaign->title ?? 'Details'))

@push('styles')
<style>
    .app-tabs { display: flex; gap: 0; border-bottom: 2px solid var(--border); margin-bottom: 1.5rem; }
    .app-tab {
        padding: .65rem 1.25rem; font-size: .88rem; font-weight: 600;
        color: var(--muted); cursor: pointer; border: none; background: none;
        border-bottom: 3px solid transparent; margin-bottom: -2px; transition: all .15s;
    }
    .app-tab:hover { color: var(--navy); }
    .app-tab.active { color: var(--teal2); border-bottom-color: var(--teal); }
    .app-tab-panel { display: none; }
    .app-tab-panel.active { display: block; }

    .detail-card { background: #fff; border: 1px solid var(--border); border-radius: 12px; padding: 1.5rem; margin-bottom: 1.25rem; }
    .detail-card h3 { font-size: .95rem; font-weight: 700; color: var(--navy); margin-bottom: 1rem; padding-bottom: .5rem; border-bottom: 1px solid var(--border); }
    .detail-grid { display: grid; grid-template-columns: 1fr 1fr; gap: .85rem 2rem; }
    .detail-item label { display: block; font-size: .72rem; font-weight: 600; color: var(--muted); text-transform: uppercase; letter-spacing: .04em; margin-bottom: .15rem; }
    .detail-item span { font-size: .88rem; color: var(--text); font-weight: 500; }
    .detail-item span.empty { color: var(--muted); font-style: italic; font-weight: 400; }

    .status-pill {
        display: inline-flex; align-items: center; padding: .3rem .75rem;
        border-radius: 20px; font-size: .78rem; font-weight: 600; text-transform: capitalize;
    }
    .status-applied { background: #e8f2ff; color: #1a56a8; }
    .status-under_review { background: #fef3c7; color: #92400e; }
    .status-shortlisted { background: #d1fae5; color: #065f46; }
    .status-rejected { background: #fee2e2; color: #991b1b; }
    .status-hired { background: #d1fae5; color: #065f46; }
    .status-withdrawn { background: #f1f3f6; color: #64748b; }
    .status-interview_scheduled { background: #e0e7ff; color: #3730a3; }
    .status-offer_pending { background: #fef3c7; color: #92400e; }
    .status-offer_accepted { background: #d1fae5; color: #065f46; }
    .status-offer_declined { background: #fee2e2; color: #991b1b; }

    .skill-pill-sm {
        display: inline-flex; align-items: center; gap: .2rem;
        padding: .2rem .6rem; border-radius: 16px;
        font-size: .75rem; font-weight: 600; margin: .15rem;
        background: #e8f7f5; color: var(--teal2); border: 1px solid #99e6dc;
    }

    .history-row { padding: .75rem 0; border-bottom: 1px solid #f1f3f6; }
    .history-row:last-child { border-bottom: none; }

    @media (max-width: 640px) {
        .detail-grid { grid-template-columns: 1fr; }
    }
</style>
@endpush

@section('content')
<div style="max-width: 960px; margin: 0 auto;">

    {{-- Back --}}
    <div style="margin-bottom: 1.5rem;">
        <a href="{{ route('candidate.my-applications') }}"
           style="display: inline-flex; align-items: center; gap: .3rem; font-size: .85rem; color: var(--teal2); text-decoration: none; font-weight: 500;">
            <svg style="width: 14px; height: 14px;" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M10.5 19.5L3 12m0 0l7.5-7.5M3 12h18"/></svg>
            Back to My Applications
        </a>
    </div>

    {{-- Header Card --}}
    <div style="background: linear-gradient(135deg, var(--navy), #004d99); border-radius: 14px; padding: 2rem; color: #fff; margin-bottom: 1.5rem;">
        <div style="display: flex; justify-content: space-between; align-items: flex-start; flex-wrap: wrap; gap: 1rem;">
            <div>
                <p style="font-size: .78rem; color: rgba(255,255,255,.55); text-transform: uppercase; letter-spacing: .06em; margin-bottom: .25rem;">Application</p>
                <h1 style="font-size: 1.5rem; font-weight: 800; margin: 0 0 .25rem;">{{ $campaign->title ?? 'Position' }}</h1>
                @if($position)
                    <p style="color: rgba(255,255,255,.65); font-size: .88rem; margin: 0;">{{ $position->name ?? $position->title ?? '' }}@if($dept) — {{ $dept->name }}@endif</p>
                @endif
            </div>
            <div>
                <span class="status-pill status-{{ $application->status }}">{{ str_replace('_', ' ', $application->status) }}</span>
                
                @if(in_array($application->status, ['applied', 'under_review', 'shortlisted', 'interview_scheduled']))
                <form action="{{ route('candidate.my-applications.withdraw', $application) }}" method="POST" 
                      style="display: inline-block; margin-left: .5rem;"
                      onsubmit="return confirm('Are you sure you want to withdraw this application? This action cannot be undone.')">
                    @csrf
                    <button type="submit" 
                            style="background: #ef4444; color: #ffffff; border: none; padding: .5rem 1rem; border-radius: 8px; font-size: .85rem; font-weight: 700; cursor: pointer; transition: all .2s; box-shadow: 0 1px 2px rgba(0,0,0,0.1);" 
                            onmouseover="this.style.background='#dc2626'" 
                            onmouseout="this.style.background='#ef4444'">
                        Withdraw Application
                    </button>
                </form>
                @endif
            </div>
        </div>
        <div style="margin-top: 1rem; display: flex; gap: 2rem; flex-wrap: wrap; font-size: .82rem; color: rgba(255,255,255,.7);">
            <span>Applied: {{ $application->applied_at ? $application->applied_at->format('M d, Y') : $application->created_at->format('M d, Y') }}</span>
            @if($campaign->employment_type)
                <span>{{ ucfirst(str_replace('_', ' ', $campaign->employment_type)) }}</span>
            @endif
            @if($campaign->location)
                <span>{{ $campaign->location }}</span>
            @endif
        </div>
    </div>

    {{-- Tabs --}}
    <div x-data="{ tab: 'application' }">
        <div class="app-tabs">
            <button class="app-tab" :class="{ active: tab === 'application' }" @click="tab = 'application'">Application</button>
            <button class="app-tab" :class="{ active: tab === 'candidate' }" @click="tab = 'candidate'">My Profile</button>
            <button class="app-tab" :class="{ active: tab === 'position' }" @click="tab = 'position'">Position Details</button>
        </div>

        {{-- TAB: Application --}}
        <div class="app-tab-panel" :class="{ active: tab === 'application' }">
            @if($application->status === 'rejected' && $application->rejection_reason)
            <div style="background: #fff1f2; border: 1px solid #fecaca; border-radius: 12px; padding: 1.25rem; margin-bottom: 1.25rem; display: flex; gap: 1rem; align-items: flex-start;">
                <div style="background: #fee2e2; padding: .5rem; border-radius: 50%;">
                    <svg style="width: 20px; height: 20px; color: #b91c1c;" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m9-.75a9 9 0 11-18 0 9 9 0 0118 0zm-9 3.75h.008v.008H12v-.008z"/></svg>
                </div>
                <div>
                    <h4 style="color: #991b1b; margin: 0 0 .25rem 0; font-size: .95rem; font-weight: 700;">Application Status: Rejected</h4>
                    <p style="color: #b91c1c; margin: 0; font-size: .88rem; line-height: 1.5;"><strong>Reason:</strong> {{ $application->rejection_reason }}</p>
                </div>
            </div>
            @endif

            <div class="detail-card">
                <h3>Application Details</h3>
                <div class="detail-grid">
                    <div class="detail-item">
                        <label>Status</label>
                        <span class="status-pill status-{{ $application->status }}">{{ str_replace('_', ' ', $application->status) }}</span>
                    </div>
                    <div class="detail-item">
                        <label>Applied On</label>
                        <span>{{ $application->applied_at ? $application->applied_at->format('M d, Y h:i A') : $application->created_at->format('M d, Y h:i A') }}</span>
                    </div>
                    <div class="detail-item">
                        <label>Campaign</label>
                        <span>{{ $campaign->title ?? '—' }}</span>
                    </div>
                    <div class="detail-item">
                        <label>Campaign Code</label>
                        <span>{{ $campaign->campaign_code ?? '—' }}</span>
                    </div>
                    @if($application->desired_salary)
                    <div class="detail-item">
                        <label>Desired Salary</label>
                        <span>{{ number_format($application->desired_salary, 2) }}</span>
                    </div>
                    @endif
                    @if($application->cover_letter)
                    <div class="detail-item" style="grid-column: 1 / -1;">
                        <label>Cover Letter</label>
                        <span style="white-space: pre-wrap; line-height: 1.6;">{{ $application->cover_letter }}</span>
                    </div>
                    @endif
                    @if($application->introduce_yourself)
                    <div class="detail-item" style="grid-column: 1 / -1;">
                        <label>Introduction</label>
                        <span style="white-space: pre-wrap; line-height: 1.6;">{{ $application->introduce_yourself }}</span>
                    </div>
                    @endif
                </div>
            </div>
        </div>

        {{-- TAB: Candidate Profile --}}
        <div class="app-tab-panel" :class="{ active: tab === 'candidate' }">
            {{-- Personal Info --}}
            <div class="detail-card">
                <h3>Personal Information</h3>
                <div class="detail-grid">
                    <div class="detail-item">
                        <label>Full Name</label>
                        <span>{{ $appCandidate->first_name }} {{ $appCandidate->last_name }}</span>
                    </div>
                    <div class="detail-item">
                        <label>Candidate Code</label>
                        <span>{{ $appCandidate->candidate_code }}</span>
                    </div>
                    <div class="detail-item">
                        <label>Email</label>
                        <span>{{ $appCandidate->email }}</span>
                    </div>
                    <div class="detail-item">
                        <label>Phone</label>
                        <span>{{ $appCandidate->phone ?: '—' }}</span>
                    </div>
                    <div class="detail-item">
                        <label>Birthday</label>
                        <span>{{ $appCandidate->birthday?->format('M d, Y') ?: '—' }}</span>
                    </div>
                    <div class="detail-item">
                        <label>Gender</label>
                        <span>{{ $appCandidate->gender ? ucfirst($appCandidate->gender) : '—' }}</span>
                    </div>
                    <div class="detail-item">
                        <label>Nationality</label>
                        <span>{{ $appCandidate->nationality ?: '—' }}</span>
                    </div>
                    <div class="detail-item">
                        <label>Marital Status</label>
                        <span>{{ $appCandidate->marital_status ? ucfirst($appCandidate->marital_status) : '—' }}</span>
                    </div>
                </div>
            </div>

            {{-- Contact --}}
            <div class="detail-card">
                <h3>Contact Information</h3>
                <div class="detail-grid">
                    <div class="detail-item">
                        <label>Alternate Phone</label>
                        <span>{{ $appCandidate->alternate_phone ?: '—' }}</span>
                    </div>
                    <div class="detail-item">
                        <label>Skype</label>
                        <span>{{ $appCandidate->skype ?: '—' }}</span>
                    </div>
                    <div class="detail-item">
                        <label>LinkedIn</label>
                        <span>{{ $appCandidate->linkedin_url ?: '—' }}</span>
                    </div>
                    <div class="detail-item">
                        <label>Facebook</label>
                        <span>{{ $appCandidate->facebook ?: '—' }}</span>
                    </div>
                    @if($appCandidate->resident)
                    <div class="detail-item" style="grid-column: 1 / -1;">
                        <label>Residence</label>
                        <span>{{ $appCandidate->resident }}</span>
                    </div>
                    @endif
                </div>
            </div>

            {{-- Skills --}}
            @if($appCandidate->skills && $appCandidate->skills->count())
            <div class="detail-card">
                <h3>Skills</h3>
                <div style="display: flex; flex-wrap: wrap; gap: .25rem;">
                    @foreach($appCandidate->skills as $skill)
                        <span class="skill-pill-sm">
                            {{ $skill->name }}
                            @if($skill->pivot->proficiency)
                                <span style="opacity:.6; font-size:.68rem;">(Lvl {{ $skill->pivot->proficiency }})</span>
                            @endif
                        </span>
                    @endforeach
                </div>
            </div>
            @endif

            {{-- Work History --}}
            @if($appCandidate->seniorities && $appCandidate->seniorities->count())
            <div class="detail-card">
                <h3>Work History</h3>
                @foreach($appCandidate->seniorities->sortBy('sort_order') as $job)
                <div class="history-row">
                    <div style="display: flex; justify-content: space-between; align-items: baseline; flex-wrap: wrap;">
                        <strong style="font-size: .88rem; color: var(--navy);">{{ $job->position ?: 'Position' }} at {{ $job->company ?: 'Company' }}</strong>
                        <span style="font-size: .78rem; color: var(--muted);">
                            {{ $job->from_date?->format('M Y') ?? '?' }} — {{ $job->to_date?->format('M Y') ?? 'Present' }}
                        </span>
                    </div>
                    @if($job->job_description)
                        <p style="font-size: .83rem; color: var(--text); margin-top: .3rem;">{{ $job->job_description }}</p>
                    @endif
                </div>
                @endforeach
            </div>
            @endif

            {{-- Education --}}
            @if($appCandidate->literacies && $appCandidate->literacies->count())
            <div class="detail-card">
                <h3>Education</h3>
                @foreach($appCandidate->literacies->sortBy('sort_order') as $edu)
                <div class="history-row">
                    <div style="display: flex; justify-content: space-between; align-items: baseline; flex-wrap: wrap;">
                        <strong style="font-size: .88rem; color: var(--navy);">{{ $edu->diploma ?: 'Diploma' }}</strong>
                        <span style="font-size: .78rem; color: var(--muted);">
                            {{ $edu->from_date?->format('M Y') ?? '?' }} — {{ $edu->to_date?->format('M Y') ?? 'Present' }}
                        </span>
                    </div>
                    @if($edu->training_places)
                        <p style="font-size: .83rem; color: var(--text); margin-top: .15rem;">{{ $edu->training_places }}</p>
                    @endif
                    @if($edu->specialized)
                        <p style="font-size: .78rem; color: var(--muted); margin-top: .1rem;">Specialization: {{ $edu->specialized }}</p>
                    @endif
                </div>
                @endforeach
            </div>
            @endif

            {{-- References --}}
            @if($appCandidate->references && $appCandidate->references->count())
            <div class="detail-card">
                <h3>References</h3>
                <div class="detail-grid">
                    @foreach($appCandidate->references->sortBy('sort_order') as $ref)
                    <div style="background: #f8fafc; border: 1px solid var(--border); border-radius: 8px; padding: .85rem;">
                        <strong style="font-size: .85rem; color: var(--navy);">{{ $ref->name ?: '—' }}</strong>
                        @if($ref->relationship)
                            <span style="font-size: .75rem; color: var(--muted); margin-left: .3rem;">({{ $ref->relationship }})</span>
                        @endif
                        @if($ref->job)
                            <p style="font-size: .8rem; color: var(--text); margin-top: .15rem;">{{ $ref->job }}</p>
                        @endif
                        @if($ref->phone)
                            <p style="font-size: .78rem; color: var(--muted); margin-top: .1rem;">{{ $ref->phone }}</p>
                        @endif
                    </div>
                    @endforeach
                </div>
            </div>
            @endif
        </div>

        {{-- TAB: Position Details --}}
        <div class="app-tab-panel" :class="{ active: tab === 'position' }">
            <div class="detail-card">
                <h3>Job Position</h3>
                <div class="detail-grid">
                    <div class="detail-item">
                        <label>Position</label>
                        <span>{{ $position->name ?? $position->title ?? '—' }}</span>
                    </div>
                    <div class="detail-item">
                        <label>Department</label>
                        <span>{{ $dept->name ?? '—' }}</span>
                    </div>
                    <div class="detail-item">
                        <label>Employment Type</label>
                        <span>{{ ucfirst(str_replace('_', ' ', $campaign->employment_type ?? '—')) }}</span>
                    </div>
                    <div class="detail-item">
                        <label>Location</label>
                        <span>{{ $campaign->location ?: '—' }}</span>
                    </div>
                    <div class="detail-item">
                        <label>Openings</label>
                        <span>{{ $campaign->vacancies_needed ?? '—' }}</span>
                    </div>
                    @if($campaign->display_salary && ($campaign->salary_min || $campaign->salary_max))
                    <div class="detail-item">
                        <label>Salary Range</label>
                        <span style="color: var(--teal2); font-weight: 600;">
                            @if($campaign->salary_min && $campaign->salary_max)
                                {{ $campaign->currency }} {{ number_format($campaign->salary_min) }} – {{ number_format($campaign->salary_max) }}
                            @elseif($campaign->salary_min)
                                From {{ $campaign->currency }} {{ number_format($campaign->salary_min) }}
                            @else
                                Up to {{ $campaign->currency }} {{ number_format($campaign->salary_max) }}
                            @endif
                        </span>
                    </div>
                    @endif
                </div>
            </div>

            @if($campaign->description)
            <div class="detail-card">
                <h3>About This Role</h3>
                <div style="font-size: .88rem; line-height: 1.75; color: var(--text);">{!! $campaign->description !!}</div>
            </div>
            @endif

            @if($campaign->requirements)
            <div class="detail-card">
                <h3>Requirements</h3>
                <div style="font-size: .88rem; line-height: 1.75; color: var(--text);">{!! $campaign->requirements !!}</div>
            </div>
            @endif

            @if($campaign->skills && $campaign->skills->count())
            <div class="detail-card">
                <h3>Required Skills</h3>
                <div style="display: flex; flex-wrap: wrap; gap: .25rem;">
                    @foreach($campaign->skills as $skill)
                        <span class="skill-pill-sm" style="{{ $skill->pivot->is_required ? 'background: #fff0e8; color: #c2440e; border-color: #f8b49c;' : '' }}">
                            {{ $skill->name }}
                            @if($skill->pivot->is_required)
                                <span style="font-size:.65rem;">(Required)</span>
                            @endif
                        </span>
                    @endforeach
                </div>
            </div>
            @endif
        </div>
    </div>

</div>
@endsection
