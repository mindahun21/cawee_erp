<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Services\AI\Chat\DocumentIndexer;

class IndexDocuments extends Command
{
    protected $signature = 'ai:index-documents 
                            {--path=app/ai-context : Relative path under storage/}
                            {--fresh : Clear all embeddings and re-index everything}';
    protected $description = 'Index ERP documents (text, PDF, images, videos) into the pgvector store for AI RAG search';

    public function handle(DocumentIndexer $indexer): int
    {
        $path = $this->option('path');
        $fresh = $this->option('fresh');

        if ($fresh) {
            $this->warn("FRESH MODE: All existing embeddings will be cleared and re-indexed");
        } else {
            $this->info("INCREMENTAL MODE: Only new/modified files will be processed");
        }
        
        $this->newLine();
        $this->info("Starting document indexing from storage/{$path}...");
        $this->newLine();
        $this->line("Supported formats:");
        $this->line("  - Text: .md, .txt, .html");
        $this->line("  - PDFs: .pdf (text extraction)");
        $this->line("  - Images: .png, .jpg, .jpeg, .webp (Vision AI)");
        $this->line("  - Videos: .mp4, .avi, .mov, .webm, .mkv (Video AI)");
        $this->newLine();
        $this->warn("Note: Video and PDF processing may take longer due to AI analysis.");
        $this->newLine();

        // Capture logs emitted by the indexer cleanly onto the console
        \Illuminate\Support\Facades\Log::listen(function ($message) {
            if (str_contains($message->message, 'DocumentIndexer:')) {
                $this->line(" > " . str_replace("DocumentIndexer: ", "", $message->message));
            }
        });

        try {
            $indexer->indexDirectory($path, $fresh);
            $this->newLine();
            $this->info('Document indexing completed successfully!');
            
            if (!$fresh) {
                $this->newLine();
                $this->comment('Tip: Use --fresh flag to force re-index all files');
            }
            
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
