<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations on the vector database connection.
     */
    public function up(): void
    {
        // Enable pgvector extension
        DB::connection('vector')->statement('CREATE EXTENSION IF NOT EXISTS vector');

        // Get embedding dimensions from config
        $dimensions = (int) env('EMBEDDING_DIMENSIONS', 768);

        // Check if table already exists (from old ensureSchema method)
        $tableExists = DB::connection('vector')
            ->select("SELECT EXISTS (SELECT FROM information_schema.tables WHERE table_name = 'ai_embeddings')")[0]->exists ?? false;

        if (!$tableExists) {
            // Create the ai_embeddings table on the vector database
            Schema::connection('vector')->create('ai_embeddings', function (Blueprint $table) use ($dimensions) {
                $table->id();
                $table->string('source', 500)->index();
                $table->text('content');
                $table->string('file_hash', 64)->nullable()->index();
                $table->bigInteger('file_size')->nullable();
                $table->timestamp('file_modified_at')->nullable();
                $table->jsonb('metadata')->default('{}');
                $table->timestamps();
            });

            // Add the vector column using raw SQL
            DB::connection('vector')->statement("
                ALTER TABLE ai_embeddings 
                ADD COLUMN embedding vector({$dimensions})
            ");
        } else {
            // Table exists, just add missing columns if they don't exist
            $columns = DB::connection('vector')
                ->select("SELECT column_name FROM information_schema.columns WHERE table_name = 'ai_embeddings'");
            
            $existingColumns = array_column($columns, 'column_name');
            
            if (!in_array('file_hash', $existingColumns)) {
                DB::connection('vector')->statement("ALTER TABLE ai_embeddings ADD COLUMN file_hash VARCHAR(64)");
            }
            
            if (!in_array('file_size', $existingColumns)) {
                DB::connection('vector')->statement("ALTER TABLE ai_embeddings ADD COLUMN file_size BIGINT");
            }
            
            if (!in_array('file_modified_at', $existingColumns)) {
                DB::connection('vector')->statement("ALTER TABLE ai_embeddings ADD COLUMN file_modified_at TIMESTAMP");
            }
        }

        // Create indexes (IF NOT EXISTS is safe)
        DB::connection('vector')->statement("
            CREATE INDEX IF NOT EXISTS idx_embeddings_source ON ai_embeddings(source)
        ");
        
        DB::connection('vector')->statement("
            CREATE INDEX IF NOT EXISTS idx_embeddings_source_hash ON ai_embeddings(source, file_hash)
        ");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::connection('vector')->dropIfExists('ai_embeddings');
    }
};
