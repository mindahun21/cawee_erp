<div class="mb-4 flex flex-wrap gap-2">
    @foreach(\App\Filament\Resources\HR\Reports\HrReportResource::getReportNavLinks() as $link)
        <a href="{{ route($link['route']) }}"
           class="px-4 py-2 rounded-lg text-sm font-medium transition-all {{ request()->routeIs($link['route'])
                ? 'bg-primary-600 text-white shadow-md'
                : 'bg-white dark:bg-gray-800 text-gray-600 dark:text-gray-300 border border-gray-200 dark:border-gray-700 hover:bg-gray-50 dark:hover:bg-gray-700' }}">
            {{ $link['label'] }}
        </a>
    @endforeach
</div>
