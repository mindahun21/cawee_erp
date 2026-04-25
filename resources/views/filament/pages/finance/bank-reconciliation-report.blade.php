<x-filament-panels::page>
    <div class="space-y-6">

        {{-- ── KPI Summary Strip ─────────────────────────────────────────────── --}}
        <div class="grid grid-cols-2 gap-4 sm:grid-cols-4">
            {{-- Total Reconciliations --}}
            <div class="rounded-xl border border-gray-200 bg-white dark:border-white/10 dark:bg-white/5 p-4 shadow-sm">
                <div class="flex items-start gap-3">
                    <div class="shrink-0 rounded-lg bg-primary-50 dark:bg-primary-500/10 p-2">
                        <x-filament::icon icon="heroicon-o-scale" class="h-5 w-5 text-primary-600 dark:text-primary-400" />
                    </div>
                    <div class="min-w-0">
                        <p class="text-xs font-medium text-gray-500 dark:text-gray-400">Total Reconciliations</p>
                        <p class="mt-0.5 text-xl font-bold font-mono text-primary-600 dark:text-primary-400">
                            {{ number_format($totalReconciliations) }}
                        </p>
                    </div>
                </div>
            </div>

            {{-- Reconciled --}}
            <div class="rounded-xl border border-gray-200 bg-white dark:border-white/10 dark:bg-white/5 p-4 shadow-sm">
                <div class="flex items-start gap-3">
                    <div class="shrink-0 rounded-lg bg-emerald-50 dark:bg-emerald-500/10 p-2">
                        <x-filament::icon icon="heroicon-o-check-badge" class="h-5 w-5 text-emerald-600 dark:text-emerald-400" />
                    </div>
                    <div class="min-w-0">
                        <p class="text-xs font-medium text-gray-500 dark:text-gray-400">Reconciled</p>
                        <p class="mt-0.5 text-xl font-bold font-mono text-emerald-600 dark:text-emerald-400">
                            {{ number_format($reconciledCount) }}
                        </p>
                    </div>
                </div>
            </div>

            {{-- In Progress --}}
            <div class="rounded-xl border border-gray-200 bg-white dark:border-white/10 dark:bg-white/5 p-4 shadow-sm">
                <div class="flex items-start gap-3">
                    <div class="shrink-0 rounded-lg bg-amber-50 dark:bg-amber-500/10 p-2">
                        <x-filament::icon icon="heroicon-o-clock" class="h-5 w-5 text-amber-600 dark:text-amber-400" />
                    </div>
                    <div class="min-w-0">
                        <p class="text-xs font-medium text-gray-500 dark:text-gray-400">In Progress</p>
                        <p class="mt-0.5 text-xl font-bold font-mono text-amber-600 dark:text-amber-400">
                            {{ number_format($inProgressCount) }}
                        </p>
                    </div>
                </div>
            </div>

            {{-- Locked --}}
            <div class="rounded-xl border border-gray-200 bg-white dark:border-white/10 dark:bg-white/5 p-4 shadow-sm">
                <div class="flex items-start gap-3">
                    <div class="shrink-0 rounded-lg bg-blue-50 dark:bg-blue-500/10 p-2">
                        <x-filament::icon icon="heroicon-o-lock-closed" class="h-5 w-5 text-blue-600 dark:text-blue-400" />
                    </div>
                    <div class="min-w-0">
                        <p class="text-xs font-medium text-gray-500 dark:text-gray-400">Locked</p>
                        <p class="mt-0.5 text-xl font-bold font-mono text-blue-600 dark:text-blue-400">
                            {{ number_format($lockedCount) }}
                        </p>
                    </div>
                </div>
            </div>
        </div>

        {{-- ── Latest Reconciliation per Account Cards ─────────────────────── --}}
        @if($latestPerAccount->isNotEmpty())
        <div class="rounded-xl border border-gray-200 bg-white dark:border-white/10 dark:bg-white/5 shadow-sm overflow-hidden">
            <div class="flex items-center gap-2 border-b border-gray-100 dark:border-white/10 px-5 py-3">
                <x-filament::icon icon="heroicon-o-building-library" class="h-4 w-4 text-gray-400" />
                <h3 class="text-sm font-semibold text-gray-800 dark:text-gray-200">Latest Status Per Bank Account</h3>
            </div>
            <div class="grid grid-cols-1 gap-px bg-gray-100 dark:bg-white/5 sm:grid-cols-2 lg:grid-cols-3">
                @foreach($latestPerAccount as $rec)
                <div class="bg-white dark:bg-gray-900 px-5 py-4">
                    <div class="flex items-start justify-between gap-2">
                        <div class="min-w-0">
                            <p class="truncate text-sm font-semibold text-gray-900 dark:text-gray-100">
                                {{ $rec->bankAccount?->account_name ?? '—' }}
                            </p>
                            <p class="truncate text-xs text-gray-500 dark:text-gray-400">
                                {{ $rec->bankAccount?->bank_name }} &mdash; {{ $rec->bankAccount?->account_number }}
                            </p>
                        </div>
                        <span @class([
                            'shrink-0 inline-flex items-center rounded-full px-2 py-0.5 text-xs font-medium',
                            'bg-emerald-100 text-emerald-700 dark:bg-emerald-900/30 dark:text-emerald-400' => $rec->status === 'reconciled',
                            'bg-amber-100 text-amber-700 dark:bg-amber-900/30 dark:text-amber-400'         => $rec->status === 'in_progress',
                            'bg-blue-100 text-blue-700 dark:bg-blue-900/30 dark:text-blue-400'             => $rec->status === 'locked',
                            'bg-gray-100 text-gray-600 dark:bg-gray-800 dark:text-gray-300'                => !in_array($rec->status, ['reconciled','in_progress','locked']),
                        ])>
                            {{ \App\Models\Finance\BankReconciliation::statuses()[$rec->status] ?? $rec->status }}
                        </span>
                    </div>
                    <div class="mt-3 grid grid-cols-2 gap-2 text-xs">
                        <div>
                            <span class="text-gray-400">Statement Date</span>
                            <p class="font-mono font-medium text-gray-700 dark:text-gray-300">
                                {{ $rec->statement_date?->format('d M Y') ?? '—' }}
                            </p>
                        </div>
                        <div>
                            <span class="text-gray-400">Period</span>
                            <p class="font-medium text-gray-700 dark:text-gray-300">
                                {{ $rec->period?->name ?? '—' }}
                            </p>
                        </div>
                        <div>
                            <span class="text-gray-400">Stmt Balance</span>
                            <p class="font-mono font-medium text-blue-600 dark:text-blue-400">
                                {{ number_format((float)$rec->statement_balance, 2) }}
                            </p>
                        </div>
                        <div>
                            <span class="text-gray-400">Difference</span>
                            <p @class([
                                'font-mono font-bold',
                                'text-emerald-600 dark:text-emerald-400' => abs((float)$rec->difference) < 0.01,
                                'text-red-600 dark:text-red-400'         => abs((float)$rec->difference) >= 0.01,
                            ])>
                                {{ number_format((float)$rec->difference, 2) }}
                            </p>
                        </div>
                    </div>
                </div>
                @endforeach
            </div>
        </div>
        @endif

        {{-- ── Unreconciled Accounts Alert ─────────────────────────────────── --}}
        @if($unreconciledAccounts->isNotEmpty())
        <div class="rounded-xl border border-amber-200 bg-amber-50 dark:border-amber-800/50 dark:bg-amber-900/10 p-4">
            <div class="flex items-start gap-3">
                <x-filament::icon icon="heroicon-o-exclamation-triangle" class="mt-0.5 h-5 w-5 shrink-0 text-amber-500" />
                <div>
                    <p class="text-sm font-semibold text-amber-800 dark:text-amber-300">
                        {{ $unreconciledAccounts->count() }} reconciliation(s) still in progress
                    </p>
                    <p class="mt-1 text-xs text-amber-700 dark:text-amber-400">
                        The following bank accounts have open reconciliations that have not yet been finalised:
                        <span class="font-medium">
                            {{ $unreconciledAccounts->map(fn($r) => $r->bankAccount?->account_name ?? $r->reference)->implode(', ') }}
                        </span>
                    </p>
                </div>
            </div>
        </div>
        @endif

        {{-- ── Reconciliation Data Table ─────────────────────────────────── --}}
        <div class="rounded-xl border border-gray-200 dark:border-white/10 overflow-hidden shadow-sm">
            {{ $this->table }}
        </div>

    </div>
</x-filament-panels::page>
