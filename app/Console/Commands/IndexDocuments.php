<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Services\AI\Chat\DocumentIndexer;
use App\Support\ModuleManager;
use App\Support\VectorDatabaseDetector;

class IndexDocuments extends Command
{
    protected $signature = 'ai:index-documents 
                            {--path=app/ai-context : Relative path under storage/}
                            {--fresh : Clear all embeddings and re-index everything}';
    protected $description = 'Index ERP documents (text, PDF, images, videos) into the pgvector store for AI RAG search';

    public function handle(DocumentIndexer $indexer): int
    {
        // Check if AI Intelligence module is enabled
        if (!ModuleManager::isEnabled('ai_intelligence')) {
            $this->error('AI Intelligence module is disabled.');
            $this->newLine();
            $this->info('To enable AI features:');
            $this->line('  1. Add "ai_intelligence" to ENABLED_MODULES in .env');
            $this->line('     Example: ENABLED_MODULES=hr,finance,inventory,ai_intelligence');
            $this->line('  2. Ensure PostgreSQL with pgvector extension is available');
            $this->line('  3. Configure VECTOR_DB_* credentials in .env');
            $this->line('  4. Run: php artisan migrate');
            $this->line('  5. Run: php artisan ai:index-documents');
            $this->newLine();
            $this->comment('For cPanel deployments without PostgreSQL, AI features cannot be enabled.');
            return self::FAILURE;
        }
        
        // Check if vector database is available
        if (!VectorDatabaseDetector::isAvailable()) {
            $this->error('Vector database is not available.');
            $this->newLine();
            $this->info('To fix this:');
            $this->line('  1. Ensure PostgreSQL with pgvector extension is running');
            $this->line('  2. Check VECTOR_DB_* credentials in .env:');
            $this->line('     VECTOR_DB_HOST=' . config('database.connections.vector.host'));
            $this->line('     VECTOR_DB_PORT=' . config('database.connections.vector.port'));
            $this->line('     VECTOR_DB_DATABASE=' . config('database.connections.vector.database'));
            $this->line('  3. Test connection: php artisan ai:check');
            $this->newLine();
            $this->comment('For cPanel deployments, PostgreSQL/pgvector is typically not available.');
            return self::FAILURE;
        }

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
            $this->line('  1. Vector database is running and accessible');
            $this->line('  2. GEMINI_API_KEY is set in .env');
            $this->line('  3. Documents exist in storage/' . $path);
            return self::FAILURE;
        }
    }
}
