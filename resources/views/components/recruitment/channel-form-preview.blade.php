@php
    $resolvedSchema = $schema ?? $formSchema ?? [];

    if (is_string($resolvedSchema)) {
        $resolvedSchema = json_decode($resolvedSchema, true) ?? [];
    }
@endphp

@if (empty($resolvedSchema))
    <div style="text-align:center; padding:24px; color:#9ca3af;">
        <p>No form fields configured.</p>
    </div>
@else
    <div style="max-width:680px; margin:0 auto;">
        @foreach ($resolvedSchema as $field)
            @php
                $data = $field['data'] ?? $field;
                $fieldKey = $data['field_key'] ?? $field['type'] ?? '';
                $type = $data['type'] ?? '';
                $label = $data['label'] ?? '';
                $helpText = $data['help_text'] ?? '';
                $placeholder = $data['placeholder'] ?? '';
                $required = ! empty($data['required']);
                $options = $data['options'] ?? [];
                $allowMultiple = ! empty($data['allow_multiple']);
                $multipleFiles = ! empty($data['multiple_files']);
                $content = $data['content'] ?? '';

                $isHeader = $fieldKey === 'header';
                $isParagraph = $fieldKey === 'paragraph';

                $inputStyle = 'width:100%;border:1px solid #d1d5db;border-radius:6px;padding:8px 10px;font-size:13px;background:#fff;color:#374151;';
            @endphp

            @if ($isHeader)
                <h2 style="font-size:22px;font-weight:700;margin:16px 0 8px;color:#111827;border-bottom:1px solid #e5e7eb;padding-bottom:8px;">
                    {{ $label ?: 'Header' }}
                </h2>
            @elseif ($isParagraph)
                <p style="font-size:14px;line-height:1.65;color:#4b5563;margin:0 0 14px;">
                    {{ $content ?: $label }}
                </p>
            @else
                <div style="margin-bottom:16px;">
                    <label style="display:block;font-size:13px;font-weight:600;color:#111827;margin-bottom:4px;">
                        {{ $label }}
                        @if ($required)
                            <span style="color:#ef4444;"> *</span>
                        @endif
                    </label>

                    @if ($helpText)
                        <p style="font-size:12px;color:#6b7280;margin:0 0 4px;">{{ $helpText }}</p>
                    @endif

                    @if ($type === 'file')
                        <input type="file" style="{{ $inputStyle }}" {{ $multipleFiles ? 'multiple' : '' }}>
                    @elseif ($type === 'select' || $type === 'skill_select')
                        @if ($allowMultiple)
                            <div style="{{ $inputStyle }} display:flex; gap:6px; flex-wrap:wrap; align-items:center; min-height:38px; cursor:pointer;">
                                @foreach (array_slice($options, 0, 3) as $opt)
                                    <span style="background:#eef2ff;border:1px solid #c7d2fe;padding:3px 10px;border-radius:16px;font-size:12px;color:#4338ca;font-weight:500;">
                                        {{ $opt['label'] ?? '' }}
                                    </span>
                                @endforeach
                                <span style="color:#9ca3af;font-size:12px;">Select options...</span>
                            </div>
                        @else
                            <select style="{{ $inputStyle }}">
                                @foreach ($options as $opt)
                                    <option value="{{ $opt['value'] ?? '' }}">{{ $opt['label'] ?? '' }}</option>
                                @endforeach
                            </select>
                        @endif
                    @elseif ($type === 'date')
                        <input type="date" style="{{ $inputStyle }}">
                    @elseif ($type === 'number')
                        <input type="number" style="{{ $inputStyle }}" placeholder="{{ $placeholder }}">
                    @elseif (in_array($fieldKey, ['introduce_yourself', 'interests', 'resident', 'current_accommodation', 'reason_for_leaving_job', 'job_description', 'birthplace', 'home_town']))
                        <textarea rows="3" style="{{ $inputStyle }} resize:vertical;min-height:80px;" placeholder="{{ $placeholder }}"></textarea>
                    @else
                        <input type="{{ $fieldKey === 'email' ? 'email' : 'text' }}" style="{{ $inputStyle }}" placeholder="{{ $placeholder }}">
                    @endif
                </div>
            @endif
        @endforeach
    </div>
@endif
