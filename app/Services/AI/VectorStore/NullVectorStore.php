<?php

namespace App\Services\AI\VectorStore;

use RuntimeException;

/**
 * Null implementation of VectorStoreInterface.
 * 
 * This class is used when the AI Intelligence module is disabled or when
 * the vector database (PostgreSQL with pgvector) is unavailable. All methods
 * throw descriptive exceptions with actionable instructions for enabling AI features.
 * 
 * This enables graceful degradation on hosting environments like cPanel that
 * don't have PostgreSQL/pgvector support.
 */
class NullVectorStore implements VectorStoreInterface
{
    /**
     * Throw a descriptive exception explaining why AI features are unavailable.
     * 
     * @throws RuntimeException Always throws with actionable instructions
     * @return never
     */
    protected function throwDisabledException(): never
    {
        throw new RuntimeException(
            "AI Intelligence module is disabled or vector database is unavailable.\n\n" .
            "To enable AI features:\n" .
            "1. Ensure PostgreSQL with pgvector extension is available\n" .
            "2. Configure VECTOR_DB_* credentials in .env:\n" .
            "   VECTOR_DB_HOST=127.0.0.1\n" .
            "   VECTOR_DB_PORT=15432\n" .
            "   VECTOR_DB_DATABASE=elisoft_vectors\n" .
            "   VECTOR_DB_USERNAME=vector_user\n" .
            "   VECTOR_DB_PASSWORD=your_password\n" .
            "3. Add 'ai_intelligence' to ENABLED_MODULES in .env:\n" .
            "   ENABLED_MODULES=hr,finance,inventory,ai_intelligence\n" .
            "4. Run migrations: php artisan migrate\n" .
            "5. Index documents: php artisan ai:index-documents\n\n" .
            "For cPanel deployments without PostgreSQL, AI features cannot be enabled.\n" .
            "All other ERP modules will continue to function normally."
        );
    }
    
    /**
     * {@inheritdoc}
     */
    public function ensureSchema(): void
    {
        $this->throwDisabledException();
    }
    
    /**
     * {@inheritdoc}
     */
    public function store(string $source, string $content, array $embedding, array $metadata = []): void
    {
        $this->throwDisabledException();
    }
    
    /**
     * {@inheritdoc}
     */
    public function similaritySearch(array $queryEmbedding, int $limit = 5): array
    {
        $this->throwDisabledException();
    }
    
    /**
     * {@inheritdoc}
     */
    public function keywordSearch(string $query, int $limit = 5): array
    {
        $this->throwDisabledException();
    }
    
    /**
     * {@inheritdoc}
     */
    public function truncate(): void
    {
        $this->throwDisabledException();
    }
    
    /**
     * {@inheritdoc}
     */
    public function findBySource(string $source): ?object
    {
        $this->throwDisabledException();
    }
    
    /**
     * {@inheritdoc}
     */
    public function deleteBySource(string $source): void
    {
        $this->throwDisabledException();
    }
}
