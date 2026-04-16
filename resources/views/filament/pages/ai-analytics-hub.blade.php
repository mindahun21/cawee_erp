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
                        <button wire:click="loadChat('{{ $convo['id'] }}')" class="w-full text-left px-3 py-2 text-sm rounded-lg transition {{ $conversationId === $convo['id'] ? 'bg-primary-500/10 text-primary-600 dark:text-primary-400 font-medium' : 'text-gray-600 dark:text-gray-400 hover:bg-gray-100 dark:hover:bg-gray-800/50' }} truncate relative group">
                            <x-filament::icon icon="heroicon-o-chat-bubble-left" class="w-4 h-4 inline-block mr-2 opacity-50" />
                            {{ $convo['title'] ?? 'New Conversation' }}
                        </button>
                    @empty
                        <div class="px-3 py-2 text-xs text-gray-500 italic">No previous chats.</div>
                    @endforelse
                @elseif($activeTab === 'reports')
                    <div class="text-xs font-semibold text-gray-500 uppercase tracking-wider mb-2 px-2">Saved Reports</div>
                    @if(empty($reports))
                        <div class="px-3 py-2 text-xs text-gray-500 italic">No saved reports yet.</div>
                    @endif
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
                <div class="flex-1 p-6 overflow-y-auto space-y-6" id="chat-messages">
                    @if(empty($messages))
                        <div class="h-full flex flex-col items-center justify-center text-gray-400">
                            <x-filament::icon icon="heroicon-o-sparkles" class="w-12 h-12 mb-3 text-primary-500 opacity-50"/>
                            <p class="text-lg font-medium text-gray-700 dark:text-gray-300">How can I help you manage your ERP today?</p>
                            <p class="text-sm mt-1">Start typing below to generate reports or query manuals.</p>
                        </div>
                    @endif

                    @foreach($messages as $msg)
                        <div class="flex {{ $msg['role'] === 'user' ? 'justify-end' : 'justify-start' }} relative mb-6">
                            <div class="rounded-2xl px-5 py-3 max-w-[85%] {{ $msg['role'] === 'user' ? 'bg-primary-600 text-white shadow-md' : 'bg-gray-100 dark:bg-gray-800 text-gray-900 dark:text-gray-100 shadow-sm' }} prose dark:prose-invert max-w-full">
                                {!! \Illuminate\Support\Str::markdown($msg['content']) !!}
                            </div>
                            
                            @if($loop->last && $msg['role'] === 'user')
                                <div class="absolute -bottom-8 right-0">
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
                    <form wire:submit="sendMessage" class="flex gap-3 items-center max-w-4xl mx-auto">
                        <div class="flex-1 relative shadow-sm rounded-lg">
                            <x-filament::input.wrapper>
                                <x-filament::input
                                    type="text"
                                    wire:model="prompt"
                                    placeholder="Message the AI Assistant..."
                                    class="w-full text-base py-3"
                                    required
                                />
                            </x-filament::input.wrapper>
                        </div>
                        <x-filament::button type="submit" size="xl" icon="heroicon-m-arrow-up" color="gray" class="!rounded-lg !px-4">
                            Send
                        </x-filament::button>
                    </form>
                </div>
            @elseif($activeTab === 'reports')
                <div class="flex-1 flex flex-col items-center justify-center text-gray-400 p-6">
                    <x-filament::icon icon="heroicon-o-chart-bar" class="w-16 h-16 mb-4 text-gray-300 dark:text-gray-600"/>
                    <p class="text-lg font-medium text-gray-700 dark:text-gray-300">Reports Dashboard</p>
                    <p class="text-sm mt-2 text-center max-w-md">Start a new AI chat to generate dynamic dashboards from your ERP data to save them here.</p>
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
</x-filament-panels::page>
