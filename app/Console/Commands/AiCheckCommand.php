<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Support\ModuleManager;
use App\Support\VectorDatabaseDetector;
use Illuminate\Support\Facades\DB;

class AiCheckCommand extends Command
{
    protected $signature = 'ai:check';
    protected $description = 'Check AI Intelligence module and vector database status';

    public function handle(): int
    {
        $this->info('═══════════════════════════════════════════════════');
        $this->info('   AI Intelligence Module Status Check');
        $this->info('═══════════════════════════════════════════════════');
        $this->newLine();
        
        // Check module status
        $moduleEnabled = ModuleManager::isEnabled('ai_intelligence');
        $this->line('Module Status: ' . ($moduleEnabled ? '<fg=green>✓ ENABLED</>' : '<fg=red>✗ DISABLED</>'));
        
        if (!$moduleEnabled) {
            $this->newLine();
            $this->warn('⚠ AI Intelligence module is disabled in configuration.');
            $this->newLine();
            $this->info('To enable:');
            $this->line('  1. Edit .env file');
            $this->line('  2. Add "ai_intelligence" to ENABLED_MODULES');
            $this->line('     Example: ENABLED_MODULES=hr,finance,inventory,ai_intelligence');
            $this->line('  3. Restart the application');
            $this->newLine();
            $this->comment('Note: AI features require PostgreSQL with pgvector extension.');
            return self::FAILURE;
        }
        
        // Check vector database connection
        $this->newLine();
        VectorDatabaseDetector::flush(); // Force fresh check
        $dbAvailable = VectorDatabaseDetector::isAvailable();
        $this->line('Vector Database: ' . ($dbAvailable ? '<fg=green>✓ CONNECTED</>' : '<fg=red>✗ UNAVAILABLE</>'));
        
        if (!$dbAvailable) {
            $this->newLine();
            $this->warn('⚠ Vector database connection failed.');
            $this->newLine();
            $this->info('Check these settings in .env:');
            $this->line('  VECTOR_DB_HOST=' . config('database.connections.vector.host'));
            $this->line('  VECTOR_DB_PORT=' . config('database.connections.vector.port'));
            $this->line('  VECTOR_DB_DATABASE=' . config('database.connections.vector.database'));
            $this->line('  VECTOR_DB_USERNAME=' . config('database.connections.vector.username'));
            $this->newLine();
            $this->info('Troubleshooting:');
            $this->line('  1. Ensure PostgreSQL is running');
            $this->line('  2. Verify credentials are correct');
            $this->line('  3. Check firewall/network settings');
            $this->newLine();
            $this->comment('For cPanel hosting, PostgreSQL/pgvector is typically not available.');
            return self::FAILURE;
        }
        
        // Check pgvector extension
        try {
            $result = DB::connection('vector')->select("SELECT extname FROM pg_extension WHERE extname = 'vector'");
            $vectorInstalled = count($result) > 0;
            $this->line('pgvector Extension: ' . ($vectorInstalled ? '<fg=green>✓ INSTALLED</>' : '<fg=red>✗ MISSING</>'));
            
            if (!$vectorInstalled) {
                $this->newLine();
                $this->warn('⚠ pgvector extension is not installed in PostgreSQL.');
                $this->newLine();
                $this->info('To install:');
                $this->line('  1. Connect to PostgreSQL as superuser');
                $this->line('  2. Run: CREATE EXTENSION vector;');
                $this->line('  3. Run this check again');
                return self::FAILURE;
            }
        } catch (\Exception $e) {
            $this->line('pgvector Extension: <fg=yellow>? UNKNOWN</>');
            $this->newLine();
            $this->warn('Could not check pgvector extension: ' . $e->getMessage());
        }
        
        // Check embeddings table
        try {
            $tableExists = DB::connection('vector')
                ->select("SELECT EXISTS (SELECT FROM information_schema.tables WHERE table_name = 'ai_embeddings')")[0]->exists ?? false;
            $this->line('Embeddings Table: ' . ($tableExists ? '<fg=green>✓ EXISTS</>' : '<fg=yellow>⚠ MISSING</>'));
            
            if (!$tableExists) {
                $this->newLine();
                $this->info('To create the embeddings table:');
                $this->line('  Run: php artisan migrate');
            } else {
                // Count indexed documents
                $count = DB::connection('vector')->table('ai_embeddings')->count();
                $this->line('Indexed Documents: <fg=green>' . number_format($count) . '</>');
                
                if ($count === 0) {
                    $this->newLine();
                    $this->comment('No documents indexed yet. Run: php artisan ai:index-documents');
                }
            }
        } catch (\Exception $e) {
            $this->line('Embeddings Table: <fg=red>✗ ERROR</>');
            $this->newLine();
            $this->error('Error checking table: ' . $e->getMessage());
            return self::FAILURE;
        }
        
        $this->newLine();
        $this->info('═══════════════════════════════════════════════════');
        $this->info('   ✓ AI Intelligence is fully operational!');
        $this->info('═══════════════════════════════════════════════════');
        $this->newLine();
        
        return self::SUCCESS;
    }
}
