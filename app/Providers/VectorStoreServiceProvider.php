<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use App\Services\AI\VectorStore\VectorStoreInterface;
use App\Services\AI\VectorStore\PgVectorStore;
use App\Services\AI\VectorStore\EmbeddingService;

class VectorStoreServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        // Bind the VectorStoreInterface to the configured driver
        $this->app->singleton(VectorStoreInterface::class, function () {
            return new PgVectorStore();
        });

        // Bind EmbeddingService as a singleton
        $this->app->singleton(EmbeddingService::class, function () {
            return new EmbeddingService();
        });
    }

    public function boot(): void
    {
        //
    }
}
