<?php

namespace App\Services\AI\Core;

use App\Models\User;
use Prism\Prism\Facades\Prism;
use Prism\Prism\ValueObjects\Messages\UserMessage;
use Prism\Prism\ValueObjects\Messages\AssistantMessage;
use App\Services\AI\Shared\RbacContextFilter;

class AiRouterService
{
    public function __construct(
        protected AiConversationService $conversationService,
        protected RbacContextFilter $rbacFilter,
        protected \App\Services\AI\Tools\ChatTool $chatTool
    ) {}

    public function route(User $user, string $conversationId, string $prompt, ?array $imageMetadata = null): array
    {
        // 1. Build context
        $preamble = $this->rbacFilter->buildPermissionPreamble($user);
        $systemPrompt = "You are an intelligent, user-friendly ERP Assistant. \n" 
            . $preamble . "\n"
            . "Answer the user's questions strictly based on the manuals and tools provided. "
            . "CRITICAL INSTRUCTION: Never expose backend technical terms (like 'Spatie permissions' or 'Error Code 502') directly to the user. "
            . "Always translate technical jargon into friendly, business-oriented instructions (e.g. 'Please ask your administrator to grant you Interviewer Access'). Act as a helpful human ERP specialist.";

        // 2. Fetch history and reconstruct Prism messages
        $historyData = $this->conversationService->getHistory($user->id, $conversationId);
        $messages = $this->reconstructMessages($historyData);

        // 3. Add current message
        $currentMessage = new UserMessage($prompt); // Multimodal image will be handled later
        $messages[] = $currentMessage;
        
        // Save user message to Redis
        $this->conversationService->addMessage($user->id, $conversationId, [
            'role' => 'user',
            'content' => $prompt,
        ]);

        // 4. Call Prism Router (Will map to gemini or openai based on env)
        try {
            // Attempt the API call 3 times with a 2-second backoff to handle "Overloaded" spikes
            $response = retry(3, function () use ($systemPrompt, $messages) {
                return Prism::text()
                    ->using('gemini', 'gemini-2.5-flash')
                    ->withSystemPrompt($systemPrompt)
                    ->withMessages($messages)
                    ->withTools([
                        $this->chatTool->asPrismTool()
                    ])
                    ->withMaxSteps(5)
                    ->generate();
            }, 2000);
            
            $responseText = $response->text;
        } catch (\Exception $e) {
            $responseText = "I apologize, but my AI language server is currently experiencing extremely high traffic and is overloaded. Please click the 'Continue' button to try your request again in a few moments.";
            \Illuminate\Support\Facades\Log::error('AiRouterService Gemini Overloaded: ' . $e->getMessage());
        }

        // 5. Save assistant message
        $this->conversationService->addMessage($user->id, $conversationId, [
            'role' => 'assistant',
            'content' => $responseText,
            'type' => 'text' 
        ]);

        return [
            'role' => 'assistant',
            'content' => $responseText,
            'type' => 'text'
        ];
    }

    public function retry(User $user, string $conversationId): array
    {
        $historyData = $this->conversationService->getHistory($user->id, $conversationId);
        $messages = $this->reconstructMessages($historyData);
        
        $preamble = $this->rbacFilter->buildPermissionPreamble($user);
        $systemPrompt = "You are an intelligent, user-friendly ERP Assistant. \n" 
            . $preamble . "\n"
            . "Answer the user's questions strictly based on the manuals and tools provided. "
            . "CRITICAL INSTRUCTION: Never expose backend technical terms (like 'Spatie permissions' or 'Error Code 502') directly to the user. "
            . "Always translate technical jargon into friendly, business-oriented instructions (e.g. 'Please ask your administrator to grant you Interviewer Access'). Act as a helpful human ERP specialist.";

        try {
            $response = retry(3, function () use ($systemPrompt, $messages) {
                return Prism::text()
                    ->using('gemini', 'gemini-2.5-flash')
                    ->withSystemPrompt($systemPrompt)
                    ->withMessages($messages)
                    ->withTools([
                        $this->chatTool->asPrismTool()
                    ])
                    ->withMaxSteps(5)
                    ->generate();
            }, 2000);
            
            $responseText = $response->text;
        } catch (\Exception $e) {
            $responseText = "I apologize, but my AI language server is currently experiencing extremely high traffic and is overloaded. Please click the 'Continue' button to try your request again in a few moments.";
            \Illuminate\Support\Facades\Log::error('AiRouterService Gemini Overloaded Retry: ' . $e->getMessage());
        }

        $assistantMsg = [
            'role' => 'assistant',
            'content' => $responseText,
            'type' => 'text'
        ];
        
        $this->conversationService->addMessage($user->id, $conversationId, $assistantMsg);

        return $assistantMsg;
    }

    protected function reconstructMessages(array $historyData): array
    {
        $messages = [];
        foreach ($historyData as $data) {
            if ($data['role'] === 'user') {
                $messages[] = new UserMessage($data['content']);
            } elseif ($data['role'] === 'assistant') {
                $messages[] = new AssistantMessage($data['content']);
            }
        }
        return $messages;
    }
}
