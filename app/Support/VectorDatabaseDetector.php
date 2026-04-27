<?php

namespace App\Support;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * Detects whether the vector database (PostgreSQL with pgvector) is available.
 * 
 * This utility is used to enable graceful degradation when deploying on hosting
 * environments that don't have PostgreSQL/pgvector (e.g., cPanel with MySQL only).
 */
class VectorDatabaseDetector
{
    /** @var bool|null Cached availability status (null = not yet checked) */
    protected static ?bool $available = null;
    
    /**
     * Check if the vector database connection is available and functional.
     * 
     * This method caches the result to avoid repeated connection attempts.
     * 
     * @return bool True if vector database is connected, false otherwise
     */
    public static function isAvailable(): bool
    {
        // Return cached result if already checked
        if (self::$available !== null) {
            return self::$available;
        }
        
        try {
            // Attempt to get PDO connection to vector database
            DB::connection('vector')->getPdo();
            self::$available = true;
            return true;
        } catch (\Exception $e) {
            // Log warning but don't throw - allow application to continue
            Log::warning('Vector database unavailable', [
                'error' => $e->getMessage(),
                'host' => config('database.connections.vector.host'),
                'port' => config('database.connections.vector.port'),
                'hint' => 'AI Intelligence features will be disabled'
            ]);
            self::$available = false;
            return false;
        }
    }
    
    /**
     * Reset the cached availability check.
     * 
     * Useful for testing or when database configuration changes at runtime.
     * 
     * @return void
     */
    public static function flush(): void
    {
        self::$available = null;
    }
    
    /**
     * Check if AI Intelligence module is fully ready (enabled AND database available).
     * 
     * This is a convenience method that combines module status and database checks.
     * 
     * @return bool True if AI module is enabled and vector database is available
     */
    public static function isAiReady(): bool
    {
        return ModuleManager::isEnabled('ai_intelligence') 
            && self::isAvailable();
    }
}
