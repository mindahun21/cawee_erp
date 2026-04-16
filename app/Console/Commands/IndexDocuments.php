<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Services\AI\Chat\DocumentIndexer;

class IndexDocuments extends Command
{
    protected $signature = 'ai:index-documents {--path=app/ai-context : Relative path under storage/}';
    protected $description = 'Index ERP documents into the pgvector store for AI RAG search';

    public function handle(DocumentIndexer $indexer): int
    {
        $path = $this->option('path');

        $this->info("Starting document indexing from storage/{$path}...");
        $this->warn("Note: Chunk embeddings might take a few seconds per file depending on Gemini API response times.");
        $this->newLine();

        // Capture logs emitted by the indexer cleanly onto the console
        \Illuminate\Support\Facades\Log::listen(function ($message) {
            if (str_contains($message->message, 'DocumentIndexer:')) {
                $this->line(" ➜ " . str_replace("DocumentIndexer: ", "", $message->message));
            }
        });

        try {
            $indexer->indexDirectory($path);
            $this->newLine();
            $this->info('✓ Document indexing completed successfully!');
            return self::SUCCESS;
        } catch (\Exception $e) {
            $this->error('Indexing failed: ' . $e->getMessage());
            $this->newLine();
            $this->warn('Check that:');
            $this->line('  1. pgvector Docker container is running (docker compose up pgvector)');
            $this->line('  2. GEMINI_API_KEY is set in .env');
            $this->line('  3. Documents exist in storage/' . $path);
            return self::FAILURE;
        }
    }
}
