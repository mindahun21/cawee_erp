<x-filament-panels::page>
    <div class="flex gap-4 overflow-x-auto pb-4 custom-scrollbar items-start" 
         x-data="kanbanBoard()"
         x-init="initKanban()">
        
        @foreach($this->getStatuses() as $statusKey => $details)
            <div class="flex-shrink-0 w-80 bg-gray-50 dark:bg-gray-800/80 rounded-xl overflow-hidden shadow-sm flex flex-col max-h-[75vh]"
                 data-status="{{ $statusKey }}"
                 @dragover.prevent="onDragOver($event)"
                 @drop.prevent="onDrop($event, '{{ $statusKey }}')">
                 
                <div class="p-3 text-sm font-bold border-b {{ $details['color'] }}">
                    {{ $details['label'] }}
                    <span class="ml-2 text-xs opacity-70">
                        {{ count($this->getRecords()[$statusKey]) }}
                    </span>
                </div>
                
                <div class="p-3 space-y-3 overflow-y-auto flex-1 custom-scrollbar min-h-[150px]">
                    @foreach($this->getRecords()[$statusKey] as $app)
                        <div class="bg-white dark:bg-gray-900 border border-gray-200 dark:border-gray-700 rounded-lg p-3 shadow-sm cursor-grab hover:ring-2 hover:ring-primary-500 transition-all"
                             draggable="true"
                             data-id="{{ $app->id }}"
                             @dragstart="onDragStart($event, {{ $app->id }}, '{{ $statusKey }}')"
                             @dragend="onDragEnd($event)">
                            
                            <a href="{{ \App\Filament\Resources\Recruitment\RecruitmentApplications\RecruitmentApplicationResource::getUrl('view', ['record' => $app]) }}" class="block w-full">
                                <div class="flex justify-between items-start mb-2">
                                    <span class="text-xs font-semibold text-primary-600 truncate mr-2">
                                        {{ $app->application_code ?? 'APP-'.$app->id }}
                                    </span>
                                </div>
                                <div class="flex items-center gap-2 mb-2">
                                    <div class="w-8 h-8 rounded-full bg-gray-200 dark:bg-gray-700 flex items-center justify-center text-gray-500">
                                        <x-heroicon-o-user class="w-5 h-5"/>
                                    </div>
                                    <div class="overflow-hidden">
                                        <div class="text-sm font-semibold truncate dark:text-gray-200">
                                            {{ $app->candidate?->first_name }} {{ $app->candidate?->last_name }}
                                        </div>
                                        <div class="text-xs text-gray-500 truncate">
                                            {{ $app->candidate?->email }}
                                        </div>
                                        <div class="text-xs text-gray-500 truncate">
                                            {{ $app->candidate?->phone ?? 'No Phone' }}
                                        </div>
                                    </div>
                                </div>
                                <div class="text-[10px] text-gray-400 mt-2 truncate border-t dark:border-gray-700 pt-2">
                                    {{ $app->campaign?->title ?? 'No Campaign' }}
                                </div>
                            </a>
                        </div>
                    @endforeach
                    
                    @if(count($this->getRecords()[$statusKey]) === 0)
                        <div class="text-center text-sm text-gray-400 dark:text-gray-500 py-4 opacity-50 font-medium">
                            No candidate found
                        </div>
                    @endif
                </div>
            </div>
        @endforeach
    </div>

    <script>
        document.addEventListener('alpine:init', () => {
            Alpine.data('kanbanBoard', () => ({
                draggedId: null,
                sourceStatus: null,
                
                initKanban() {
                    console.log('Kanban board initialized');
                },
                
                onDragStart(event, id, status) {
                    this.draggedId = id;
                    this.sourceStatus = status;
                    event.dataTransfer.effectAllowed = 'move';
                    event.target.classList.add('opacity-50');
                },
                
                onDragEnd(event) {
                    event.target.classList.remove('opacity-50');
                    this.draggedId = null;
                    this.sourceStatus = null;
                },
                
                onDragOver(event) {
                    event.dataTransfer.dropEffect = 'move';
                },
                
                onDrop(event, targetStatus) {
                    if (this.draggedId && this.sourceStatus !== targetStatus) {
                        @this.call('updateApplicationStatus', this.draggedId, targetStatus);
                    }
                }
            }));
        });
    </script>
    
    <style>
        .custom-scrollbar::-webkit-scrollbar { height: 8px; width: 6px; }
        .custom-scrollbar::-webkit-scrollbar-track { background: transparent; }
        .custom-scrollbar::-webkit-scrollbar-thumb { background: #cbd5e1; border-radius: 4px; }
        .dark .custom-scrollbar::-webkit-scrollbar-thumb { background: #475569; }
    </style>
</x-filament-panels::page>
