@php
    $resolvedSchema = $schema ?? $formSchema ?? [];

    if (is_string($resolvedSchema)) {
        $resolvedSchema = json_decode($resolvedSchema, true) ?? [];
    }
@endphp

@if (empty($resolvedSchema))
    <div class="text-center p-6 text-gray-400 dark:text-gray-500">
        <p>No form fields configured.</p>
    </div>
@else
    <div style="max-width: 1100px; margin:0 auto;">
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
            @endphp

            @if ($isHeader)
                <h2 class="text-[22px] font-bold mt-4 mb-2 pb-2 border-b text-gray-900 dark:text-gray-100 border-gray-200 dark:border-gray-700">
                    {{ $label ?: 'Header' }}
                </h2>
            @elseif ($isParagraph)
                <p class="text-sm leading-relaxed text-gray-600 dark:text-gray-400 mb-3.5">
                    {{ $content ?: $label }}
                </p>
            @else
                <div class="mb-4">
                    <label class="block text-[13px] font-semibold mb-1 text-gray-900 dark:text-gray-100">
                        {{ $label }}
                        @if ($required)
                            <span class="text-red-500"> *</span>
                        @endif
                    </label>

                    @if ($helpText)
                        <p class="text-xs text-gray-500 dark:text-gray-400 mb-1">{{ $helpText }}</p>
                    @endif

                    @if ($type === 'file')
                        <input type="file"
                            class="w-full border rounded-md px-2.5 py-2 text-[13px]
                                   bg-white dark:bg-gray-800
                                   border-gray-300 dark:border-gray-600
                                   text-gray-700 dark:text-gray-200"
                            {{ $multipleFiles ? 'multiple' : '' }}>
                    @elseif ($type === 'select' || $type === 'skill_select')
                        @if ($allowMultiple)
                            <div class="w-full border rounded-md px-2.5 py-2 text-[13px] flex gap-1.5 flex-wrap items-center min-h-[38px] cursor-pointer
                                        bg-white dark:bg-gray-800
                                        border-gray-300 dark:border-gray-600
                                        text-gray-700 dark:text-gray-200">
                                @foreach (array_slice($options, 0, 3) as $opt)
                                    <span class="bg-indigo-50 dark:bg-indigo-900/40 border border-indigo-200 dark:border-indigo-700 px-2.5 py-0.5 rounded-full text-xs text-indigo-700 dark:text-indigo-300 font-medium">
                                        {{ $opt['label'] ?? '' }}
                                    </span>
                                @endforeach
                                <span class="text-gray-400 dark:text-gray-500 text-xs">Select options...</span>
                            </div>
                        @else
                            <select class="w-full border rounded-md px-2.5 py-2 text-[13px]
                                          bg-white dark:bg-gray-800
                                          border-gray-300 dark:border-gray-600
                                          text-gray-700 dark:text-gray-200">
                                @foreach ($options as $opt)
                                    <option value="{{ $opt['value'] ?? '' }}">{{ $opt['label'] ?? '' }}</option>
                                @endforeach
                            </select>
                        @endif
                    @elseif ($type === 'date')
                        <input type="date"
                            class="w-full border rounded-md px-2.5 py-2 text-[13px]
                                   bg-white dark:bg-gray-800
                                   border-gray-300 dark:border-gray-600
                                   text-gray-700 dark:text-gray-200">
                    @elseif ($type === 'number')
                        <input type="number" placeholder="{{ $placeholder }}"
                            class="w-full border rounded-md px-2.5 py-2 text-[13px]
                                   bg-white dark:bg-gray-800
                                   border-gray-300 dark:border-gray-600
                                   text-gray-700 dark:text-gray-200">
                    @elseif (in_array($fieldKey, ['introduce_yourself', 'interests', 'resident', 'current_accommodation', 'reason_for_leaving_job', 'job_description', 'birthplace', 'home_town']))
                        <textarea rows="3" placeholder="{{ $placeholder }}"
                            class="w-full border rounded-md px-2.5 py-2 text-[13px] resize-y min-h-[80px]
                                   bg-white dark:bg-gray-800
                                   border-gray-300 dark:border-gray-600
                                   text-gray-700 dark:text-gray-200"></textarea>
                    @else
                        <input type="{{ $fieldKey === 'email' ? 'email' : 'text' }}" placeholder="{{ $placeholder }}"
                            class="w-full border rounded-md px-2.5 py-2 text-[13px]
                                   bg-white dark:bg-gray-800
                                   border-gray-300 dark:border-gray-600
                                   text-gray-700 dark:text-gray-200">
                    @endif
                </div>
            @endif
        @endforeach
    </div>
@endif
