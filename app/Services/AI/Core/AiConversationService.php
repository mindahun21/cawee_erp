<?php

namespace App\Services\AI\Core;

use Illuminate\Support\Facades\Redis;

class AiConversationService
{
    protected string $prefix = 'ai_chat:';
    protected int $ttl = 60 * 60 * 24 * 7; // 7 days

    public function addMessage(int $userId, string $conversationId, array $message): void
    {
        if ($message['role'] === 'user') {
            $history = $this->getHistory($userId, $conversationId);
            $firstPrompt = empty($history) ? $message['content'] : $history[0]['content'];
            $this->trackConversationMeta($userId, $conversationId, $firstPrompt);
        }

        $key = $this->getKey($userId, $conversationId);
        $message['timestamp'] = now()->toIso8601String();
        
        Redis::rpush($key, json_encode($message));
        Redis::expire($key, $this->ttl);
    }

    public function getConversations(int $userId): array
    {
        $keys = Redis::smembers("ai_conversations:{$userId}");
        $conversations = [];
        foreach ($keys as $convoId) {
            $data = Redis::hgetall("ai_conversation_meta:{$convoId}");
            if (!empty($data)) {
                $conversations[] = $data;
            }
        }
        
        usort($conversations, fn($a, $b) => $b['updated_at'] <=> $a['updated_at']);
        return $conversations;
    }

    public function trackConversationMeta(int $userId, string $conversationId, string $firstPrompt): void
    {
        Redis::sadd("ai_conversations:{$userId}", $conversationId);
        
        $metaKey = "ai_conversation_meta:{$conversationId}";
        if (!Redis::exists($metaKey)) {
            $title = mb_strlen($firstPrompt) > 35 ? mb_substr($firstPrompt, 0, 35) . '...' : $firstPrompt;
            Redis::hmset($metaKey, [
                'id' => $conversationId,
                'title' => $title,
                'created_at' => now()->timestamp,
                'updated_at' => now()->timestamp,
            ]);
        } else {
            Redis::hset($metaKey, 'updated_at', now()->timestamp);
        }
        
        Redis::expire("ai_conversations:{$userId}", $this->ttl);
        Redis::expire($metaKey, $this->ttl);
    }

    public function getHistory(int $userId, string $conversationId): array
    {
        $key = $this->getKey($userId, $conversationId);
        $data = Redis::lrange($key, 0, -1);
        
        return array_map(function ($item) {
            return json_decode($item, true);
        }, $data);
    }
    
    public function clearHistory(int $userId, string $conversationId): void
    {
        Redis::del($this->getKey($userId, $conversationId));
    }

    public function deleteConversation(int $userId, string $conversationId): void
    {
        $this->clearHistory($userId, $conversationId);
        Redis::srem("ai_conversations:{$userId}", $conversationId);
        Redis::del("ai_conversation_meta:{$conversationId}");
    }
    
    public function truncateAfter(int $userId, string $conversationId, int $messageIndex): void
    {
        $key = $this->getKey($userId, $conversationId);
        $length = Redis::llen($key);
        
        if ($messageIndex >= 0 && $messageIndex < $length) {
            // LTRIM keeps range (start, stop). We want 0 to messageIndex
            // So trimming directly to messageIndex will remove messages after it
            Redis::ltrim($key, 0, $messageIndex);
            Redis::hset("ai_conversation_meta:{$conversationId}", 'updated_at', now()->timestamp);
        }
    }

    protected function getKey(int $userId, string $conversationId): string
    {
        return "{$this->prefix}{$userId}:{$conversationId}";
    }
}
