@props(['reportId', 'title', 'message'])

<div class="rounded-xl border border-indigo-200 dark:border-indigo-500/20 bg-gradient-to-r from-indigo-50 to-purple-50 dark:from-indigo-900/10 dark:to-purple-900/10 p-5 space-y-3">
    <div class="flex items-start gap-3">
        <div class="shrink-0 rounded-lg bg-indigo-100 dark:bg-indigo-500/20 p-2">
            <x-heroicon-o-chart-bar class="h-5 w-5 text-indigo-600 dark:text-indigo-400" />
        </div>
        <div class="flex-1">
            <h3 class="text-sm font-semibold text-indigo-900 dark:text-indigo-200 mb-1">
                📊 {{ $title }}
            </h3>
            <p class="text-sm text-indigo-800 dark:text-indigo-300 leading-relaxed">
                {{ $message }}
            </p>
        </div>
    </div>
    
    <div class="flex items-center gap-2 pt-2 border-t border-indigo-200 dark:border-indigo-500/20">
        <button
            type="button"
            wire:click="viewReport({{ $reportId }})"
            class="inline-flex items-center gap-1.5 rounded-lg px-4 py-2 text-sm font-semibold bg-indigo-600 text-white hover:bg-indigo-500 transition-all shadow-sm"
        >
            <x-heroicon-o-eye class="h-4 w-4" />
            Open Report
        </button>
        
        <button
            type="button"
            wire:click="saveReport({{ $reportId }})"
            class="inline-flex items-center gap-1.5 rounded-lg px-3 py-2 text-sm font-medium bg-white dark:bg-white/5 text-gray-700 dark:text-gray-300 ring-1 ring-gray-200 dark:ring-white/10 hover:bg-gray-50 dark:hover:bg-white/10 transition-all"
        >
            <x-heroicon-o-bookmark class="h-4 w-4" />
            Save
        </button>
        
        <button
            type="button"
            wire:click="exportReportCsv({{ $reportId }})"
            class="inline-flex items-center gap-1.5 rounded-lg px-3 py-2 text-sm font-medium bg-white dark:bg-white/5 text-gray-700 dark:text-gray-300 ring-1 ring-gray-200 dark:ring-white/10 hover:bg-gray-50 dark:hover:bg-white/10 transition-all"
        >
            <x-heroicon-o-arrow-down-tray class="h-4 w-4" />
            Export CSV
        </button>
    </div>
</div>
