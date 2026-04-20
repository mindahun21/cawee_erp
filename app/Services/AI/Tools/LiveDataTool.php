<?php

namespace App\Services\AI\Tools;

use App\Models\User;
use App\Services\AI\Data\ModuleDataPreloader;
use Prism\Prism\Facades\Tool;
use Prism\Prism\Tool as PrismTool;
use Illuminate\Support\Facades\Log;

/**
 * LiveDataTool - Prism tool for fetching live ERP data from modules
 * 
 * This tool allows the AI to request live data from specific modules
 * with optional filtering capabilities.
 */
class LiveDataTool
{
    protected array $preloaders = [];

    public function __construct()
    {
        // Register available preloaders
        $this->preloaders = [
            'recruitment' => \App\Services\AI\Data\RecruitmentDataPreloader::class,
            'procurement' => \App\Services\AI\Data\ProcurementDataPreloader::class,
        ];
    }

    /**
     * Convert this tool to a Prism Tool definition
     */
    public function asPrismTool(): PrismTool
    {
        try {
            return Tool::as('get_live_data')
                ->for('Fetch live ERP data from specific modules with optional filters. Use this when the user asks about current data, statistics, or real-time information from the system. You can optionally provide filters as a JSON object with keys: status (string), date_range (string: last_7_days, last_30_days, this_month, last_month, etc), limit (integer: 1-100), search (string).')
                ->withStringParameter(
                    'module',
                    'The module to query for live data. Available: recruitment, procurement',
                    true,
                    ['recruitment', 'procurement']
                )
                ->withStringParameter(
                    'filters_json',
                    'Optional JSON string of filters to apply. Example: {"status":"active","limit":10}',
                    false
                )
                ->using(function (string $module, ?string $filters_json = null) {
                    // Parse filters from JSON
                    $filters = [];
                    if ($filters_json) {
                        try {
                            $filters = json_decode($filters_json, true) ?? [];
                        } catch (\Exception $e) {
                            Log::warning("LiveDataTool: Failed to parse filters JSON", [
                                'filters_json' => $filters_json,
                                'error' => $e->getMessage(),
                            ]);
                        }
                    }
                    return $this->execute($module, $filters);
                });
        } catch (\Exception $e) {
            Log::error("LiveDataTool: Failed to create Prism tool", [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            
            // Return a fallback tool that just returns an error message
            return Tool::as('get_live_data_fallback')
                ->for('Fetch live ERP data (currently unavailable)')
                ->withStringParameter('module', 'Module name', true)
                ->using(fn(string $module) => "Error: Live data tool is currently unavailable. Please try again later.");
        }
    }

    /**
     * Execute the tool - fetch live data from the specified module
     */
    public function execute(string $module, array $filters = []): string
    {
        try {
            // Validate module
            if (!isset($this->preloaders[$module])) {
                return "Error: Module '{$module}' is not available. Available modules: " . implode(', ', array_keys($this->preloaders));
            }

            // Get the preloader class
            $preloaderClass = $this->preloaders[$module];
            
            // Instantiate the preloader
            /** @var ModuleDataPreloader $preloader */
            $preloader = app($preloaderClass);

            // Check if user has permission (get from auth)
            $user = auth()->user();
            if (!$user instanceof User) {
                return "Error: User not authenticated";
            }

            if (!$preloader->canAccess($user)) {
                return "Error: You do not have permission to access {$module} data. Required permission: {$preloader->getRequiredPermission()}";
            }

            // Log the request
            Log::info("LiveDataTool: Fetching {$module} data", [
                'user_id' => $user->id,
                'filters' => $filters,
            ]);

            // Generate the snapshot with filters
            $snapshot = $preloader->snapshot($filters);

            return $snapshot;

        } catch (\Exception $e) {
            Log::error("LiveDataTool: Error fetching {$module} data", [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return "Error: Failed to fetch {$module} data. " . $e->getMessage();
        }
    }

    /**
     * Get available modules
     */
    public function getAvailableModules(): array
    {
        return array_keys($this->preloaders);
    }

    /**
     * Register a new preloader
     */
    public function registerPreloader(string $module, string $preloaderClass): void
    {
        $this->preloaders[$module] = $preloaderClass;
    }
}

