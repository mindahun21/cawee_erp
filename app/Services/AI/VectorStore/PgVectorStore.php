<?php

namespace App\Services\AI\VectorStore;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class PgVectorStore implements VectorStoreInterface
{
    protected string $connection = 'vector';
    protected string $table = 'ai_embeddings';

    public function ensureSchema(): void
    {
        $db = DB::connection($this->connection);

        // Enable the pgvector extension
        $db->statement('CREATE EXTENSION IF NOT EXISTS vector');

        // Create the embeddings table if it doesn't exist
        $dimensions = (int) env('EMBEDDING_DIMENSIONS', 768);

        $exists = $db->select(
            "SELECT EXISTS (SELECT FROM information_schema.tables WHERE table_name = ?)",
            [$this->table]
        );

        if (!($exists[0]->exists ?? false)) {
            $db->statement("
                CREATE TABLE {$this->table} (
                    id BIGSERIAL PRIMARY KEY,
                    source VARCHAR(500) NOT NULL,
                    content TEXT NOT NULL,
                    embedding vector({$dimensions}),
                    metadata JSONB DEFAULT '{}',
                    created_at TIMESTAMP DEFAULT NOW(),
                    updated_at TIMESTAMP DEFAULT NOW()
                )
            ");

            // Create an IVFFlat index for fast cosine similarity queries
            // Index is created after initial data load for better accuracy
            Log::info("PgVectorStore: Created table '{$this->table}' with vector({$dimensions})");
        }
    }

    public function store(string $source, string $content, array $embedding, array $metadata = []): void
    {
        $db = DB::connection($this->connection);
        $vectorStr = '[' . implode(',', $embedding) . ']';

        $db->table($this->table)->insert([
            'source' => $source,
            'content' => $content,
            'embedding' => $vectorStr,
            'file_hash' => $metadata['file_hash'] ?? null,
            'file_size' => $metadata['file_size'] ?? null,
            'file_modified_at' => $metadata['file_modified_at'] ?? null,
            'metadata' => json_encode($metadata),
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    public function similaritySearch(array $queryEmbedding, int $limit = 5): array
    {
        $db = DB::connection($this->connection);
        $vectorStr = '[' . implode(',', $queryEmbedding) . ']';

        // Use cosine distance operator <=> for similarity search
        $results = $db->select("
            SELECT id, source, content, metadata,
                   1 - (embedding <=> ?::vector) AS similarity
            FROM {$this->table}
            ORDER BY embedding <=> ?::vector
            LIMIT ?
        ", [$vectorStr, $vectorStr, $limit]);

        return $results;
    }

    public function keywordSearch(string $query, int $limit = 5): array
    {
        $db = DB::connection($this->connection);

        try {
            // Use PostgreSQL's built-in full-text search
            $tsQuery = implode(' & ', array_filter(explode(' ', $query)));

            $results = $db->select("
                SELECT id, source, content, metadata,
                       ts_rank(to_tsvector('english', content), plainto_tsquery('english', ?)) AS rank
                FROM {$this->table}
                WHERE to_tsvector('english', content) @@ plainto_tsquery('english', ?)
                ORDER BY rank DESC
                LIMIT ?
            ", [$query, $query, $limit]);

            return $results;
        } catch (\Exception $e) {
            Log::warning('PgVectorStore: Keyword search failed, falling back to LIKE', [
                'error' => $e->getMessage(),
            ]);

            return $db->select("
                SELECT id, source, content, metadata
                FROM {$this->table}
                WHERE content ILIKE ?
                LIMIT ?
            ", ["%{$query}%", $limit]);
        }
    }

    public function truncate(): void
    {
        DB::connection($this->connection)->table($this->table)->truncate();
        Log::info("PgVectorStore: Truncated table '{$this->table}'");
    }

    /**
     * Build the IVFFlat index after bulk data loading for optimal performance.
     */
    public function buildIndex(): void
    {
        $db = DB::connection($this->connection);
        $count = $db->table($this->table)->count();

        if ($count < 10) {
            Log::info("PgVectorStore: Skipping index build ({$count} rows, need >= 10)");
            return;
        }

        // Calculate lists count: sqrt(n) is a good starting point
        $lists = (int) max(1, ceil(sqrt($count)));

        $db->statement("DROP INDEX IF EXISTS idx_embeddings_cosine");
        $db->statement("
            CREATE INDEX idx_embeddings_cosine
            ON {$this->table}
            USING ivfflat (embedding vector_cosine_ops)
            WITH (lists = {$lists})
        ");

        Log::info("PgVectorStore: Built IVFFlat index with {$lists} lists over {$count} rows");
    }

    /**
     * Find existing embeddings by source file path.
     */
    public function findBySource(string $source): ?object
    {
        $db = DB::connection($this->connection);
        
        $result = $db->table($this->table)
            ->select('source', 'file_hash', 'file_size', 'file_modified_at')
            ->where('source', $source)
            ->first();

        return $result;
    }

    /**
     * Delete all embeddings for a specific source file.
     */
    public function deleteBySource(string $source): void
    {
        $db = DB::connection($this->connection);
        
        $deleted = $db->table($this->table)
            ->where('source', $source)
            ->delete();

        if ($deleted > 0) {
            Log::info("PgVectorStore: Deleted {$deleted} embeddings for source '{$source}'");
        }
    }
}
