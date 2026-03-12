@extends('supplier.layouts.portal')
@section('title', 'Submit Bid — ' . $tender->tender_number)
@section('content')
<div class="sp-page">

    <div class="sp-breadcrumb">
        <a href="{{ route('supplier.tenders') }}">Open Tenders</a>
        <span>/</span>
        <a href="{{ route('supplier.tenders.show', $tender) }}">{{ $tender->tender_number }}</a>
        <span>/</span>
        <span>Submit Bid</span>
    </div>

    <div style="display:grid;grid-template-columns:2fr 1fr;gap:1.5rem;align-items:start;">

        <div>
            <div class="sp-card-header" style="margin-bottom:1.25rem;">
                <div>
                    <h1 style="font-size:1.35rem;font-weight:700;color:var(--navy);">Submit Your Bid</h1>
                    <p style="color:var(--muted);font-size:.875rem;margin-top:.2rem;">{{ $tender->title }}</p>
                </div>
            </div>

            @if($errors->any())
            <div class="sp-alert sp-alert-error">
                <strong>Please fix the following:</strong><br>
                @foreach($errors->all() as $e)• {{ $e }}<br>@endforeach
            </div>
            @endif

            <form method="POST" action="{{ route('supplier.bids.store', $tender) }}" enctype="multipart/form-data">
                @csrf

                <div class="sp-card">
                    <div class="sp-card-title" style="margin-bottom:1.25rem;">Bid Details</div>
                    <div class="sp-grid-2">
                        <div class="sp-form-group">
                            <label>Bid Amount <span style="color:var(--danger)">*</span></label>
                            <input name="bid_amount" type="number" step="0.01" min="0" class="sp-input {{ $errors->has('bid_amount')?'error':'' }}" value="{{ old('bid_amount') }}" placeholder="0.00" required>
                            @error('bid_amount')<div class="sp-error-msg">{{ $message }}</div>@enderror
                        </div>
                        <div class="sp-form-group">
                            <label>Currency <span style="color:var(--danger)">*</span></label>
                            <select name="currency" class="sp-select" required>
                                @foreach(['ETB','USD','EUR','GBP'] as $c)
                                    <option value="{{ $c }}" {{ old('currency',$tender->currency)==$c?'selected':'' }}>{{ $c }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="sp-form-group">
                            <label>Delivery Period (days) <span style="color:var(--danger)">*</span></label>
                            <input name="delivery_days" type="number" min="1" class="sp-input {{ $errors->has('delivery_days')?'error':'' }}" value="{{ old('delivery_days') }}" placeholder="e.g., 30" required>
                        </div>
                        <div class="sp-form-group">
                            <label>Bid Valid Until <span style="color:var(--danger)">*</span></label>
                            <input name="validity_date" type="date" class="sp-input {{ $errors->has('validity_date')?'error':'' }}" value="{{ old('validity_date') }}" required>
                        </div>
                        <div class="sp-form-group">
                            <label>Your Reference #</label>
                            <input name="reference_number" type="text" class="sp-input" value="{{ old('reference_number') }}" placeholder="Internal ref. number">
                        </div>
                        <div class="sp-form-group">
                            <label>Bid Security / Bond Type</label>
                            <select name="bid_security" class="sp-select">
                                <option value="">— Select type —</option>
                                @foreach(\App\Models\Procurement\ProcurementBidSecurity::where('is_active', true)->pluck('name') as $type)
                                    <option value="{{ $type }}" {{ old('bid_security') == $type ? 'selected' : '' }}>{{ $type }}</option>
                                @endforeach
                            </select>
                            <div class="sp-hint">Select the type of bid security you will provide.</div>
                        </div>
                    </div>

                    <div class="sp-form-group">
                        <label>General Technical Proposal / Notes</label>
                        <textarea name="notes" class="sp-textarea" rows="4" placeholder="Any additional information, approach, or context for your bid…">{{ old('notes') }}</textarea>
                    </div>

                    <div class="sp-form-group">
                        <div class="sp-checkbox-group">
                            <input type="hidden" name="conflict_of_interest_declared" value="0">
                            <input type="checkbox" name="conflict_of_interest_declared" id="coi" value="1" {{ old('conflict_of_interest_declared') ? 'checked' : '' }}>
                            <label for="coi">I declare a <strong>conflict of interest</strong> with any member of the evaluation committee.</label>
                        </div>
                        <div class="sp-hint">Only check this if applicable. False declarations may disqualify your bid.</div>
                    </div>
                </div>

                {{-- ── Evaluation Criteria Responses ── --}}
                @if($tender->evaluationCriteria->isNotEmpty())
                <div class="sp-card">
                    <div class="sp-card-header" style="margin-bottom:1rem;">
                        <div>
                            <div class="sp-card-title">Criterion Responses</div>
                            <div class="sp-card-sub">For each evaluation criterion, describe how your bid meets the requirement. These will be used by the evaluators when scoring your submission.</div>
                        </div>
                    </div>

                    @foreach($tender->evaluationCriteria->sortBy('sort_order') as $criterion)
                    <div style="border:1px solid var(--border);border-radius:10px;padding:1.25rem;margin-bottom:1rem;background:#fafcff;">
                        <div style="display:flex;justify-content:space-between;align-items:flex-start;margin-bottom:.75rem;gap:1rem;">
                            <div>
                                <div style="font-weight:700;font-size:.9rem;color:var(--navy);">{{ $criterion->name }}</div>
                                @if($criterion->description)
                                <div style="font-size:.8rem;color:var(--muted);margin-top:.2rem;">{{ $criterion->description }}</div>
                                @endif
                            </div>
                            <span class="badge badge-cyan" style="white-space:nowrap;flex-shrink:0;">{{ $criterion->weight }}% weight</span>
                        </div>
                        <div class="sp-form-group" style="margin-bottom:0;">
                            <label style="font-size:.72rem;">Your Response for "{{ $criterion->name }}" <span style="color:var(--danger)">*</span></label>
                            <textarea
                                name="criterion_responses[{{ $criterion->id }}]"
                                class="sp-textarea {{ $errors->has('criterion_responses.'.$criterion->id) ? 'error' : '' }}"
                                rows="4"
                                placeholder="Explain how your company meets or exceeds this criterion. Include relevant experience, certifications, references, or technical specifications…"
                                required>{{ old('criterion_responses.'.$criterion->id) }}</textarea>
                            @error('criterion_responses.'.$criterion->id)
                                <div class="sp-error-msg">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>
                    @endforeach
                </div>
                @endif

                <div class="sp-card">
                    <div class="sp-card-title" style="margin-bottom:.5rem;">Bid Documents <span style="color:var(--danger)">*</span></div>
                    <div class="sp-card-sub" style="margin-bottom:1rem;">Upload your technical and financial proposals. Accepted: PDF, Word, Excel, ZIP (max 10MB each).</div>
                    <input name="attachments[]" type="file" multiple accept=".pdf,.doc,.docx,.xls,.xlsx,.zip,.png,.jpg"
                        class="sp-input {{ $errors->has('attachments')?'error':'' }}" style="padding:.5rem;" required>
                    @error('attachments')<div class="sp-error-msg">{{ $message }}</div>@enderror
                </div>

                <div style="display:flex;justify-content:space-between;align-items:center;margin-top:1.5rem;">
                    <a href="{{ route('supplier.tenders.show', $tender) }}" class="sp-btn sp-btn-outline">← Cancel</a>
                    <button type="submit" class="sp-btn sp-btn-navy sp-btn-lg">Submit Bid →</button>
                </div>
            </form>
        </div>


        {{-- Sidebar summary --}}
        <div>
            <div class="sp-card" style="position:sticky;top:80px;">
                <div class="sp-card-title" style="margin-bottom:1rem;">Tender Summary</div>
                <div style="font-size:.78rem;color:var(--cyan2);font-weight:700;margin-bottom:.25rem;">{{ $tender->tender_number }}</div>
                <div style="font-weight:600;margin-bottom:1rem;line-height:1.35;">{{ $tender->title }}</div>
                <hr class="sp-divider" style="margin:.75rem 0;">
                @foreach([
                    ['Method', $tender->method],
                    ['Est. Value', $tender->currency.' '.number_format($tender->estimated_value,0)],
                    ['Deadline', \Carbon\Carbon::parse($tender->submission_deadline)->format('d M Y')],
                ] as [$l, $v])
                <div style="display:flex;justify-content:space-between;padding:.45rem 0;font-size:.82rem;border-bottom:1px solid var(--border);">
                    <span style="color:var(--muted);">{{ $l }}</span>
                    <strong>{{ $v }}</strong>
                </div>
                @endforeach

                @if($tender->evaluationCriteria->isNotEmpty())
                <div style="margin-top:1rem;">
                    <div style="font-size:.72rem;font-weight:700;letter-spacing:.06em;text-transform:uppercase;color:var(--muted);margin-bottom:.6rem;">Evaluation Criteria</div>
                    @foreach($tender->evaluationCriteria as $c)
                    <div style="display:flex;justify-content:space-between;font-size:.8rem;padding:.3rem 0;">
                        <span>{{ $c->name }}</span>
                        <span class="badge badge-cyan">{{ $c->weight }}%</span>
                    </div>
                    @endforeach
                </div>
                @endif
            </div>
        </div>

    </div>
</div>
@endsection
