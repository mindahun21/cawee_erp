<x-filament-panels::page>
    <x-filament::tabs>
        <x-filament::tabs.item active>
            Saved Reports
        </x-filament::tabs.item>
        
        <x-filament::tabs.item tag="a" href="{{ \App\Filament\Pages\AiChatPage::getUrl() }}">
            AI Chats & Planning
        </x-filament::tabs.item>
    </x-filament::tabs>

    <div class="mt-4 bg-white dark:bg-gray-900 shadow rounded-xl p-6 border border-gray-200 dark:border-gray-800">
        <!-- In Phase 2, we will render a Filament Table here of the ai_saved_reports -->
        <div class="text-center py-12">
            <x-filament::icon
                icon="heroicon-o-document-magnifying-glass"
                class="mx-auto h-12 w-12 text-gray-400"
            />
            <h3 class="mt-2 text-sm font-semibold text-gray-900 dark:text-white">No saved reports yet</h3>
            <p class="mt-1 text-sm text-gray-500">Start an AI chat to generate dynamic dynamic dashboards!</p>
            <div class="mt-6">
                <x-filament::button tag="a" href="{{ \App\Filament\Pages\AiChatPage::getUrl() }}">
                    Start New Chat
                </x-filament::button>
            </div>
        </div>
    </div>
</x-filament-panels::page>
