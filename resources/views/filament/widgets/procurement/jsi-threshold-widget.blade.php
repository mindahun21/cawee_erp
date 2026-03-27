{{-- ══════════════════════════════════════════════════════════════ --}}
{{-- JSI Procurement Threshold Compliance Panel                      --}}
{{-- ══════════════════════════════════════════════════════════════ --}}
@php
    use App\Services\Procurement\JsiThresholds;
@endphp

<div class="rounded-xl bg-white shadow-sm ring-1 ring-gray-950/5 dark:bg-gray-900 dark:ring-white/10 overflow-hidden">

    {{-- Header --}}
    <div class="flex items-center justify-between px-6 py-4 border-b border-gray-200 dark:border-white/10"
         style="background: linear-gradient(135deg, #0f172a 0%, #1e3a5f 100%);">
        <div class="flex items-center gap-3">
            <div class="flex h-9 w-9 items-center justify-center rounded-lg bg-white/10">
                <x-filament::icon icon="heroicon-o-scale" class="h-5 w-5 text-white" />
            </div>
            <div>
                <h2 class="text-sm font-bold text-white tracking-wide">JSI Procurement Threshold Compliance</h2>
                <p class="text-xs text-blue-200/70 mt-0.5">Federal procurement methods and authorization thresholds — ETB denominated</p>
            </div>
        </div>
        <span class="rounded-full bg-white/10 px-3 py-1 text-xs font-semibold text-white">
            {{ array_sum(array_values($counts)) }} Total PR{{ array_sum(array_values($counts)) !== 1 ? 's' : '' }}
        </span>
    </div>

    {{-- 4-Tier Grid --}}
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 divide-y sm:divide-y-0 sm:divide-x divide-gray-100 dark:divide-white/5">
        @foreach($tiers as $key => $tier)
            @php $count = $counts[$key] ?? 0; @endphp
            <div class="p-5 hover:bg-gray-50 dark:hover:bg-white/5 transition-colors duration-150">

                {{-- Tier badge --}}
                <div class="flex items-center justify-between mb-3">
                    <span class="inline-flex items-center gap-1.5 rounded-full px-2.5 py-1 text-[10px] font-bold uppercase tracking-wider"
                          style="background-color:{{ $tier['badge_bg'] }};color:{{ $tier['badge_text'] }};">
                        @if($key === \App\Services\Procurement\JsiThresholds::TIER_MICRO)
                            ① Micro
                        @elseif($key === \App\Services\Procurement\JsiThresholds::TIER_SIMPLIFIED)
                            ② Simplified
                        @elseif($key === \App\Services\Procurement\JsiThresholds::TIER_RFQ)
                            ③ RFQ / RFP
                        @else
                            ④ Open Tender
                        @endif
                    </span>
                    <span class="text-2xl font-black" style="color:{{ $tier['color'] }}">{{ $count }}</span>
                </div>

                {{-- Tier name + range --}}
                <div class="mb-3">
                    <div class="text-sm font-bold text-gray-900 dark:text-white leading-tight">{{ $tier['label'] }}</div>
                    <div class="text-xs font-semibold mt-0.5" style="color:{{ $tier['color'] }}">{{ $tier['range'] }}</div>
                </div>

                {{-- Key rule chips --}}
                <div class="space-y-1">
                    @if($tier['quotations'] === 0)
                        <div class="flex items-center gap-1.5 text-[11px] text-gray-600 dark:text-gray-400">
                            <span class="h-1.5 w-1.5 rounded-full flex-shrink-0" style="background:{{ $tier['color'] }}"></span>
                            Full open competition
                        </div>
                        <div class="flex items-center gap-1.5 text-[11px] text-gray-600 dark:text-gray-400">
                            <span class="h-1.5 w-1.5 rounded-full flex-shrink-0" style="background:{{ $tier['color'] }}"></span>
                            Public advertisement required
                        </div>
                    @elseif($tier['quotations'] === 1)
                        <div class="flex items-center gap-1.5 text-[11px] text-gray-600 dark:text-gray-400">
                            <span class="h-1.5 w-1.5 rounded-full flex-shrink-0" style="background:{{ $tier['color'] }}"></span>
                            1 quotation · No competition
                        </div>
                        <div class="flex items-center gap-1.5 text-[11px] text-gray-600 dark:text-gray-400">
                            <span class="h-1.5 w-1.5 rounded-full flex-shrink-0" style="background:{{ $tier['color'] }}"></span>
                            Justification document required
                        </div>
                    @else
                        <div class="flex items-center gap-1.5 text-[11px] text-gray-600 dark:text-gray-400">
                            <span class="h-1.5 w-1.5 rounded-full flex-shrink-0" style="background:{{ $tier['color'] }}"></span>
                            Min {{ $tier['quotations'] }} quotations required
                        </div>
                        @if($key === \App\Services\Procurement\JsiThresholds::TIER_SIMPLIFIED)
                            <div class="flex items-center gap-1.5 text-[11px] text-gray-600 dark:text-gray-400">
                                <span class="h-1.5 w-1.5 rounded-full flex-shrink-0" style="background:{{ $tier['color'] }}"></span>
                                Price analysis required
                            </div>
                        @else
                            <div class="flex items-center gap-1.5 text-[11px] text-gray-600 dark:text-gray-400">
                                <span class="h-1.5 w-1.5 rounded-full flex-shrink-0" style="background:{{ $tier['color'] }}"></span>
                                Formal bid evaluation
                            </div>
                        @endif
                    @endif
                    <div class="flex items-center gap-1.5 text-[11px] text-gray-600 dark:text-gray-400">
                        <span class="h-1.5 w-1.5 rounded-full flex-shrink-0" style="background:{{ $tier['color'] }}"></span>
                        {{ $tier['method_label'] }}
                    </div>
                </div>

                {{-- Step count chip --}}
                <div class="mt-3 pt-3 border-t border-gray-100 dark:border-white/5">
                    <span class="text-[10px] text-gray-400 dark:text-gray-500">
                        {{ count($tier['steps']) }} process steps
                    </span>
                </div>
            </div>
        @endforeach
    </div>

    {{-- Process flow footer bar --}}
    <div class="px-6 py-3 border-t border-gray-100 dark:border-white/5 bg-gray-50 dark:bg-white/5">
        <div class="flex flex-wrap items-center gap-x-1 gap-y-1">
            @foreach(['Purchase Request','PR Approval','RFQ / Tender','Bids / Quotes','PO Approval','Purchase Order','GRN','Inventory / Asset','Invoice','Payment'] as $i => $step)
                <span class="text-[11px] font-medium text-gray-700 dark:text-gray-300">{{ $step }}</span>
                @if(!$loop->last)
                    <span class="text-gray-400 dark:text-gray-600 text-xs">→</span>
                @endif
            @endforeach
        </div>
    </div>
</div>
