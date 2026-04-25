<x-filament-panels::page>
    <div class="flex flex-col gap-6">
        {{-- Header Section with Global Stats --}}
        <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
            <x-filament::section>
                <div class="flex items-center gap-4">
                    <div class="p-3 bg-primary-100 dark:bg-primary-900 rounded-lg text-primary-600">
                        <x-heroicon-o-clipboard-document-list class="w-6 h-6" />
                    </div>
                    <div>
                        <p class="text-sm text-gray-500 dark:text-gray-400">Total Strategic Plans</p>
                        <h4 class="text-2xl font-bold">{{ $stats['total_plans'] }}</h4>
                    </div>
                </div>
            </x-filament::section>

            <x-filament::section>
                <div class="flex items-center gap-4">
                    <div class="p-3 bg-success-100 dark:bg-success-900 rounded-lg text-success-600">
                        <x-heroicon-o-chart-bar class="w-6 h-6" />
                    </div>
                    <div>
                        <p class="text-sm text-gray-500 dark:text-gray-400">Average Execution %</p>
                        <h4 class="text-2xl font-bold">{{ $stats['avg_progress'] }}%</h4>
                    </div>
                </div>
            </x-filament::section>

            <x-filament::section>
                <div class="flex items-center gap-4">
                    <div class="p-3 bg-danger-100 dark:bg-danger-900 rounded-lg text-danger-600">
                        <x-heroicon-o-exclamation-triangle class="w-6 h-6" />
                    </div>
                    <div>
                        <p class="text-sm text-gray-500 dark:text-gray-400">Overdue Tasks</p>
                        <h4 class="text-2xl font-bold text-danger-600">{{ $stats['overdue_tasks'] }}</h4>
                    </div>
                </div>
            </x-filament::section>
        </div>

        {{-- Filters Form --}}
        <div class="bg-white dark:bg-gray-900 rounded-xl shadow-sm ring-1 ring-gray-950/5 dark:ring-white/10 p-4">
            <form wire:submit.prevent="loadReport">
                {{ $this->form }}
            </form>
        </div>

        {{-- Unified Tabs --}}
        <x-filament::tabs label="Planning Reports" class="w-full">
            <x-filament::tabs.item :active="$activeTab === 'kpis'" wire:click="setActiveTab('kpis')">
                KPI Performance
            </x-filament::tabs.item>
            <x-filament::tabs.item :active="$activeTab === 'plans'" wire:click="setActiveTab('plans')">
                Strategic Plans
            </x-filament::tabs.item>
            <x-filament::tabs.item :active="$activeTab === 'tasks'" wire:click="setActiveTab('tasks')">
                Task Execution
            </x-filament::tabs.item>
        </x-filament::tabs>

        {{-- The Data Table --}}
        <div class="fi-ta-container border border-gray-200 dark:border-white/10 rounded-xl overflow-hidden shadow-sm">
            {{ $this->table }}
        </div>

        {{-- Export Tools Section --}}
        <x-filament::section collapsible collapsed title="Advanced Exports">
            <div class="flex flex-col sm:flex-row gap-4 items-center justify-between">
                <div>
                    <h2 class="text-base font-semibold text-gray-950 dark:text-white">Reporting Controls</h2>
                    <p class="text-sm text-gray-500 dark:text-gray-400">Generate printable summaries or exported spreadsheets for stakeholders.</p>
                </div>
                <div class="flex flex-wrap gap-3">
                    <x-filament::button color="danger" icon="heroicon-m-document-arrow-down">Generate PDF</x-filament::button>
                    <x-filament::button color="success" icon="heroicon-m-table-cells">Export XLSX</x-filament::button>
                </div>
            </div>
        </x-filament::section>
    </div>
</x-filament-panels::page>
