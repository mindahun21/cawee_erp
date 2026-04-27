<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use App\Services\AI\VectorStore\VectorStoreInterface;
use App\Services\AI\VectorStore\PgVectorStore;
use App\Services\AI\VectorStore\NullVectorStore;
use App\Services\AI\VectorStore\EmbeddingService;
use App\Support\ModuleManager;
use App\Support\VectorDatabaseDetector;

class VectorStoreServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        // Only register AI services if the ai_intelligence module is enabled
        if (!ModuleManager::isEnabled('ai_intelligence')) {
            // Module is disabled - register null implementation
            $this->app->singleton(VectorStoreInterface::class, function () {
                return new NullVectorStore();
            });
            return;
        }
        
        // Module is enabled - check if vector database is available
        $this->app->singleton(VectorStoreInterface::class, function () {
            if (VectorDatabaseDetector::isAvailable()) {
                // Vector database is available - use real implementation
                return new PgVectorStore();
            }
            // Vector database unavailable - use null implementation
            return new NullVectorStore();
        });

        // Only register EmbeddingService if AI is fully ready (module enabled AND database available)
        if (VectorDatabaseDetector::isAiReady()) {
            $this->app->singleton(EmbeddingService::class, function () {
                return new EmbeddingService();
            });
        }
    }

    public function boot(): void
    {
        //
    }
}

