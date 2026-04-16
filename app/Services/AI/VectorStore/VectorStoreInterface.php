<?php

namespace App\Services\AI\VectorStore;

interface VectorStoreInterface
{
    /**
     * Ensure the vector store schema (table, extension) exists.
     */
    public function ensureSchema(): void;

    /**
     * Store a document chunk with its embedding vector.
     *
     * @param string $source  The source filename or identifier.
     * @param string $content The text content of the chunk.
     * @param array  $embedding The float vector from the embedding model.
     * @param array  $metadata Optional metadata (module, tags, etc).
     */
    public function store(string $source, string $content, array $embedding, array $metadata = []): void;

    /**
     * Search for similar documents using cosine similarity.
     *
     * @param array $queryEmbedding The embedded query vector.
     * @param int   $limit Max results.
     * @return array Array of result objects with id, source, content, similarity.
     */
    public function similaritySearch(array $queryEmbedding, int $limit = 5): array;

    /**
     * Keyword-based fulltext search (the "hybrid" part).
     *
     * @param string $query Raw text query.
     * @param int    $limit Max results.
     * @return array Array of result objects with id, source, content.
     */
    public function keywordSearch(string $query, int $limit = 5): array;

    /**
     * Remove all stored documents (used during re-indexing).
     */
    public function truncate(): void;
}
