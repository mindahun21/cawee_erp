<x-filament-panels::page>

    <div class="space-y-6">

        {{-- ══════════════════════════════════════════════════════════════ --}}
        {{-- HERO BANNER                                                     --}}
        {{-- ══════════════════════════════════════════════════════════════ --}}
        <div class="rounded-xl bg-white px-6 py-8 shadow-sm ring-1 ring-gray-950/5 dark:bg-gray-900 dark:ring-white/10">
            <div class="flex flex-col gap-6 sm:flex-row sm:items-center sm:justify-between">
                <div>
                    <h1 class="text-2xl font-bold tracking-tight text-gray-950 dark:text-white sm:text-3xl">Procurement Dashboard</h1>
                    <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
                        {{ now()->format('l, F j, Y') }} &nbsp;·&nbsp; FY {{ $currentYear }}
                    </p>
                </div>

                <div class="grid grid-cols-2 gap-3 sm:grid-cols-4">
                    @php
                        $heroKpis = [
                            ['label' => 'Pending Requisitions', 'value' => $reqPending,     'icon' => 'heroicon-o-clipboard-document-list',   'cls' => $reqPending > 0    ? 'bg-warning-50 ring-warning-500/20 dark:bg-warning-500/10 dark:ring-warning-500/20' : 'bg-gray-50 ring-gray-950/5 dark:bg-white/5 dark:ring-white/10'],
                            ['label' => 'Open Tenders',        'value' => $tendersOpen,     'icon' => 'heroicon-o-document-magnifying-glass', 'cls' => 'bg-gray-50 ring-gray-950/5 dark:bg-white/5 dark:ring-white/10'],
                            ['label' => 'Overdue Invoices',    'value' => $invoiceOverdue,  'icon' => 'heroicon-o-exclamation-triangle',      'cls' => $invoiceOverdue > 0 ? 'bg-danger-50 ring-danger-500/20 dark:bg-danger-500/10 dark:ring-danger-500/20'   : 'bg-gray-50 ring-gray-950/5 dark:bg-white/5 dark:ring-white/10'],
                            ['label' => 'GRNs Pending',        'value' => $grnPending,      'icon' => 'heroicon-o-truck',                     'cls' => $grnPending > 0    ? 'bg-info-50 ring-info-500/20 dark:bg-info-500/10 dark:ring-info-500/20'             : 'bg-gray-50 ring-gray-950/5 dark:bg-white/5 dark:ring-white/10'],
                        ];
                    @endphp
                    @foreach($heroKpis as $kpi)
                        <div class="flex items-center gap-3 rounded-xl {{ $kpi['cls'] }} ring-1 ring-inset px-4 py-3">
                            <x-filament::icon :icon="$kpi['icon']" class="h-6 w-6 shrink-0 opacity-80 text-gray-400 dark:text-gray-500" />
                            <div>
                                <div class="text-xl font-bold leading-none text-gray-950 dark:text-white">{{ $kpi['value'] }}</div>
                                <div class="mt-0.5 text-[11px] font-medium uppercase tracking-wider opacity-70">{{ $kpi['label'] }}</div>
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>
        </div>

        {{-- ══════════════════════════════════════════════════════════════ --}}
        {{-- SUMMARY STRIP                                                  --}}
        {{-- ══════════════════════════════════════════════════════════════ --}}
        <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-4">
            @php
                $strips = [
                    ['label' => 'PO Value YTD', 'value' => 'ETB ' . number_format($poValueYTD, 0), 'sub' => 'All purchase orders ' . $currentYear, 'icon' => 'heroicon-o-shopping-cart', 'iconBg' => 'bg-primary-50 dark:bg-primary-500/10', 'iconText' => 'text-primary-600 dark:text-primary-400', 'trend' => null],
                    ['label' => 'Budget Utilization', 'value' => $utilizationPct . '%', 'sub' => 'Committed + expended vs allocated', 'icon' => 'heroicon-o-chart-bar', 'iconBg' => $utilizationPct >= 90 ? 'bg-danger-50 dark:bg-danger-500/10' : ($utilizationPct >= 70 ? 'bg-warning-50 dark:bg-warning-500/10' : 'bg-success-50 dark:bg-success-500/10'), 'iconText' => $utilizationPct >= 90 ? 'text-danger-600 dark:text-danger-400' : ($utilizationPct >= 70 ? 'text-warning-600 dark:text-warning-400' : 'text-success-600 dark:text-success-400'), 'trend' => $utilizationPct >= 90 ? 'up' : null],
                    ['label' => 'Active Contracts', 'value' => $contractsActive, 'sub' => $contractsExpiring > 0 ? "{$contractsExpiring} expiring in 30 days ⚠️" : 'No contracts expiring soon', 'icon' => 'heroicon-o-document-check', 'iconBg' => $contractsExpiring > 0 ? 'bg-warning-50 dark:bg-warning-500/10' : 'bg-success-50 dark:bg-success-500/10', 'iconText' => $contractsExpiring > 0 ? 'text-warning-600 dark:text-warning-400' : 'text-success-600 dark:text-success-400', 'trend' => null],
                    ['label' => 'Payments Pending Auth.', 'value' => $paymentPending, 'sub' => 'ETB ' . number_format($paymentsThisMonth, 0) . ' processed this month', 'icon' => 'heroicon-o-banknotes', 'iconBg' => $paymentPending > 0 ? 'bg-warning-50 dark:bg-warning-500/10' : 'bg-success-50 dark:bg-success-500/10', 'iconText' => $paymentPending > 0 ? 'text-warning-600 dark:text-warning-400' : 'text-success-600 dark:text-success-400', 'trend' => null],
                ];
            @endphp
            @foreach($strips as $s)
                <div class="flex items-center gap-4 rounded-xl bg-white px-5 py-4 shadow-sm ring-1 ring-gray-950/5 dark:bg-gray-900 dark:ring-white/10">
                    <div class="flex h-12 w-12 shrink-0 items-center justify-center rounded-xl {{ $s['iconBg'] }}">
                        <x-filament::icon :icon="$s['icon']" class="h-6 w-6 {{ $s['iconText'] }}" />
                    </div>
                    <div class="min-w-0 flex-1">
                        <div class="flex items-baseline gap-1.5">
                            <span class="text-2xl font-bold tracking-tight text-gray-950 dark:text-white">{{ $s['value'] }}</span>
                            @if($s['trend'] === 'up')
                                <x-filament::icon icon="heroicon-s-arrow-trending-up" class="h-4 w-4 text-danger-500" />
                            @endif
                        </div>
                        <div class="text-sm font-semibold text-gray-950 dark:text-white leading-tight">{{ $s['label'] }}</div>
                        <div class="text-xs text-gray-500 dark:text-gray-400 mt-0.5">{{ $s['sub'] }}</div>
                    </div>
                </div>
            @endforeach
        </div>

        {{-- ══════════════════════════════════════════════════════════════ --}}
        {{-- JSI PIPELINE PANEL — Live operational view only                 --}}
        {{-- ══════════════════════════════════════════════════════════════ --}}
        <style>
            .jsi-grid  { display:grid;grid-template-columns:repeat(4,1fr);background:#ffffff; }
            .dark .jsi-grid  { background:#1e293b; }
            .jsi-col   { transition:background .12s; }
            .jsi-col:hover { background:#f8fafc; }
            .dark .jsi-col:hover { background:rgba(255,255,255,.03); }
            .jsi-col-divider { border-right:1px solid #f1f5f9; }
            .dark .jsi-col-divider { border-right-color:rgba(255,255,255,.06); }
            .jsi-footer { padding:.5rem 1.5rem;border-top:1px solid #f1f5f9;background:#f8fafc;display:flex;align-items:center;justify-content:space-between;flex-wrap:wrap;gap:.5rem; }
            .dark .jsi-footer { background:rgba(255,255,255,.02);border-top-color:rgba(255,255,255,.05); }
            .jsi-footer-cnt { color:#334155;font-weight:700; }
            .dark .jsi-footer-cnt { color:#e2e8f0; }
            .jsi-footer-lbl { font-size:.65rem;color:#64748b; }
            .dark .jsi-footer-lbl { color:#94a3b8; }
            .jsi-bar-bg { background:#f1f5f9;border-radius:99px;height:3px; }
            .dark .jsi-bar-bg { background:rgba(255,255,255,.07); }
            .jsi-pct { font-size:.58rem;color:#cbd5e1;margin-top:.3rem;text-align:right; }
            .dark .jsi-pct { color:#475569;}
        </style>
        @php
            $jsiTotal  = ($jsiMicroCount ?? 0) + ($jsiSimplifiedCount ?? 0) + ($jsiRfqCount ?? 0) + ($jsiOpenCount ?? 0);
            $jsiCounts = [
                ['label'=>'① Micro',     'range'=>'< ETB 77K',     'color'=>'#10b981','count'=>$jsiMicroCount      ?? 0],
                ['label'=>'② Simplified','range'=>'77K – 1.54M',   'color'=>'#3b82f6','count'=>$jsiSimplifiedCount ?? 0],
                ['label'=>'③ RFQ/RFP',  'range'=>'1.54M – 38.5M', 'color'=>'#f59e0b','count'=>$jsiRfqCount        ?? 0],
                ['label'=>'④ Open',      'range'=>'> ETB 38.5M',   'color'=>'#ef4444','count'=>$jsiOpenCount       ?? 0],
            ];
        @endphp
        <div style="border:1px solid #e2e8f0;border-radius:10px;overflow:hidden;" class="dark:border-white/[.07]">

            {{-- Header --}}
            <div style="background:#0f172a;padding:.875rem 1.5rem;display:flex;align-items:center;justify-content:space-between;gap:1rem;flex-wrap:wrap;">
                <div>
                    <div style="font-size:.85rem;font-weight:700;color:#f8fafc;letter-spacing:-.01em;">Procurement Pipeline — JSI Threshold Distribution</div>
                    <div style="font-size:.68rem;color:#475569;margin-top:.2rem;">
                        Live active PRs by authorization tier
                        @if($jsiTotal > 0)<span style="color:#64748b;"> · {{ $jsiTotal }} total</span>@endif
                    </div>
                </div>
                <a href="{{ route('filament.admin.pages.jsi-procurement-journey') }}"
                   style="font-size:.68rem;font-weight:600;color:#64748b;border:1px solid #1e293b;border-radius:5px;padding:.3rem .75rem;white-space:nowrap;text-decoration:none;"
                   onmouseover="this.style.color='#f8fafc'" onmouseout="this.style.color='#64748b'">
                    Process Guide →
                </a>
            </div>

            {{-- 4 Tier Columns --}}
            <div class="jsi-grid">
                @foreach($jsiCounts as $i => $t)
                    @php $pct = $jsiTotal > 0 ? round(($t['count'] / $jsiTotal) * 100) : 0; @endphp
                    <div class="jsi-col {{ $i < 3 ? 'jsi-col-divider' : '' }}"
                         style="padding:1.125rem 1.25rem 1rem;">

                        {{-- Tier + range --}}
                        <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:.75rem;">
                            <span style="font-size:.62rem;font-weight:800;text-transform:uppercase;letter-spacing:.07em;color:{{ $t['color'] }};">{{ $t['label'] }}</span>
                            <span style="font-size:.58rem;color:#94a3b8;font-family:monospace;">{{ $t['range'] }}</span>
                        </div>

                        {{-- Count --}}
                        <div style="font-size:2.25rem;font-weight:800;color:{{ $t['color'] }};line-height:1;margin-bottom:.25rem;">{{ $t['count'] }}</div>
                        <div style="font-size:.65rem;color:#94a3b8;margin-bottom:.75rem;">active PR{{ $t['count'] !== 1 ? 's' : '' }}</div>

                        <div class="jsi-bar-bg">
                            <div style="width:{{ $pct > 0 ? max(5,$pct) : 0 }}%;height:100%;background:{{ $t['color'] }};border-radius:99px;"></div>
                        </div>
                        <div class="jsi-pct">{{ $pct }}%</div>
                    </div>
                @endforeach
            </div>

            {{-- Footer --}}
            <div class="jsi-footer">
                <div style="display:flex;align-items:center;gap:.625rem;flex-wrap:wrap;">
                    @foreach($jsiCounts as $t)
                        <span class="jsi-footer-lbl" style="display:inline-flex;align-items:center;gap:.3rem;">
                            <span style="width:7px;height:7px;border-radius:50%;background:{{ $t['color'] }};flex-shrink:0;"></span>
                            <span class="jsi-footer-cnt">{{ $t['count'] }}</span> {{ $t['label'] }}
                        </span>
                        @if(!$loop->last)<span style="color:#e2e8f0;font-size:.55rem;" class="dark:text-gray-700">|</span>@endif
                    @endforeach
                </div>
                <span style="font-size:.62rem;color:#94a3b8;">{{ now()->format('d M Y, H:i') }}</span>
            </div>
        </div>


        {{-- ══════════════════════════════════════════════════════════════ --}}
        {{-- WIDGETS GRID (Charts & Tables)                                 --}}
        {{-- ══════════════════════════════════════════════════════════════ --}}
        <x-filament-widgets::widgets
            :widgets="$this->getWidgets()"
            :columns="$this->getColumns()"
        />

    </div>

</x-filament-panels::page>
