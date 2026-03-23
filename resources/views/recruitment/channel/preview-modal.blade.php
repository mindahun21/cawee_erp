<div class="prose max-w-2xl mx-auto filament-modal-content">
    @if(empty($formSchema))
        <div class="p-8 bg-gray-50 rounded-xl text-center text-gray-400 italic border-2 border-dashed border-gray-200">
            The form is currently empty. Add fields from the "Form builder" tab.
        </div>
    @else
        <div class="space-y-6 bg-white p-6 rounded-xl border border-gray-100 shadow-sm">
            @foreach($formSchema as $block)
                @php
                    $blockData = $block['data'] ?? [];
                    $label = $blockData['label'] ?? 'Unknown Field';
                    $type = $block['type'] ?? 'unknown';
                    $required = !empty($blockData['required']);
                    $placeholder = $blockData['placeholder'] ?? '';
                    $helpText = $blockData['help_text'] ?? '';
                @endphp
                
                <div class="flex flex-col gap-1 w-full">
                    @if($type === 'layout_header')
                        <h2 class="text-2xl font-bold text-gray-800 border-b pb-2 mb-2 mt-4">{{ $label }}</h2>
                    @elseif($type === 'layout_paragraph')
                        <p class="text-gray-600 leading-relaxed mb-4">{{ $blockData['content'] ?? $label }}</p>
                    @else
                        {{-- STANDARD FIELD LABEL --}}
                        <label class="text-sm font-semibold text-gray-700 flex items-center gap-1">
                            {{ $label }}
                            @if($required)
                                <span class="text-red-500 font-bold" title="Required">*</span>
                            @endif
                        </label>

                        {{-- FIELD INPUT RENDERER --}}
                        @if($type === 'file')
                            <div class="mt-1 flex justify-center px-6 pt-5 pb-6 border-2 border-gray-300 border-dashed rounded-md bg-gray-50">
                                <div class="space-y-1 text-center">
                                    <svg class="mx-auto h-12 w-12 text-gray-400" stroke="currentColor" fill="none" viewBox="0 0 48 48" aria-hidden="true">
                                        <path d="M28 8H12a4 4 0 00-4 4v20m32-12v8m0 0v8a4 4 0 01-4 4H12a4 4 0 01-4-4v-4m32-4l-3.172-3.172a4 4 0 00-5.656 0L28 28M8 32l9.172-9.172a4 4 0 015.656 0L28 28m0 0l4 4m4-24h8m-4-4v8m-12 4h.02" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" />
                                    </svg>
                                    <div class="flex text-sm text-gray-600 justify-center">
                                        <label class="relative cursor-pointer bg-white rounded-md font-medium text-primary-600 hover:text-primary-500 focus-within:outline-none focus-within:ring-2 focus-within:ring-offset-2 focus-within:ring-primary-500">
                                            <span>Upload a file</span>
                                        </label>
                                        <p class="pl-1">or drag and drop</p>
                                    </div>
                                    <p class="text-xs text-gray-500">PNG, JPG, PDF up to 10MB</p>
                                </div>
                            </div>
                        @elseif(in_array($type, ['gender', 'marital_status', 'nationality', 'seniority']))
                            {{-- SELECT DROPDOWN --}}
                            <select class="mt-1 block w-full pl-3 pr-10 py-2 text-base border-gray-300 focus:outline-none focus:ring-primary-500 focus:border-primary-500 sm:text-sm rounded-md shadow-sm bg-white border">
                                <option value="">Select {{ strtolower($label) }}...</option>
                                @if($type === 'gender')
                                    <option>Male</option>
                                    <option>Female</option>
                                @elseif($type === 'marital_status')
                                    <option>Single</option>
                                    <option>Married</option>
                                @elseif($type === 'seniority')
                                    <option>No seniority yet</option>
                                    <option>1 year</option>
                                    <option>2 years</option>
                                    <option>5+ years</option>
                                @else
                                    <option>Option 1</option>
                                    <option>Option 2</option>
                                @endif
                            </select>
                        @elseif($type === 'birthday' || $type === 'days_for_identity')
                            {{-- DATE INPUT --}}
                            <input type="date" class="mt-1 shadow-sm focus:ring-primary-500 focus:border-primary-500 block w-full sm:text-sm border-gray-300 rounded-md border px-3 py-2">
                        @else
                            {{-- TEXT/NUMBER INPUT --}}
                            <input type="{{ in_array($type, ['desired_salary', 'height_m', 'weight_kg', 'salary', 'percentage']) ? 'number' : 'text' }}" 
                                   placeholder="{{ $placeholder }}" 
                                   class="mt-1 shadow-sm focus:ring-primary-500 focus:border-primary-500 block w-full sm:text-sm border-gray-300 rounded-md border px-3 py-2">
                        @endif

                        {{-- HELP TEXT --}}
                        @if($helpText)
                            <p class="mt-1 text-xs text-gray-500">{{ $helpText }}</p>
                        @endif

                    @endif
                </div>
            @endforeach
        </div>
    @endif
</div>
