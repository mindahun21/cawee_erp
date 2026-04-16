<div class="fixed bottom-6 right-8 z-50">
    @if($isOpen)
        <div class="bg-white dark:bg-gray-900 shadow-2xl rounded-2xl w-96 h-[400px] flex flex-col mb-4 border border-gray-200 dark:border-gray-800 overflow-hidden transform transition-all animate-flicker">
            <div class="bg-primary-600 text-white p-4 flex justify-between items-center shadow-sm">
                <div class="flex items-center gap-2">
                    <x-filament::icon icon="heroicon-m-sparkles" class="w-5 h-5"/>
                    <h3 class="font-bold">AI Assistant</h3>
                </div>
                <button wire:click="toggle" class="hover:text-gray-200 focus:outline-none rounded-full p-1 hover:bg-primary-500 transition">
                    <x-filament::icon icon="heroicon-m-x-mark" class="w-5 h-5"/>
                </button>
            </div>
            
            <div class="flex-1 p-6 flex flex-col justify-center items-center text-center bg-gray-50/50 dark:bg-gray-900/50">
                <div class="bg-white dark:bg-gray-800 rounded-full p-4 mb-4 shadow-sm border border-gray-100 dark:border-gray-700">
                     <x-filament::icon icon="heroicon-o-chat-bubble-bottom-center-text" class="w-8 h-8 text-primary-500"/>
                </div>
                <p class="text-sm text-gray-600 dark:text-gray-400 mb-6 px-4">
                    The full Conversational AI and Reporting engine is available in the Analytics Hub.
                </p>
                <x-filament::button tag="a" href="{{ \App\Filament\Pages\AiChatPage::getUrl() }}" color="primary">
                    Open AI Chat
                </x-filament::button>
            </div>
        </div>
    @endif

    <div class="flex justify-end">
        <button wire:click="toggle" class="bg-primary-600 text-white rounded-full p-4 shadow-xl hover:bg-primary-500 hover:scale-105 transition-all focus:outline-none">
            <x-filament::icon icon="heroicon-o-sparkles" class="w-7 h-7"/>
        </button>
    </div>
</div>
