@extends('recruitment.layouts.portal')

@section('title', 'My Applications')

@section('content')
<div style="margin-bottom: 2rem;">
    <a href="{{ route('candidate.campaigns') }}" style="color: var(--teal); text-decoration: none; font-size: .9rem;">&larr; Back to Positions</a>
    <h1 style="font-size: 1.8rem; font-weight: 800; color: var(--navy); margin-top: .5rem; margin-bottom: .35rem;">My Applications</h1>
</div>

<div x-data="{ 
    activeTab: 'active',
    apps: {{ $applications->toJson() }},
    get filteredApps() {
        if (this.activeTab === 'active') {
            return this.apps.filter(a => ['applied', 'under_review', 'shortlisted', 'interview_scheduled', 'offer_pending'].includes(a.status));
        }
        if (this.activeTab === 'hired') {
            return this.apps.filter(a => ['hired', 'offer_accepted'].includes(a.status));
        }
        if (this.activeTab === 'rejected') {
            return this.apps.filter(a => ['rejected', 'offer_declined'].includes(a.status));
        }
        if (this.activeTab === 'withdrawn') {
            return this.apps.filter(a => a.status === 'withdrawn');
        }
        return this.apps;
    }
}">
    <div style="display: flex; gap: 0.5rem; background: #f1f5f9; padding: 0.4rem; border-radius: 12px; margin-bottom: 2rem; width: fit-content;">
        <button @click="activeTab = 'active'" 
                :class="activeTab === 'active' ? 'rp-tab-active' : 'rp-tab-inactive'"
                style="border: none; padding: .6rem 1.25rem; border-radius: 8px; cursor: pointer; font-size: .9rem; font-weight: 600; transition: all .2s;">
            Active
        </button>
        <button @click="activeTab = 'hired'" 
                :class="activeTab === 'hired' ? 'rp-tab-active' : 'rp-tab-inactive'"
                style="border: none; padding: .6rem 1.25rem; border-radius: 8px; cursor: pointer; font-size: .9rem; font-weight: 600; transition: all .2s;">
            Hired
        </button>
        <button @click="activeTab = 'rejected'" 
                :class="activeTab === 'rejected' ? 'rp-tab-active' : 'rp-tab-inactive'"
                style="border: none; padding: .6rem 1.25rem; border-radius: 8px; cursor: pointer; font-size: .9rem; font-weight: 600; transition: all .2s;">
            Rejected
        </button>
        <button @click="activeTab = 'withdrawn'" 
                :class="activeTab === 'withdrawn' ? 'rp-tab-active' : 'rp-tab-inactive'"
                style="border: none; padding: .6rem 1.25rem; border-radius: 8px; cursor: pointer; font-size: .9rem; font-weight: 600; transition: all .2s;">
            Withdrawn
        </button>
    </div>

    <style>
        .rp-tab-active { background: #fff; color: var(--teal2); boxShadow: 0 1px 3px rgba(0,0,0,0.1); }
        .rp-tab-inactive { background: transparent; color: #64748b; }
        .rp-tab-inactive:hover { color: var(--navy); }
    </style>

    <template x-if="filteredApps.length === 0">
        <div style="text-align: center; padding: 4rem 2rem; background: #fff; border-radius: 16px; border: 1px solid var(--border);">
            <p style="color: var(--muted); font-size: .9rem;">No applications found in this category.</p>
        </div>
    </template>

    <div style="display: flex; flex-direction: column; gap: 1rem;">
        <template x-for="app in filteredApps" :key="app.id">
            <a :href="'{{ route('candidate.my-applications') }}/' + app.id"
               style="background: #fff; border: 1px solid var(--border); border-radius: 12px; padding: 1.5rem; display: flex; justify-content: space-between; align-items: center; text-decoration: none; transition: border-color .15s, box-shadow .15s;"
               onmouseover="this.style.borderColor='var(--teal)'; this.style.boxShadow='0 2px 8px rgba(13,148,136,.1)'"
               onmouseout="this.style.borderColor='var(--border)'; this.style.boxShadow='none'">
                <div>
                    <h3 style="color: var(--navy); margin: 0 0 .25rem 0;" x-text="app.campaign.title"></h3>
                    <p style="color: var(--muted); margin: 0; font-size: .85rem;">Applied on <span x-text="new Date(app.created_at).toLocaleDateString('en-US', { month: 'short', day: 'numeric', year: 'numeric' })"></span></p>
                </div>
                <div style="display: flex; align-items: center; gap: .75rem;">
                    <span style="background: #f1f5f9; color: #475569; padding: .35rem .75rem; border-radius: 99px; font-size: .8rem; font-weight: 600; text-transform: capitalize;"
                          x-text="app.status.replace('_', ' ')"></span>
                    <svg style="width: 16px; height: 16px; color: var(--muted);" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M8.25 4.5l7.5 7.5-7.5 7.5"/></svg>
                </div>
            </a>
        </template>
    </div>
</div>
@endsection
