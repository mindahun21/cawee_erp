<x-filament-panels::page>
    <div class="flex items-center justify-between mb-4">
        <h2 class="text-2xl font-bold tracking-tight text-gray-950 dark:text-white">
            {{ \Carbon\Carbon::parse($currentDate)->format('F Y') }}
        </h2>
        <div class="flex gap-2">
            <x-filament::button wire:click="previousMonth" color="gray" icon="heroicon-o-chevron-left" tooltip="Previous Month"></x-filament::button>
            <x-filament::button wire:click="today" color="gray">Today</x-filament::button>
            <x-filament::button wire:click="nextMonth" color="gray" icon="heroicon-o-chevron-right" tooltip="Next Month"></x-filament::button>
        </div>
    </div>

    <div class="grid grid-cols-7 gap-px bg-gray-200 dark:bg-gray-700 border border-gray-200 dark:border-gray-700 rounded-xl overflow-hidden shadow-sm">
        @foreach(['Sun', 'Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat'] as $day)
            <div class="bg-gray-50 dark:bg-gray-800 p-2 text-center text-sm font-semibold text-gray-500 dark:text-gray-400">
                {{ $day }}
            </div>
        @endforeach

        @foreach($this->getDays() as $day)
            <div class="min-h-[140px] bg-white dark:bg-gray-900 p-2 transition-colors {{ !$day['isCurrentMonth'] ? 'opacity-40 bg-gray-50 dark:bg-gray-800/50' : '' }} {{ $day['isToday'] ? 'bg-primary-50/50 dark:bg-primary-900/10' : '' }}">
                <div class="flex justify-between items-center mb-2">
                    <span class="inline-flex items-center justify-center w-7 h-7 text-sm rounded-full {{ $day['isToday'] ? 'font-bold bg-primary-600 text-white' : 'font-medium text-gray-700 dark:text-gray-200' }}">
                        {{ $day['date']->format('j') }}
                    </span>
                </div>
                <div class="space-y-1.5 overflow-y-auto max-h-[100px] pr-1 custom-scrollbar">
                    @foreach($day['schedules'] as $schedule)
                        <a href="{{ \App\Filament\Resources\Recruitment\RecruitmentInterviewSchedules\RecruitmentInterviewScheduleResource::getUrl('view', ['record' => $schedule]) }}" 
                           class="group block px-2 py-1.5 text-xs rounded-md shadow-sm border {{ $schedule->status === 'scheduled' ? 'bg-success-50 border-success-200 text-success-700 dark:bg-success-900/30 dark:border-success-800 dark:text-success-400' : 'bg-white border-gray-200 text-gray-700 dark:bg-gray-800 dark:border-gray-700 dark:text-gray-300' }} hover:ring-2 hover:ring-primary-500 transition-all cursor-pointer"
                           title="{{ $schedule->name }} ({{ \Carbon\Carbon::parse($schedule->from_time)->format('H:i') }})">
                            <div class="hidden group-hover:block mb-1">
                                <span class="px-1.5 py-0.5 text-[10px] font-medium rounded-full inline-block shadow-sm {{ ['draft'=>'bg-gray-100 text-gray-800','submitted'=>'bg-warning-100 text-warning-800','scheduled'=>'bg-success-100 text-success-800','completed'=>'bg-primary-100 text-primary-800','cancelled'=>'bg-danger-100 text-danger-800','rejected'=>'bg-danger-100 text-danger-800'][$schedule->status] ?? 'bg-gray-100 text-gray-800' }}">
                                    {{ ucfirst($schedule->status) }}
                                </span>
                            </div>
                            <div class="truncate">
                                <span class="font-semibold">{{ \Carbon\Carbon::parse($schedule->from_time)->format('H:i') }}</span>
                                <span class="ml-1 opacity-90">{{ $schedule->name }}</span>
                            </div>
                        </a>
                    @endforeach
                </div>
            </div>
        @endforeach
    </div>

    <style>
        .custom-scrollbar::-webkit-scrollbar {
            width: 4px;
        }
        .custom-scrollbar::-webkit-scrollbar-track {
            background: transparent;
        }
        .custom-scrollbar::-webkit-scrollbar-thumb {
            background: #cbd5e1;
            border-radius: 4px;
        }
        .dark .custom-scrollbar::-webkit-scrollbar-thumb {
            background: #475569;
        }
    </style>
</x-filament-panels::page>
