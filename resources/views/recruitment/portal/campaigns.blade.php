@extends('recruitment.layouts.portal')

@section('title', 'Open Positions')
@section('description', 'Browse our current job openings and find your next career opportunity.')

@section('content')

{{-- Search & Filter Bar --}}
<div style="background: #fff; padding: 1.5rem; border-radius: 12px; border: 1px solid var(--border); box-shadow: 0 4px 12px rgba(0,0,0,0.03); margin-bottom: 2.5rem;">
    <form action="{{ route('candidate.campaigns') }}" method="GET" style="display: flex; gap: 1rem; flex-wrap: wrap; align-items: flex-end;">
        
        {{-- Search Input --}}
        <div style="flex: 1; min-width: 250px;">
            <label for="search" style="display: block; font-size: .85rem; font-weight: 600; color: var(--navy); margin-bottom: .4rem;">Keywords</label>
            <div style="position: relative;">
                <svg style="position: absolute; left: 12px; top: 50%; transform: translateY(-50%); width: 18px; height: 18px; color: var(--muted);" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-5.197-5.197m0 0A7.5 7.5 0 105.196 5.196a7.5 7.5 0 0010.607 10.607z" />
                </svg>
                <input type="text" name="search" id="search" value="{{ request('search') }}" placeholder="Search jobs, keywords..."
                       style="width: 100%; padding: .65rem 1rem .65rem 2.5rem; border: 1px solid var(--border); border-radius: 8px; font-size: .95rem; outline: none; transition: border-color .2s;">
            </div>
        </div>

        {{-- Department (Category) --}}
        <div style="flex: 1; min-width: 200px;">
            <label for="category" style="display: block; font-size: .85rem; font-weight: 600; color: var(--navy); margin-bottom: .4rem;">Category</label>
            <select name="category" id="category" style="width: 100%; padding: .65rem 1rem; border: 1px solid var(--border); border-radius: 8px; font-size: .95rem; outline: none; transition: border-color .2s; background: #fff;">
                <option value="">All Categories</option>
                @foreach($departments as $dept)
                    <option value="{{ $dept->id }}" {{ request('category') == $dept->id ? 'selected' : '' }}>
                        {{ $dept->name }}
                    </option>
                @endforeach
            </select>
        </div>

        {{-- Employment Type --}}
        <div style="flex: 1; min-width: 200px;">
            <label for="type" style="display: block; font-size: .85rem; font-weight: 600; color: var(--navy); margin-bottom: .4rem;">Employment Type</label>
            <select name="type" id="type" style="width: 100%; padding: .65rem 1rem; border: 1px solid var(--border); border-radius: 8px; font-size: .95rem; outline: none; transition: border-color .2s; background: #fff;">
                <option value="">All Types</option>
                @foreach($employmentTypes as $type)
                    <option value="{{ $type }}" {{ request('type') == $type ? 'selected' : '' }}>
                        {{ ucfirst(str_replace('_', ' ', $type)) }}
                    </option>
                @endforeach
            </select>
        </div>

        {{-- Submit Button --}}
        <div>
            <button type="submit" style="padding: .65rem 1.5rem; background: var(--teal); color: #fff; border: none; border-radius: 8px; font-size: .95rem; font-weight: 600; cursor: pointer; transition: background .2s; height: calc(1.3rem + 1.6rem + 2px);">
                Search
            </button>
        </div>
        
    </form>
</div>

{{-- Hero Text --}}
<div style="margin-bottom: 2rem;">
    <h1 style="font-size: 1.8rem; font-weight: 800; color: var(--navy); margin-bottom: .35rem;">Open Positions</h1>
    <p style="color: var(--muted); font-size: .95rem;">Find the role that&rsquo;s right for you. Browse our current openings and apply today.</p>
</div>

@if($campaigns->isEmpty())
    <div style="text-align: center; padding: 4rem 2rem; background: #fff; border-radius: 16px; border: 1px solid var(--border);">
        <svg style="margin: 0 auto 1rem; width: 56px; height: 56px; color: #cbd5e1;" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" d="M20.25 14.15v4.25c0 1.094-.787 2.036-1.872 2.18-2.087.277-4.216.42-6.378.42s-4.291-.143-6.378-.42c-1.085-.144-1.872-1.086-1.872-2.18v-4.25m16.5 0a2.18 2.18 0 00.75-1.661V8.706c0-1.081-.768-2.015-1.837-2.175a48.114 48.114 0 00-3.413-.387m4.5 8.006c-.194.165-.42.295-.673.38A23.978 23.978 0 0112 15.75c-2.648 0-5.195-.429-7.577-1.22a2.016 2.016 0 01-.673-.38m0 0A2.18 2.18 0 013 12.489V8.706c0-1.081.768-2.015 1.837-2.175a48.111 48.111 0 013.413-.387m7.5 0V5.25A2.25 2.25 0 0013.5 3h-3a2.25 2.25 0 00-2.25 2.25v.894m7.5 0a48.667 48.667 0 00-7.5 0" />
        </svg>
        <h2 style="font-size: 1.15rem; font-weight: 700; color: var(--navy); margin-bottom: .35rem;">No Open Positions</h2>
        <p style="color: var(--muted); font-size: .9rem;">We don&rsquo;t have any openings right now. Please check back later!</p>
    </div>
@else
    <div style="display: grid; grid-template-columns: repeat(auto-fill, minmax(360px, 1fr)); gap: 1.5rem;">
        @foreach($campaigns as $campaign)
            <article style="
                background: #fff;
                border: 1px solid var(--border);
                border-radius: 14px;
                overflow: hidden;
                transition: box-shadow .25s, transform .25s;
                display: flex;
                flex-direction: column;
                cursor: pointer;
            "
            onmouseover="this.style.boxShadow='0 12px 32px rgba(0,51,102,.12)'; this.style.transform='translateY(-3px)';"
            onmouseout="this.style.boxShadow='none'; this.style.transform='none';"
            >
                {{-- Card Header --}}
                <div style="background: var(--navy); padding: 1.25rem 1.5rem;">
                    <div style="display: flex; align-items: center; gap: .5rem; margin-bottom: .4rem;">
                        <span class="badge badge-teal">{{ ucfirst(str_replace('_', ' ', $campaign->employment_type)) }}</span>
                        @if($campaign->end_date && $campaign->end_date->isFuture())
                            <span class="badge badge-navy" style="background: rgba(255,255,255,.15); color: #fff;">
                                {{ $campaign->end_date->diffForHumans() }}
                            </span>
                        @endif
                    </div>
                    <h2 style="color: #fff; font-size: 1.1rem; font-weight: 700; line-height: 1.35; margin: 0;">
                        {{ $campaign->title }}
                    </h2>
                </div>

                {{-- Card Body --}}
                <div style="padding: 1.25rem 1.5rem; flex: 1; display: flex; flex-direction: column; gap: .75rem;">
                    {{-- Meta row --}}
                    <div style="display: flex; flex-wrap: wrap; gap: .75rem;">
                        @if($campaign->jobPosition)
                            <div style="display: flex; align-items: center; gap: .3rem; font-size: .82rem; color: var(--muted);">
                                <svg style="width: 14px; height: 14px; flex-shrink: 0;" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M20.25 14.15v4.25c0 1.094-.787 2.036-1.872 2.18-2.087.277-4.216.42-6.378.42s-4.291-.143-6.378-.42c-1.085-.144-1.872-1.086-1.872-2.18v-4.25m16.5 0a2.18 2.18 0 00.75-1.661V8.706c0-1.081-.768-2.015-1.837-2.175a48.114 48.114 0 00-3.413-.387m4.5 8.006c-.194.165-.42.295-.673.38A23.978 23.978 0 0112 15.75c-2.648 0-5.195-.429-7.577-1.22a2.016 2.016 0 01-.673-.38m0 0A2.18 2.18 0 013 12.489V8.706c0-1.081.768-2.015 1.837-2.175a48.111 48.111 0 013.413-.387m7.5 0V5.25A2.25 2.25 0 0013.5 3h-3a2.25 2.25 0 00-2.25 2.25v.894m7.5 0a48.667 48.667 0 00-7.5 0"/></svg>
                                <span>{{ $campaign->jobPosition->title }}</span>
                            </div>
                        @endif

                        @if($campaign->location)
                            <div style="display: flex; align-items: center; gap: .3rem; font-size: .82rem; color: var(--muted);">
                                <svg style="width: 14px; height: 14px; flex-shrink: 0;" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M15 10.5a3 3 0 11-6 0 3 3 0 016 0z"/><path stroke-linecap="round" stroke-linejoin="round" d="M19.5 10.5c0 7.142-7.5 11.25-7.5 11.25S4.5 17.642 4.5 10.5a7.5 7.5 0 0115 0z"/></svg>
                                <span>{{ $campaign->location }}</span>
                            </div>
                        @endif

                        @if($campaign->vacancies_needed > 1)
                            <div style="display: flex; align-items: center; gap: .3rem; font-size: .82rem; color: var(--muted);">
                                <svg style="width: 14px; height: 14px; flex-shrink: 0;" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M15 19.128a9.38 9.38 0 002.625.372 9.337 9.337 0 004.121-.952 4.125 4.125 0 00-7.533-2.493M15 19.128v-.003c0-1.113-.285-2.16-.786-3.07M15 19.128v.106A12.318 12.318 0 018.624 21c-2.331 0-4.512-.645-6.374-1.766l-.001-.109a6.375 6.375 0 0111.964-3.07M12 6.375a3.375 3.375 0 11-6.75 0 3.375 3.375 0 016.75 0zm8.25 2.25a2.625 2.625 0 11-5.25 0 2.625 2.625 0 015.25 0z"/></svg>
                                <span>{{ $campaign->vacancies_needed }} openings</span>
                            </div>
                        @endif
                    </div>

                    {{-- Salary --}}
                    @if($campaign->display_salary && ($campaign->salary_min || $campaign->salary_max))
                        <div style="font-size: .88rem; font-weight: 600; color: var(--teal2);">
                            @if($campaign->salary_min && $campaign->salary_max)
                                {{ $campaign->currency }} {{ number_format($campaign->salary_min) }} – {{ number_format($campaign->salary_max) }}
                            @elseif($campaign->salary_min)
                                From {{ $campaign->currency }} {{ number_format($campaign->salary_min) }}
                            @else
                                Up to {{ $campaign->currency }} {{ number_format($campaign->salary_max) }}
                            @endif
                        </div>
                    @else
                        <div style="font-size: .88rem; font-weight: 600; color: var(--teal2);">
                            As per the salary Scale of the Company
                        </div>
                    @endif

                    {{-- Description snippet --}}
                    @if($campaign->description)
                        <p style="font-size: .85rem; color: #4b5563; line-height: 1.6; flex: 1;">
                            {{ \Illuminate\Support\Str::limit(strip_tags($campaign->description), 140) }}
                        </p>
                    @endif
                </div>

                {{-- Card Footer --}}
                <div style="padding: .85rem 1.5rem; border-top: 1px solid var(--border); display: flex; justify-content: space-between; align-items: center;">
                    @if($campaign->end_date)
                        <span style="font-size: .78rem; color: var(--muted);">
                            Deadline: <strong style="color: {{ $campaign->end_date->isPast() ? 'var(--danger)' : 'var(--text)' }};">{{ $campaign->end_date->format('M d, Y') }}</strong>
                        </span>
                    @else
                        <span style="font-size: .78rem; color: var(--muted);">Open until filled</span>
                    @endif

                    <a href="{{ route('candidate.campaigns.show', $campaign) }}"
                       style="display: inline-flex; align-items: center; gap: .3rem;
                              padding: .45rem 1rem; border-radius: 8px; border: none;
                              font-size: .82rem; font-weight: 600; cursor: pointer;
                              text-decoration: none; transition: all .15s;
                              background: var(--teal); color: #fff;"
                       onmouseover="this.style.background='var(--teal2)'"
                       onmouseout="this.style.background='var(--teal)'"
                    >
                        View Details
                        <svg style="width: 14px; height: 14px;" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M13.5 4.5L21 12m0 0l-7.5 7.5M21 12H3"/></svg>
                    </a>
                </div>
            </article>
        @endforeach
    </div>

    {{-- Pagination --}}
    @if($campaigns->hasPages())
        <div class="rp-pagination">
            {{ $campaigns->links('vendor.pagination.simple-default') }}
        </div>
    @endif
@endif
@endsection
