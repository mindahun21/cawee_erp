<?php

namespace App\Filament\Pages;

use Filament\Pages\Page;
use App\Services\AI\Core\AiRouterService;
use App\Support\VectorDatabaseDetector;
use BackedEnum;
use UnitEnum;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\RateLimiter;
use Livewire\WithFileUploads;

class AiAnalyticsHub extends Page
{
    use \App\Traits\BelongsToModulePage;
    use WithFileUploads;
    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-chart-bar';
    protected string $view = 'filament.pages.ai-analytics-hub';
    protected static string|UnitEnum|null $navigationGroup = 'AI Intelligence';
    protected static ?string $navigationLabel = 'AI Analytics Hub';
    protected ?string $heading = ''; 

    public string $activeTab = 'chats'; // 'chats', 'reports', 'plans'
    public string $prompt = '';
    
    /** @var \Livewire\Features\SupportFileUploads\TemporaryUploadedFile|null */
    public $fileUpload;
    
    public array $messages = [];
    public ?string $conversationId = null;
    public array $conversations = [];
    public array $reports = [];
    public array $plans = [];
    public bool $isSidebarOpen = true;

    /**
     * Override canAccess to check both module status AND vector database availability.
     * 
     * This ensures the AI Analytics Hub is only accessible when:
     * 1. The ai_intelligence module is enabled
     * 2. The vector database (PostgreSQL with pgvector) is available
     */
    public static function canAccess(): bool
    {
        // Check if AI module is enabled AND vector database is available
        if (!VectorDatabaseDetector::isAiReady()) {
            return false;
        }
        
        // Call parent to check additional permissions
        if (method_exists(parent::class, 'canAccess')) {
            return parent::canAccess();
        }
        
        return true;
    }
    
    /**
     * Hide navigation item when AI is not fully operational.
     */
    public static function shouldRegisterNavigation(): bool
    {
        return static::canAccess();
    }

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

    public function deleteChat(string $id)
    {
        app(\App\Services\AI\Core\AiConversationService::class)
            ->deleteConversation(auth()->id(), $id);
            
        if ($this->conversationId === $id) {
            $this->startNewChat();
        } else {
            $this->loadConversations();
        }
    }

    public function loadReports()
    {
        $this->reports = \App\Models\AiGeneratedReport::where('user_id', auth()->id())
            ->where('is_saved', true)
            ->orderBy('created_at', 'desc')
            ->get()
            ->map(fn($report) => [
                'id' => $report->id,
                'title' => $report->title,
                'module' => $report->module_context,
                'created_at' => $report->created_at->format('M d, Y'),
                'created_at_human' => $report->created_at->diffForHumans(),
            ])
            ->toArray();
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
        if (empty(trim($this->prompt)) && !$this->fileUpload) return;

        /** @var \App\Models\User $user */
        $user = auth()->user();

        if (RateLimiter::tooManyAttempts('ai_chat_send:' . $user->id, 10)) {
            \Filament\Notifications\Notification::make()->title('Too many requests. Please wait a moment.')->danger()->send();
            return;
        }
        RateLimiter::hit('ai_chat_send:' . $user->id, 60);

        $imageMetadata = null;
        if ($this->fileUpload) {
            $path = $this->fileUpload->store('ai-uploads', 'local');
            $imageMetadata = [
                'path' => storage_path('app/' . $path),
                'mime' => $this->fileUpload->getMimeType(),
                'original_name' => $this->fileUpload->getClientOriginalName()
            ];
            $this->fileUpload = null;
        }

        $currentPrompt = $this->prompt;
        $this->messages[] = ['role' => 'user', 'content' => $currentPrompt, 'image' => $imageMetadata];
        $this->prompt = '';

        $response = $router->route($user, $this->conversationId, $currentPrompt, $imageMetadata);
        
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

        if (RateLimiter::tooManyAttempts('ai_chat_retry:' . $user->id, 5)) {
            \Filament\Notifications\Notification::make()->title('Too many retries. Please wait a moment.')->danger()->send();
            return;
        }
        RateLimiter::hit('ai_chat_retry:' . $user->id, 60);

        $response = $router->retry($user, $this->conversationId);
        
        $this->messages[] = $response;
    }

    public function copyMessage(int $index)
    {
        if (isset($this->messages[$index])) {
            $content = $this->messages[$index]['content'];
            $this->dispatch('clipboard-copy', ['content' => $content]);
            \Filament\Notifications\Notification::make()->title('Copied to clipboard!')->success()->send();
        }
    }
    
    public function setPrompt(string $prompt)
    {
        $this->prompt = $prompt;
    }
    
    public function viewReport(int $reportId)
    {
        $report = \App\Models\AiGeneratedReport::where('id', $reportId)
            ->where('user_id', auth()->id())
            ->first();
            
        if (!$report) {
            \Filament\Notifications\Notification::make()
                ->title('Report not found')
                ->danger()
                ->send();
            return;
        }
        
        $this->dispatch('open-report-modal', reportId: $reportId);
    }
    
    public function saveReport(int $reportId)
    {
        $report = \App\Models\AiGeneratedReport::where('id', $reportId)
            ->where('user_id', auth()->id())
            ->first();
            
        if (!$report) {
            \Filament\Notifications\Notification::make()
                ->title('Report not found')
                ->danger()
                ->send();
            return;
        }
        
        $report->update(['is_saved' => true]);
        
        \Filament\Notifications\Notification::make()
            ->title('Report saved successfully')
            ->success()
            ->send();
            
        $this->loadReports();
    }
    
    public function deleteReport(int $reportId)
    {
        $report = \App\Models\AiGeneratedReport::where('id', $reportId)
            ->where('user_id', auth()->id())
            ->first();
            
        if (!$report) {
            \Filament\Notifications\Notification::make()
                ->title('Report not found')
                ->danger()
                ->send();
            return;
        }
        
        $report->delete();
        
        \Filament\Notifications\Notification::make()
            ->title('Report deleted successfully')
            ->success()
            ->send();
            
        $this->loadReports();
    }
    
    public function exportReportPdf(int $reportId)
    {
        // TODO: Implement PDF export using DomPDF or Browsershot
        \Filament\Notifications\Notification::make()
            ->title('PDF export coming soon')
            ->info()
            ->send();
    }
    
    public function exportReportCsv(int $reportId)
    {
        $report = \App\Models\AiGeneratedReport::where('id', $reportId)
            ->where('user_id', auth()->id())
            ->first();
            
        if (!$report) {
            \Filament\Notifications\Notification::make()
                ->title('Report not found')
                ->danger()
                ->send();
            return;
        }
        
        // Export tables as CSV
        $reportData = $report->report_json;
        if (empty($reportData['tables'])) {
            \Filament\Notifications\Notification::make()
                ->title('No tables to export')
                ->warning()
                ->send();
            return;
        }
        
        // Generate CSV from first table
        $table = $reportData['tables'][0];
        $filename = \Illuminate\Support\Str::slug($report->title) . '.csv';
        
        $csv = [];
        // Header row
        $headers = array_map(fn($col) => $col['label'] ?? $col['key'] ?? '', $table['columns'] ?? []);
        $csv[] = $headers;
        
        // Data rows
        foreach ($table['rows'] ?? [] as $row) {
            $rowData = [];
            foreach ($table['columns'] ?? [] as $column) {
                $rowData[] = $row[$column['key']] ?? '';
            }
            $csv[] = $rowData;
        }
        
        // Convert to CSV string
        $output = fopen('php://temp', 'r+');
        foreach ($csv as $row) {
            fputcsv($output, $row);
        }
        rewind($output);
        $csvContent = stream_get_contents($output);
        fclose($output);
        
        return response()->streamDownload(function() use ($csvContent) {
            echo $csvContent;
        }, $filename, ['Content-Type' => 'text/csv']);
    }

    public function editPrompt(int $index, ?string $newPrompt)
    {
        if ($newPrompt === null || empty(trim($newPrompt)) || !isset($this->messages[$index]) || $this->messages[$index]['role'] !== 'user') return;
        
        $service = app(\App\Services\AI\Core\AiConversationService::class);
        $user = auth()->user();
        
        // Truncate to ($index - 1) which keeps only messages BEFORE the edit.
        $service->truncateAfter($user->id, $this->conversationId, $index - 1);
        
        // Reload messages
        $this->messages = $service->getHistory($user->id, $this->conversationId);
        
        // Submit the new prompt
        $this->prompt = $newPrompt;
        $this->sendMessage(app(\App\Services\AI\Core\AiRouterService::class));
    }
    
    public function regenerateResponse(int $index)
    {
        // Regenerate is clicked below an AI message, so the prompt was the message immediately before it.
        $userMessageIndex = $index - 1;
        if (!isset($this->messages[$userMessageIndex]) || $this->messages[$userMessageIndex]['role'] !== 'user') return;
        
        $promptContent = $this->messages[$userMessageIndex]['content'];
        
        // Act like we edited the prompt to be the exact same text
        $this->editPrompt($userMessageIndex, $promptContent);
    }
}
