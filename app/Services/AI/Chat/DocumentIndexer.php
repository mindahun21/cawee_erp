<?php

namespace App\Services\AI\Chat;

use App\Services\AI\VectorStore\VectorStoreInterface;
use App\Services\AI\VectorStore\EmbeddingService;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Log;

class DocumentIndexer
{
    public function __construct(
        protected VectorStoreInterface $vectorStore,
        protected EmbeddingService $embeddingService,
    ) {}

    /**
     * Index all documents from the given storage directory into the vector store.
     *
     * @param string $path Relative path under storage/.
     */
    public function indexDirectory(string $path = 'app/ai-context'): void
    {
        $this->vectorStore->ensureSchema();

        $fullPath = storage_path($path);
        if (!File::exists($fullPath)) {
            Log::info("DocumentIndexer: Directory {$fullPath} does not exist, creating it.");
            File::makeDirectory($fullPath, 0755, true, true);
            return;
        }

        $files = File::allFiles($fullPath);
        
        if (empty($files)) {
            Log::info("DocumentIndexer: No files found in {$fullPath}");
            return;
        }

        // Clear previous embeddings for a clean re-index
        $this->vectorStore->truncate();

        $totalChunks = 0;

        foreach ($files as $file) {
            $content = $file->getContents();
            $source = $file->getRelativePathname();
            $extension = $file->getExtension();

            // Split content into manageable chunks (max ~1500 chars each)
            $chunks = $this->chunkText($content, 1500);

            Log::info("DocumentIndexer: Processing '{$source}' -> " . count($chunks) . " chunks");

            // Batch embed all chunks for this file
            $chunkTexts = array_map(fn(array $c) => $c['text'], $chunks);
            
            try {
                $embeddings = $this->embeddingService->embedBatch($chunkTexts);
            } catch (\Exception $e) {
                Log::error("DocumentIndexer: Failed to embed '{$source}': " . $e->getMessage());
                continue;
            }

            foreach ($chunks as $i => $chunk) {
                if (!isset($embeddings[$i]) || empty($embeddings[$i])) {
                    continue;
                }

                $this->vectorStore->store(
                    source: $source,
                    content: $chunk['text'],
                    embedding: $embeddings[$i],
                    metadata: [
                        'file_type' => $extension,
                        'chunk_index' => $i,
                        'total_chunks' => count($chunks),
                    ],
                );

                $totalChunks++;
            }
        }

        // Build the search index after all data is loaded
        if (method_exists($this->vectorStore, 'buildIndex')) {
            $this->vectorStore->buildIndex();
        }

        Log::info("DocumentIndexer: Indexed {$totalChunks} chunks from " . count($files) . " files.");
    }

    /**
     * Split text into overlapping chunks for better semantic retrieval.
     *
     * @param string $text The full text to split.
     * @param int $maxChars Maximum characters per chunk.
     * @param int $overlap Number of overlapping characters between chunks.
     * @return array Array of ['text' => string, 'start' => int].
     */
    protected function chunkText(string $text, int $maxChars = 1500, int $overlap = 200): array
    {
        $text = trim($text);
        $length = mb_strlen($text);

        if ($length <= $maxChars) {
            return [['text' => $text, 'start' => 0]];
        }

        $chunks = [];
        $start = 0;

        while ($start < $length) {
            $end = min($start + $maxChars, $length);
            $chunkText = mb_substr($text, $start, $end - $start);

            // Try to break at a paragraph or sentence boundary
            if ($end < $length) {
                $lastParagraph = mb_strrpos($chunkText, "\n\n");
                $lastSentence = mb_strrpos($chunkText, '. ');

                if ($lastParagraph !== false && $lastParagraph > $maxChars * 0.5) {
                    $chunkText = mb_substr($chunkText, 0, $lastParagraph);
                    $end = $start + $lastParagraph;
                } elseif ($lastSentence !== false && $lastSentence > $maxChars * 0.5) {
                    $chunkText = mb_substr($chunkText, 0, $lastSentence + 2);
                    $end = $start + $lastSentence + 2;
                }
            }

            $chunks[] = ['text' => trim($chunkText), 'start' => $start];
            
            // STRICT GUARANTEE: $start MUST universally advance to prevent infinite loops.
            $nextStart = $end - $overlap;
            if ($nextStart <= $start) {
                $nextStart = $end; // No overlap, just move strictly forward
            }
            
            $start = $nextStart;

            if ($start >= $length) break;
        }

        return $chunks;
    }
}
