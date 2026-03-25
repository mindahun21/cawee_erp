@php
    /** @var \App\Models\Recruitment\RecruitmentCampaign $campaign */
    /** @var \App\Models\Recruitment\RecruitmentCandidate $candidate */
    use Illuminate\Support\Str;

    $channel     = $campaign->channel;
    $schema      = $channel?->form_schema ?? [];
    $submitLabel = $channel?->submit_button_text ?: 'Submit Application';
@endphp
@extends('recruitment.layouts.portal')

@section('title', 'Apply – ' . $campaign->title)

@push('styles')
<style>
    .apply-form-label {
        display: block; font-size: .83rem; font-weight: 600; color: var(--navy);
        margin-bottom: .35rem; letter-spacing: .01em;
    }
    .apply-form-input {
        width: 100%;
        padding: .6rem .9rem;
        border: 1.5px solid var(--border);
        border-radius: 8px;
        font-size: .88rem;
        color: var(--text);
        background: #fff;
        transition: border-color .15s, box-shadow .15s;
        outline: none;
        font-family: inherit;
    }
    .apply-form-input:focus {
        border-color: var(--teal);
        box-shadow: 0 0 0 3px rgba(13, 148, 136, .15);
    }
    .apply-form-input.has-error { border-color: var(--danger); }
    .apply-form-group { margin-bottom: 1.2rem; }
    .apply-required-star { color: var(--danger); margin-left: 2px; }
    .apply-help-text { font-size: .77rem; color: var(--muted); margin-top: .3rem; }
    .apply-form-section-title {
        font-size: 1.1rem; font-weight: 800; color: var(--navy);
        margin: 1.75rem 0 .75rem; padding-bottom: .5rem;
        border-bottom: 2px solid var(--border);
    }

    /* Skills multi-select */
    .skill-multi-select { position: relative; }
    .skill-multi-selected-pills {
        display: flex; flex-wrap: wrap; gap: .35rem;
        min-height: 42px; padding: .5rem .75rem;
        border: 1.5px solid var(--border); border-radius: 8px;
        background: #fff; cursor: pointer; transition: border-color .15s;
        align-items: center;
    }
    .skill-multi-selected-pills:focus-within { border-color: var(--teal); box-shadow: 0 0 0 3px rgba(13, 148, 136, .15); }
    .skill-multi-selected-pills .pill {
        display: inline-flex; align-items: center; gap: .25rem;
        background: #e8f7f5; color: var(--teal2); border: 1px solid #99e6dc;
        padding: .2rem .6rem; border-radius: 16px; font-size: .78rem; font-weight: 600;
    }
    .skill-multi-selected-pills .pill button {
        background: none; border: none; color: var(--teal2); cursor: pointer;
        font-size: .9rem; line-height: 1; padding: 0 0 0 .15rem;
    }
    .skill-multi-selected-pills .placeholder { color: var(--muted); font-size: .85rem; }
    .skill-multi-dropdown {
        position: absolute; top: 100%; left: 0; right: 0; z-index: 50;
        background: #fff; border: 1.5px solid var(--border); border-radius: 8px;
        max-height: 220px; overflow-y: auto; margin-top: 4px;
        box-shadow: 0 8px 24px rgba(0,0,0,.1);
    }
    .skill-multi-dropdown label {
        display: flex; align-items: center; gap: .5rem;
        padding: .55rem .85rem; font-size: .85rem; color: var(--text);
        cursor: pointer; transition: background .1s;
    }
    .skill-multi-dropdown label:hover { background: #f0fdfa; }
    .skill-multi-dropdown label input[type='checkbox'] { accent-color: var(--teal); width: 15px; height: 15px; }
</style>
@endpush

@section('content')
<div style="max-width: 1100px; width: 100%; margin: 0 auto;">

    {{-- Back link --}}
    <div style="margin-bottom: 1.5rem;">
        <a href="{{ route('candidate.campaigns.show', $campaign) }}"
           style="display: inline-flex; align-items: center; gap: .3rem; font-size: .85rem; color: var(--teal2); text-decoration: none; font-weight: 500;">
            <svg style="width: 14px; height: 14px;" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M10.5 19.5L3 12m0 0l7.5-7.5M3 12h18"/></svg>
            Back to position
        </a>
    </div>

    {{-- Header --}}
    <div style="background: linear-gradient(135deg, var(--navy), #004d99); border-radius: 14px; padding: 2rem; color: #fff; margin-bottom: 2rem;">
        <p style="font-size: .8rem; color: rgba(255,255,255,.6); margin-bottom: .3rem; text-transform: uppercase; letter-spacing: .06em;">Applying for</p>
        <h1 style="font-size: 1.5rem; font-weight: 800; margin: 0 0 .25rem;">{{ $campaign->title }}</h1>
        @if($campaign->jobPosition)
            <p style="color: rgba(255,255,255,.65); font-size: .88rem; margin: 0;">{{ $campaign->jobPosition->title }}</p>
        @endif
    </div>

    {{-- Validation errors --}}
    @if($errors->any())
        <div style="background: #fef2f2; border: 1px solid #fca5a5; border-radius: 10px; padding: 1rem 1.25rem; margin-bottom: 1.5rem;">
            <p style="font-weight: 700; color: var(--danger); font-size: .88rem; margin-bottom: .5rem;">Please fix the following errors:</p>
            <ul style="padding-left: 1.2rem; font-size: .83rem; color: #b91c1c;">
                @foreach($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    {{-- APPLICATION FORM --}}
    <form method="POST"
          action="{{ route('candidate.campaigns.apply.store', $campaign) }}"
          enctype="multipart/form-data"
          style="background: #fff; border: 1px solid var(--border); border-radius: 14px; padding: 2rem; width: 100%;">
        @csrf

        @forelse ($schema as $fieldRow)
            @php
                $data        = $fieldRow['data'] ?? $fieldRow;
                $fieldKey    = $data['field_key'] ?? null;
                $type        = $data['type'] ?? 'text';
                $label       = $data['label'] ?? '';
                $helpText    = $data['help_text'] ?? '';
                $placeholder = $data['placeholder'] ?? '';
                $required    = ! empty($data['required']);
                $options     = $data['options'] ?? [];
                $allowMultiple = ! empty($data['allow_multiple']);
                $multipleFiles = ! empty($data['multiple_files']);
                $content     = $data['content'] ?? '';

                $inputName  = $fieldKey; // matches form_schema field_key
                $oldValue   = old($fieldKey ?? '');
            @endphp

            @if($fieldKey === 'header')
                <div class="apply-form-section-title">{{ $label ?: 'Section' }}</div>
            @elseif($fieldKey === 'paragraph')
                <p style="font-size: .85rem; color: var(--muted); line-height: 1.65; margin-bottom: 1rem;">{{ $content ?: $label }}</p>
            @elseif($fieldKey)
                <div class="apply-form-group">
                    <label class="apply-form-label" for="f_{{ $fieldKey }}">
                        {{ $label }}
                        @if($required)<span class="apply-required-star">*</span>@endif
                    </label>

                    @if($helpText)
                        <p class="apply-help-text">{{ $helpText }}</p>
                    @endif

                    @if($type === 'file')
                        <input type="file"
                            id="f_{{ $fieldKey }}"
                            name="{{ $fieldKey }}"
                            class="apply-form-input {{ $errors->has($fieldKey) ? 'has-error' : '' }}"
                            {{ $multipleFiles ? 'multiple' : '' }}
                            {{ $required ? 'required' : '' }}>

                    @elseif($type === 'skill_select' && $allowMultiple)
                        {{-- Custom multi-select with checkboxes for skills --}}
                        @php $oldSkills = old($fieldKey, []); if (!is_array($oldSkills)) $oldSkills = []; @endphp
                        <div class="skill-multi-select" x-data="{
                            open: false,
                            selected: @js($oldSkills),
                            options: @js(collect($options)->filter(fn($o) => !empty($o['value']))->values()->toArray()),
                            toggle(val) {
                                const idx = this.selected.indexOf(val);
                                if (idx > -1) this.selected.splice(idx, 1);
                                else this.selected.push(val);
                            },
                            labelFor(val) {
                                const o = this.options.find(o => o.value === val);
                                return o ? o.label : val;
                            }
                        }" @click.away="open = false">
                            <div class="skill-multi-selected-pills" @click="open = !open">
                                <template x-for="val in selected" :key="val">
                                    <span class="pill">
                                        <span x-text="labelFor(val)"></span>
                                        <button type="button" @click.stop="toggle(val)">&times;</button>
                                    </span>
                                </template>
                                <span class="placeholder" x-show="selected.length === 0">Select skills...</span>
                            </div>
                            <div class="skill-multi-dropdown" x-show="open" x-transition style="display: none;">
                                <template x-for="opt in options" :key="opt.value">
                                    <label>
                                        <input type="checkbox" :value="opt.value" :checked="selected.includes(opt.value)" @change="toggle(opt.value)">
                                        <span x-text="opt.label"></span>
                                    </label>
                                </template>
                            </div>
                            {{-- Hidden inputs for form submission --}}
                            <template x-for="val in selected" :key="'h_'+val">
                                <input type="hidden" name="{{ $fieldKey }}[]" :value="val">
                            </template>
                        </div>

                    @elseif($type === 'select' || $type === 'skill_select')
                        <select id="f_{{ $fieldKey }}"
                            name="{{ $fieldKey }}{{ $allowMultiple ? '[]' : '' }}"
                            class="apply-form-input {{ $errors->has($fieldKey) ? 'has-error' : '' }}"
                            {{ $allowMultiple ? 'multiple' : '' }}
                            {{ $required ? 'required' : '' }}>
                            @foreach($options as $opt)
                                <option value="{{ $opt['value'] ?? '' }}"
                                    {{ $oldValue == ($opt['value'] ?? '') ? 'selected' : '' }}>
                                    {{ $opt['label'] ?? '' }}
                                </option>
                            @endforeach
                        </select>

                    @elseif($type === 'date')
                        <input type="date"
                            id="f_{{ $fieldKey }}"
                            name="{{ $fieldKey }}"
                            value="{{ $oldValue }}"
                            class="apply-form-input {{ $errors->has($fieldKey) ? 'has-error' : '' }}"
                            {{ $required ? 'required' : '' }}>

                    @elseif($type === 'number')
                        <input type="number"
                            id="f_{{ $fieldKey }}"
                            name="{{ $fieldKey }}"
                            value="{{ $oldValue }}"
                            placeholder="{{ $placeholder }}"
                            class="apply-form-input {{ $errors->has($fieldKey) ? 'has-error' : '' }}"
                            {{ $required ? 'required' : '' }}>

                    @elseif(in_array($fieldKey, ['introduce_yourself', 'interests', 'resident', 'current_accommodation', 'reason_for_leaving_job', 'job_description', 'birthplace', 'home_town']))
                        <textarea id="f_{{ $fieldKey }}"
                            name="{{ $fieldKey }}"
                            rows="3"
                            placeholder="{{ $placeholder }}"
                            class="apply-form-input {{ $errors->has($fieldKey) ? 'has-error' : '' }}"
                            {{ $required ? 'required' : '' }}>{{ $oldValue }}</textarea>

                    @else
                        <input type="{{ $fieldKey === 'email' ? 'email' : 'text' }}"
                            id="f_{{ $fieldKey }}"
                            name="{{ $fieldKey }}"
                            value="{{ $oldValue ?: ($candidate->{$fieldKey} ?? '') }}"
                            placeholder="{{ $placeholder }}"
                            class="apply-form-input {{ $errors->has($fieldKey) ? 'has-error' : '' }}"
                            {{ $required ? 'required' : '' }}>
                    @endif

                    @error($fieldKey)
                        <p style="color: var(--danger); font-size: .78rem; margin-top: .3rem;">{{ $message }}</p>
                    @enderror
                </div>
            @endif
        @empty
            {{-- Minimal form when no channel schema defined --}}
            <div class="apply-form-group">
                <label class="apply-form-label" for="f_cover_letter">Cover Letter</label>
                <textarea id="f_cover_letter" name="cover_letter" rows="6"
                    placeholder="Tell us why you're a great fit for this position..."
                    class="apply-form-input {{ $errors->has('cover_letter') ? 'has-error' : '' }}">{{ old('cover_letter') }}</textarea>
            </div>
        @endforelse

        {{-- Always allow cover letter if not already in schema --}}
        @php $hasCoverLetter = collect($schema)->contains(fn($r) => ($r['data']['field_key'] ?? $r['field_key'] ?? null) === 'cover_letter'); @endphp
        @if(! $hasCoverLetter)
        <div class="apply-form-group">
            <label class="apply-form-label" for="f_cover_letter">Cover Letter <span style="color: var(--muted); font-weight: 400;">(optional)</span></label>
            <textarea id="f_cover_letter" name="cover_letter" rows="5"
                placeholder="Tell us why you're a great fit..."
                class="apply-form-input">{{ old('cover_letter') }}</textarea>
        </div>
        @endif

        {{-- Submit --}}
        <div style="margin-top: 2rem; padding-top: 1.5rem; border-top: 1px solid var(--border); display: flex; justify-content: flex-end; gap: 1rem; align-items: center;">
            <a href="{{ route('candidate.campaigns.show', $campaign) }}"
               style="font-size: .88rem; color: var(--muted); text-decoration: none; font-weight: 500;">Cancel</a>
            <button type="submit"
                    style="display: inline-flex; align-items: center; gap: .4rem;
                           padding: .7rem 2rem; border-radius: 10px; border: none;
                           font-size: .95rem; font-weight: 700; cursor: pointer;
                           background: var(--teal); color: #fff; transition: background .15s;"
                    onmouseover="this.style.background='var(--teal2)'"
                    onmouseout="this.style.background='var(--teal)'">
                {{ $submitLabel }}
                <svg style="width: 15px; height: 15px;" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M13.5 4.5L21 12m0 0l-7.5 7.5M21 12H3"/></svg>
            </button>
        </div>
    </form>

</div>
@endsection
