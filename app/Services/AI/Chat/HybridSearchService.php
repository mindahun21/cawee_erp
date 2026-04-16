<?php

namespace App\Services\AI\Chat;

use App\Services\AI\VectorStore\VectorStoreInterface;
use App\Services\AI\VectorStore\EmbeddingService;
use Illuminate\Support\Facades\Log;

class HybridSearchService
{
    public function __construct(
        protected VectorStoreInterface $vectorStore,
        protected EmbeddingService $embeddingService,
    ) {}

    /**
     * Perform a hybrid search combining vector similarity and keyword matching.
     *
     * @param string $query The user's search query.
     * @param int $limit Maximum results per search type.
     * @return string Formatted document context for the LLM.
     */
    public function search(string $query, int $limit = 5): string
    {
        // 1. Generate embedding for the query
        try {
            $queryEmbedding = $this->embeddingService->embed($query);
        } catch (\Exception $e) {
            Log::warning('HybridSearchService: Embedding failed, falling back to keyword only', [
                'error' => $e->getMessage(),
            ]);
            $queryEmbedding = null;
        }

        // 2. Vector Search (Semantic Similarity)
        $vectorResults = [];
        if ($queryEmbedding) {
            try {
                $vectorResults = $this->vectorStore->similaritySearch($queryEmbedding, $limit);
            } catch (\Exception $e) {
                Log::warning('HybridSearchService: Vector search failed', [
                    'error' => $e->getMessage(),
                ]);
            }
        }

        // 3. Keyword Search (Full-text / BM25)
        $keywordResults = [];
        try {
            $keywordResults = $this->vectorStore->keywordSearch($query, $limit);
        } catch (\Exception $e) {
            Log::warning('HybridSearchService: Keyword search failed', [
                'error' => $e->getMessage(),
            ]);
        }

        // 4. Merge and Deduplicate
        $merged = array_merge($vectorResults, $keywordResults);
        
        $finalText = '';
        $seenIds = [];
        
        foreach ($merged as $result) {
            $id = $result->id ?? null;
            if ($id && !isset($seenIds[$id])) {
                $source = $result->source ?? 'unknown';
                $content = $result->content ?? '';
                $similarity = isset($result->similarity) ? ' (relevance: ' . round($result->similarity, 3) . ')' : '';
                
                $finalText .= "--- Document: [{$source}]{$similarity} ---\n{$content}\n\n";
                $seenIds[$id] = true;
            }
        }
        
        return $finalText ?: 'No relevant documentation found in internal ERP manuals.';
    }
}
