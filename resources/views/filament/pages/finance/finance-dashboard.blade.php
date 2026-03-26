<x-filament-panels::page>
    <div class="flex flex-col items-center justify-center min-h-[60vh] gap-4 text-center">
        <div class="rounded-full bg-primary-50 dark:bg-primary-950 p-6">
            <x-heroicon-o-chart-bar-square class="w-16 h-16 text-primary-500" />
        </div>
        <h2 class="text-2xl font-bold tracking-tight text-gray-900 dark:text-white">
            Finance Dashboard
        </h2>
        <p class="text-gray-500 dark:text-gray-400 max-w-sm">
            Financial KPIs, budget utilisation charts, and cash position summaries will appear here in the next phase.
        </p>
        <div class="flex gap-3 mt-2">
            <a href="{{ \App\Filament\Resources\Finance\Settings\AccountTypeResource::getUrl() }}"
               class="fi-btn fi-btn-color-primary fi-btn-size-md fi-btn-outlined inline-flex items-center gap-2 px-4 py-2 text-sm font-medium rounded-lg border border-primary-500 text-primary-600 hover:bg-primary-50 dark:hover:bg-primary-950 transition">
                <x-heroicon-o-cog-6-tooth class="w-4 h-4" />
                Go to Finance Settings
            </a>
        </div>
    </div>
</x-filament-panels::page>
