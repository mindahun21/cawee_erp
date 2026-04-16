<?php

namespace App\Filament\Pages;

use Filament\Pages\Page;
use App\Services\AI\Core\AiRouterService;
use BackedEnum;
use UnitEnum;
use Illuminate\Support\Str;

class AiAnalyticsHub extends Page
{
    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-chart-bar';
    protected string $view = 'filament.pages.ai-analytics-hub';
    protected static string|UnitEnum|null $navigationGroup = 'AI Intelligence';
    protected static ?string $navigationLabel = 'AI Analytics Hub';
    protected ?string $heading = ''; 

    public string $activeTab = 'chats'; // 'chats', 'reports', 'plans'
    public string $prompt = '';
    public array $messages = [];
    public ?string $conversationId = null;
    public array $conversations = [];
    public array $reports = [];
    public array $plans = [];
    public bool $isSidebarOpen = true;

    public function toggleSidebar()
    {
        $this->isSidebarOpen = !$this->isSidebarOpen;
    }

    public function setTab(string $tab)
    {
        $this->activeTab = $tab;
        
        switch ($tab) {
            case 'chats':
                $this->loadConversations();
                break;
            case 'reports':
                $this->loadReports();
                break;
            case 'plans':
                $this->loadPlans();
                break;
        }
    }

    public function mount()
    {
        $this->setTab('chats');
        
        if (empty($this->conversations)) {
            $this->startNewChat();
        } else {
            $this->loadChat($this->conversations[0]['id']);
        }
    }

    public function loadConversations()
    {
        $this->conversations = app(\App\Services\AI\Core\AiConversationService::class)
            ->getConversations(auth()->id());
    }

    public function loadChat(string $id)
    {
        $this->conversationId = $id;
        $this->messages = app(\App\Services\AI\Core\AiConversationService::class)
            ->getHistory(auth()->id(), $this->conversationId);
        $this->loadConversations();
    }

    public function loadReports()
    {
        // Mock data for Phase 2 readiness
        $this->reports = [];
    }
    
    public function loadPlans()
    {
        // Mock data for Phase 3 readiness
        $this->plans = [];
    }

    public function startNewChat()
    {
        $this->setTab('chats');
        $this->conversationId = (string) Str::uuid();
        $this->messages = [];
        $this->loadConversations();
    }

    public function sendMessage(AiRouterService $router)
    {
        if (empty(trim($this->prompt))) return;

        $currentPrompt = $this->prompt;
        $this->messages[] = ['role' => 'user', 'content' => $currentPrompt];
        $this->prompt = '';

        /** @var \App\Models\User $user */
        $user = auth()->user();

        $response = $router->route($user, $this->conversationId, $currentPrompt);
        
        $this->messages[] = $response;
        
        if (count($this->messages) <= 2) {
            $this->loadConversations();
        }
    }

    public function retry(AiRouterService $router)
    {
        if (empty($this->messages)) return;
        
        $lastMessage = end($this->messages);
        if ($lastMessage['role'] !== 'user') return;
        
        /** @var \App\Models\User $user */
        $user = auth()->user();

        $response = $router->retry($user, $this->conversationId);
        
        $this->messages[] = $response;
    }
}
