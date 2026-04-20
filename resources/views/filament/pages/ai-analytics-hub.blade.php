<x-filament-panels::page>
    <style>
        /* Maximize vertical layout spacing by overriding Filament main content wrappers */
        .fi-main { padding-bottom: 0 !important; }
        .fi-page { padding-bottom: 0 !important; gap: 0 !important; }
        footer { display: none !important; }
    </style>

    <div class="flex h-[calc(100vh-6rem)] bg-white dark:bg-gray-900 rounded border border-gray-200 dark:border-gray-800 overflow-hidden relative shadow-sm mt-0 -mb-8">
        
        <!-- Sidebar: Conversation List -->
        @if($isSidebarOpen)
        <div class="w-72 border-r border-gray-200 dark:border-gray-800 bg-gray-50/50 dark:bg-gray-900/50 flex flex-col hidden md:flex shrink-0">
            <div class="p-4 border-b border-gray-200 dark:border-gray-800">
                <x-filament::button wire:click="startNewChat" class="w-full" icon="heroicon-o-pencil-square" color="gray">
                    New Chat
                </x-filament::button>
            </div>
            <div class="flex-1 overflow-y-auto p-3 space-y-1">
                @if($activeTab === 'chats')
                    <div class="text-xs font-semibold text-gray-500 uppercase tracking-wider mb-2 px-2">Recent Chats</div>
                    @forelse($conversations as $convo)
                        <div class="flex items-center group/item relative">
                            <button wire:click="loadChat('{{ $convo['id'] }}')" class="flex-1 text-left pl-3 pr-8 py-2 text-sm rounded-lg transition {{ $conversationId === $convo['id'] ? 'bg-primary-500/10 text-primary-600 dark:text-primary-400 font-medium' : 'text-gray-600 dark:text-gray-400 hover:bg-gray-100 dark:hover:bg-gray-800/50' }} truncate">
                                <x-filament::icon icon="heroicon-o-chat-bubble-left" class="w-4 h-4 inline-block mr-2 opacity-50" />
                                {{ $convo['title'] ?? 'New Conversation' }}
                            </button>
                            <button wire:click.stop="deleteChat('{{ $convo['id'] }}')" 
                                wire:confirm="Are you sure you want to delete this conversation?"
                                class="absolute right-2 p-1 text-gray-400 hover:text-red-500 opacity-0 group-hover/item:opacity-100 transition-opacity rounded hover:bg-gray-200 dark:hover:bg-gray-700" title="Delete Chat">
                                <x-filament::icon icon="heroicon-o-trash" class="w-4 h-4" />
                            </button>
                        </div>
                    @empty
                        <div class="px-3 py-2 text-xs text-gray-500 italic">No previous chats.</div>
                    @endforelse
                @elseif($activeTab === 'reports')
                    <div class="text-xs font-semibold text-gray-500 uppercase tracking-wider mb-2 px-2">Saved Reports</div>
                    @forelse($reports as $report)
                        <div class="flex items-center group/item relative">
                            <button wire:click="viewReport({{ $report['id'] }})" class="flex-1 text-left pl-3 pr-8 py-2 text-sm rounded-lg transition text-gray-600 dark:text-gray-400 hover:bg-gray-100 dark:hover:bg-gray-800/50 truncate">
                                <x-filament::icon icon="heroicon-o-chart-bar" class="w-4 h-4 inline-block mr-2 opacity-50" />
                                {{ $report['title'] }}
                            </button>
                            <button wire:click.stop="deleteReport({{ $report['id'] }})" 
                                wire:confirm="Are you sure you want to delete this report?"
                                class="absolute right-2 p-1 text-gray-400 hover:text-red-500 opacity-0 group-hover/item:opacity-100 transition-opacity rounded hover:bg-gray-200 dark:hover:bg-gray-700" title="Delete Report">
                                <x-filament::icon icon="heroicon-o-trash" class="w-4 h-4" />
                            </button>
                        </div>
                    @empty
                        <div class="px-3 py-2 text-xs text-gray-500 italic">No saved reports yet.</div>
                    @endforelse
                @elseif($activeTab === 'plans')
                    <div class="text-xs font-semibold text-gray-500 uppercase tracking-wider mb-2 px-2">Saved Plans</div>
                    @if(empty($plans))
                        <div class="px-3 py-2 text-xs text-gray-500 italic">No saved plans yet.</div>
                    @endif
                @endif
            </div>
        </div>
        @endif

        <!-- Main Chat Area -->
        <div class="flex-1 flex flex-col min-w-0 bg-white dark:bg-gray-900 transition-all duration-300">
            <!-- Header Tabs (Left Aligned) -->
            <div class="px-4 py-2 border-b border-gray-200 dark:border-gray-800 bg-white dark:bg-gray-900 flex justify-start items-center gap-3">
                <button wire:click="toggleSidebar" class="p-1.5 -ml-1.5 rounded-lg text-gray-400 hover:text-gray-600 dark:hover:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-800 transition focus:outline-none" title="Toggle Sidebar">
                    <x-filament::icon icon="heroicon-o-bars-3" class="w-5 h-5" />
                </button>
                <x-filament::tabs>
                    <x-filament::tabs.item wire:click="setTab('chats')" :active="$activeTab === 'chats'" class="font-semibold {{ $activeTab === 'chats' ? '!text-primary-600' : '' }}">Chats</x-filament::tabs.item>
                    <x-filament::tabs.item wire:click="setTab('reports')" :active="$activeTab === 'reports'" class="font-semibold {{ $activeTab === 'reports' ? '!text-primary-600' : '' }}">Reports</x-filament::tabs.item>
                    <x-filament::tabs.item wire:click="setTab('plans')" :active="$activeTab === 'plans'" class="font-semibold {{ $activeTab === 'plans' ? '!text-primary-600' : '' }}">Plans</x-filament::tabs.item>
                </x-filament::tabs>
            </div>

            @if($activeTab === 'chats')
                <!-- Messages Area -->
                <div class="flex-1 p-6 overflow-y-auto space-y-6" id="chat-messages"
                    x-data="{ autoScroll() { this.$el.scrollTop = this.$el.scrollHeight; } }"
                    x-init="
                        autoScroll();
                        $watch('messages', () => { setTimeout(() => autoScroll(), 50); });
                        Livewire.hook('commit', ({ succeed }) => {
                            succeed(() => { setTimeout(() => autoScroll(), 50); });
                        });
                    ">
                    @if(empty($messages))
                        <div class="h-full flex flex-col items-center justify-center text-gray-400">
                            <x-filament::icon icon="heroicon-o-sparkles" class="w-12 h-12 mb-3 text-primary-500 opacity-50"/>
                            <p class="text-lg font-medium text-gray-700 dark:text-gray-300">How can I help you manage your ERP today?</p>
                            <p class="text-sm mt-1">Start typing below to generate reports or query manuals.</p>
                            
                            <div class="flex flex-wrap items-center justify-center gap-3 mt-8 max-w-2xl">
                                <button wire:click="setPrompt('Show me the recruitment SOP')" class="text-sm px-4 py-2 rounded-full border border-gray-200 dark:border-gray-700 hover:border-primary-500 hover:text-primary-600 dark:hover:text-primary-400 bg-white dark:bg-gray-800 shadow-sm transition">
                                    Show me the recruitment SOP
                                </button>
                                <button wire:click="setPrompt('What is the procurement process?')" class="text-sm px-4 py-2 rounded-full border border-gray-200 dark:border-gray-700 hover:border-primary-500 hover:text-primary-600 dark:hover:text-primary-400 bg-white dark:bg-gray-800 shadow-sm transition">
                                    What is the procurement process?
                                </button>
                                <button wire:click="setPrompt('How do I create a purchase order?')" class="text-sm px-4 py-2 rounded-full border border-gray-200 dark:border-gray-700 hover:border-primary-500 hover:text-primary-600 dark:hover:text-primary-400 bg-white dark:bg-gray-800 shadow-sm transition">
                                    How do I create a purchase order?
                                </button>
                                <button wire:click="setPrompt('Provide a summary of the leave attendance workflow.')" class="text-sm px-4 py-2 rounded-full border border-gray-200 dark:border-gray-700 hover:border-primary-500 hover:text-primary-600 dark:hover:text-primary-400 bg-white dark:bg-gray-800 shadow-sm transition">
                                    Summarize leave attendance
                                </button>
                            </div>
                        </div>
                    @endif

                    @foreach($messages as $index => $msg)
                        <div x-data="{ editing: false, editPromptText: {{ json_encode($msg['content'] ?? '') }} }" 
                             class="flex {{ $msg['role'] === 'user' ? 'justify-end' : 'justify-start' }} relative mb-8 group/msg">
                            
                            @if($msg['role'] === 'user')
                                <div class="flex-col pb-6 max-w-[85%]">
                                    <div x-show="!editing" class="rounded-2xl px-5 py-3 bg-primary-600 text-white shadow-md prose dark:prose-invert max-w-full">
                                        @if(isset($msg['image']) && isset($msg['image']['path']))
                                            <div class="mb-3">
                                                <img src="data:{{ $msg['image']['mime'] }};base64,{{ base64_encode(file_get_contents($msg['image']['path'])) }}" class="max-w-[200px] rounded-lg border border-primary-500 shadow-sm" alt="Attached Image">
                                            </div>
                                        @endif
                                        {!! \Illuminate\Support\Str::markdown(e($msg['content'])) !!}
                                    </div>
                                    
                                    <!-- Edit View -->
                                    <div x-show="editing" x-cloak class="w-full min-w-[300px] bg-gray-50 dark:bg-gray-800 rounded-xl p-3 shadow-md border border-gray-200 dark:border-gray-700">
                                        <textarea x-model="editPromptText" class="w-full text-sm bg-transparent border-none focus:ring-0 resize-none rounded-lg dark:text-gray-200" rows="3"></textarea>
                                        <div class="flex justify-end gap-2 mt-2">
                                            <button @click="editing = false" class="text-xs px-3 py-1 rounded bg-gray-200 text-gray-700 hover:bg-gray-300 transition">Cancel</button>
                                            <button @click="if (editPromptText && editPromptText.trim()) { $wire.editPrompt({{ $index }}, editPromptText); editing = false; }" class="text-xs px-3 py-1 rounded bg-primary-600 text-white hover:bg-primary-500 transition">Save & Submit</button>
                                        </div>
                                    </div>

                                    <!-- User Action Bar -->
                                    <div x-show="!editing" class="absolute -bottom-6 right-2 flex items-center gap-2 opacity-0 group-hover/msg:opacity-100 transition-opacity">
                                        <button @click="editing = true" class="text-xs text-gray-400 hover:text-gray-600 dark:hover:text-gray-300 p-1 rounded hover:bg-gray-100 dark:hover:bg-gray-800 transition" title="Edit">
                                            <x-filament::icon icon="heroicon-o-pencil" class="w-3.5 h-3.5" />
                                        </button>
                                        <button wire:click="copyMessage({{ $index }})" class="text-xs text-gray-400 hover:text-gray-600 dark:hover:text-gray-300 p-1 rounded hover:bg-gray-100 dark:hover:bg-gray-800 transition" title="Copy">
                                            <x-filament::icon icon="heroicon-o-clipboard" class="w-3.5 h-3.5" />
                                        </button>
                                    </div>
                                </div>
                            @else
                                <div class="flex-col pb-6 max-w-[85%]">
                                    @if(isset($msg['type']) && $msg['type'] === 'report')
                                        {{-- Report Link Component --}}
                                        <x-ai-report-link 
                                            :reportId="$msg['metadata']['report_id']" 
                                            :title="$msg['metadata']['title']" 
                                            :message="$msg['content']" 
                                        />
                                    @else
                                        {{-- Regular Text Message --}}
                                        <div class="rounded-2xl px-5 py-3 bg-gray-100 dark:bg-gray-800 text-gray-900 dark:text-gray-100 shadow-sm prose dark:prose-invert max-w-full">
                                            {!! \Illuminate\Support\Str::markdown(e($msg['content'])) !!}
                                        </div>
                                    @endif

                                    <!-- AI Action Bar -->
                                    <div class="absolute -bottom-6 left-2 flex items-center gap-2 opacity-0 group-hover/msg:opacity-100 transition-opacity">
                                        <button wire:click="copyMessage({{ $index }})" class="text-xs text-gray-400 hover:text-gray-600 dark:hover:text-gray-300 p-1 rounded hover:bg-gray-100 dark:hover:bg-gray-800 transition" title="Copy">
                                            <x-filament::icon icon="heroicon-o-clipboard-document" class="w-3.5 h-3.5" />
                                        </button>
                                        <button wire:click="regenerateResponse({{ $index }})" class="text-xs text-gray-400 hover:text-gray-600 dark:hover:text-gray-300 p-1 rounded hover:bg-gray-100 dark:hover:bg-gray-800 transition" title="Regenerate">
                                            <x-filament::icon icon="heroicon-o-arrow-path" class="w-3.5 h-3.5" />
                                        </button>
                                    </div>
                                </div>
                            @endif
                            
                            @if(isset($msg['timestamp']))
                                <div class="absolute -bottom-5 {{ $msg['role'] === 'user' ? 'right-20' : 'left-20' }} text-[10px] text-gray-400 opacity-0 group-hover/msg:opacity-100 transition-opacity">
                                    {{ \Carbon\Carbon::parse($msg['timestamp'])->format('H:i') }}
                                </div>
                            @endif
                            
                            @if($loop->last && $msg['role'] === 'user')
                                <div class="absolute -bottom-0 right-0 z-10 w-full flex justify-center mt-2">
                                    <button wire:click.prevent="retry" wire:loading.attr="disabled" class="text-xs font-semibold text-primary-600 dark:text-primary-400 bg-white dark:bg-gray-800 px-3 py-1.5 rounded-full shadow-sm border border-gray-200 dark:border-gray-700 hover:bg-gray-50 focus:outline-none flex items-center gap-1 transition">
                                        <x-filament::icon icon="heroicon-m-arrow-path" class="w-3.5 h-3.5" wire:loading.class="animate-spin" />
                                        Continue / Retry
                                    </button>
                                </div>
                            @endif
                        </div>
                    @endforeach

                    <!-- AI Typing Indicator -->
                    <div wire:loading wire:target="sendMessage, retry" class="flex justify-start w-full mb-6">
                        <div class="rounded-2xl px-5 py-3.5 max-w-[85%] bg-gray-100 dark:bg-gray-800 text-gray-900 dark:text-gray-100 shadow-sm flex items-center gap-4">
                            <x-filament::icon icon="heroicon-o-sparkles" class="w-5 h-5 text-primary-500 animate-pulse" />
                            <span class="text-sm font-medium text-gray-600 dark:text-gray-300">The AI is querying the ERP...</span>
                            
                            <div class="flex space-x-1.5 ml-1">
                                <div class="w-1.5 h-1.5 bg-gray-400 rounded-full animate-bounce" style="animation-delay: -0.3s"></div>
                                <div class="w-1.5 h-1.5 bg-gray-400 rounded-full animate-bounce" style="animation-delay: -0.15s"></div>
                                <div class="w-1.5 h-1.5 bg-gray-400 rounded-full animate-bounce"></div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Input Area -->
                <div class="p-4 bg-white dark:bg-gray-900 border-t border-gray-200 dark:border-gray-800">
                    <!-- Image Preview -->
                    @if($fileUpload)
                        <div class="max-w-4xl mx-auto mb-3 flex items-center gap-3">
                            <div class="relative inline-block">
                                <img src="{{ $fileUpload->temporaryUrl() }}" class="h-16 w-16 object-cover rounded shadow-sm border border-gray-200 dark:border-gray-700">
                                <button type="button" wire:click="$set('fileUpload', null)" class="absolute -top-2 -right-2 bg-red-500 text-white rounded-full p-0.5 hover:bg-red-600 shadow transition">
                                    <x-filament::icon icon="heroicon-m-x-mark" class="w-3 h-3" />
                                </button>
                            </div>
                            <span class="text-xs text-gray-500">{{ $fileUpload->getClientOriginalName() }}</span>
                        </div>
                    @endif
                    
                    <form wire:submit="sendMessage" class="flex gap-3 items-center max-w-4xl mx-auto">
                        <!-- Image Upload Button -->
                        <div class="relative">
                            <input type="file" wire:model="fileUpload" id="chat-image-upload" class="hidden" accept="image/*">
                            <label for="chat-image-upload" class="cursor-pointer p-2.5 rounded-lg text-gray-400 hover:text-primary-600 hover:bg-primary-50 dark:hover:bg-primary-900/30 transition flex items-center justify-center">
                                <x-filament::icon icon="heroicon-o-paper-clip" class="w-5 h-5" />
                            </label>
                        </div>
                        
                        <div class="flex-1 relative shadow-sm rounded-lg flex items-center">
                            <div wire:loading wire:target="fileUpload" class="absolute right-3">
                                <x-filament::icon icon="heroicon-m-arrow-path" class="w-4 h-4 text-primary-500 animate-spin" />
                            </div>
                            <x-filament::input.wrapper class="w-full">
                                <x-filament::input
                                    type="text"
                                    wire:model="prompt"
                                    placeholder="Message the AI Assistant..."
                                    class="w-full text-base py-3"
                                />
                            </x-filament::input.wrapper>
                        </div>
                        <x-filament::button type="submit" size="xl" icon="heroicon-m-arrow-up" color="gray" class="!rounded-lg !px-4"
                            x-bind:disabled="$wire.prompt === '' && !$wire.fileUpload">
                            Send
                        </x-filament::button>
                    </form>
                </div>
            @elseif($activeTab === 'reports')
                <div class="flex-1 overflow-y-auto p-6">
                    @if(empty($reports))
                        <div class="flex flex-col items-center justify-center h-full text-gray-400">
                            <x-filament::icon icon="heroicon-o-chart-bar" class="w-16 h-16 mb-4 text-gray-300 dark:text-gray-600"/>
                            <p class="text-lg font-medium text-gray-700 dark:text-gray-300">No Saved Reports</p>
                            <p class="text-sm mt-2 text-center max-w-md">Start a new AI chat to generate dynamic dashboards from your ERP data and save them here.</p>
                            <x-filament::button wire:click="startNewChat" class="mt-4" icon="heroicon-o-plus" color="primary">
                                Start New Chat
                            </x-filament::button>
                        </div>
                    @else
                        <div class="max-w-5xl mx-auto space-y-4">
                            <div class="flex items-center justify-between mb-6">
                                <h2 class="text-xl font-semibold text-gray-900 dark:text-white">Saved Reports</h2>
                                <x-filament::button wire:click="startNewChat" size="sm" icon="heroicon-o-plus" color="gray">
                                    New Report
                                </x-filament::button>
                            </div>
                            
                            @foreach($reports as $report)
                                <div class="rounded-xl border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800 p-5 hover:shadow-md transition-shadow">
                                    <div class="flex items-start justify-between">
                                        <div class="flex-1">
                                            <div class="flex items-center gap-2 mb-2">
                                                <x-heroicon-o-chart-bar class="h-5 w-5 text-indigo-500" />
                                                <h3 class="text-lg font-semibold text-gray-900 dark:text-white">
                                                    {{ $report['title'] }}
                                                </h3>
                                            </div>
                                            <div class="flex items-center gap-4 text-sm text-gray-500 dark:text-gray-400">
                                                <span class="inline-flex items-center gap-1">
                                                    <x-heroicon-o-tag class="h-4 w-4" />
                                                    {{ ucfirst($report['module']) }}
                                                </span>
                                                <span class="inline-flex items-center gap-1">
                                                    <x-heroicon-o-calendar class="h-4 w-4" />
                                                    {{ $report['created_at'] }}
                                                </span>
                                            </div>
                                        </div>
                                        
                                        <div class="flex items-center gap-2">
                                            <x-filament::button 
                                                wire:click="viewReport({{ $report['id'] }})" 
                                                size="sm" 
                                                icon="heroicon-o-eye"
                                                color="primary"
                                            >
                                                View
                                            </x-filament::button>
                                            
                                            <x-filament::button 
                                                wire:click="exportReportPdf({{ $report['id'] }})" 
                                                size="sm" 
                                                icon="heroicon-o-document-arrow-down"
                                                color="gray"
                                                outlined
                                            >
                                                PDF
                                            </x-filament::button>
                                            
                                            <x-filament::button 
                                                wire:click="exportReportCsv({{ $report['id'] }})" 
                                                size="sm" 
                                                icon="heroicon-o-arrow-down-tray"
                                                color="gray"
                                                outlined
                                            >
                                                CSV
                                            </x-filament::button>
                                            
                                            <x-filament::button 
                                                wire:click="deleteReport({{ $report['id'] }})" 
                                                wire:confirm="Are you sure you want to delete this report?"
                                                size="sm" 
                                                icon="heroicon-o-trash"
                                                color="danger"
                                                outlined
                                            >
                                                Delete
                                            </x-filament::button>
                                        </div>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    @endif
                </div>
            @elseif($activeTab === 'plans')
                 <div class="flex-1 flex flex-col items-center justify-center text-gray-400 p-6">
                    <x-filament::icon icon="heroicon-o-queue-list" class="w-16 h-16 mb-4 text-gray-300 dark:text-gray-600"/>
                    <p class="text-lg font-medium text-gray-700 dark:text-gray-300">Execution Plans</p>
                    <p class="text-sm mt-2 text-center max-w-md">Ask the AI to create a step-by-step checklist or hiring plan, and save it here to track progression.</p>
                </div>
            @endif
        </div>
    </div>
    
    {{-- Report Modals (rendered for each report in messages) --}}
    @foreach($messages as $msg)
        @if(isset($msg['type']) && $msg['type'] === 'report' && isset($msg['metadata']['report_id']))
            <x-report-modal :reportId="$msg['metadata']['report_id']" />
        @endif
    @endforeach
    
    <script>
        document.addEventListener('livewire:initialized', () => {
            Livewire.on('clipboard-copy', (event) => {
                const text = event[0]?.content || '';
                if(navigator.clipboard) {
                    navigator.clipboard.writeText(text);
                }
            });
        });
    </script>
</x-filament-panels::page>
