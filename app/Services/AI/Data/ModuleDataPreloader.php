<?php

namespace App\Services\AI\Data;

use App\Models\User;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;

/**
 * Abstract base class for module-specific data preloaders.
 * 
 * Each module implements this to provide live data snapshots
 * with optional filtering capabilities.
 */
abstract class ModuleDataPreloader
{
    /**
     * Get the module identifier (e.g., 'recruitment', 'procurement')
     */
    abstract public function getModuleName(): string;

    /**
     * Get the required permission to access this module's data
     */
    abstract public function getRequiredPermission(): string;

    /**
     * Generate a data snapshot with optional filters
     * 
     * @param array $filters Optional filters to apply
     * @return string Formatted text snapshot of module data
     */
    abstract public function snapshot(array $filters = []): string;

    /**
     * Get available filter definitions for this module
     * 
     * @return array Filter definitions
     */
    public function getAvailableFilters(): array
    {
        return [
            'status' => [
                'type' => 'string',
                'description' => 'Filter by status',
                'values' => $this->getStatusValues(),
            ],
            'date_range' => [
                'type' => 'string',
                'description' => 'Filter by time period',
                'values' => [
                    'today', 'yesterday', 'last_7_days', 'last_30_days', 'last_90_days',
                    'this_week', 'last_week', 'this_month', 'last_month',
                    'this_quarter', 'last_quarter', 'this_year', 'last_year',
                ],
            ],
            'limit' => [
                'type' => 'integer',
                'description' => 'Maximum number of records',
                'default' => 10,
                'range' => [1, 100],
            ],
            'search' => [
                'type' => 'string',
                'description' => 'Search term for text fields',
            ],
        ];
    }

    /**
     * Get status values for this module (override in subclasses)
     */
    protected function getStatusValues(): array
    {
        return [];
    }

    /**
     * Parse date_range filter into Carbon date
     * 
     * @param string $range Date range identifier
     * @return Carbon
     */
    protected function parseDateRange(string $range): Carbon
    {
        return match ($range) {
            'today' => now()->startOfDay(),
            'yesterday' => now()->subDay()->startOfDay(),
            'last_7_days' => now()->subDays(7)->startOfDay(),
            'last_30_days' => now()->subDays(30)->startOfDay(),
            'last_90_days' => now()->subDays(90)->startOfDay(),
            'this_week' => now()->startOfWeek(),
            'last_week' => now()->subWeek()->startOfWeek(),
            'this_month' => now()->startOfMonth(),
            'last_month' => now()->subMonth()->startOfMonth(),
            'this_quarter' => now()->startOfQuarter(),
            'last_quarter' => now()->subQuarter()->startOfQuarter(),
            'this_year' => now()->startOfYear(),
            'last_year' => now()->subYear()->startOfYear(),
            default => now()->subDays(30)->startOfDay(),
        };
    }

    /**
     * Apply common filters to a query builder
     * 
     * @param Builder $query
     * @param array $filters
     * @param string $dateField Default date field to filter on
     * @return Builder
     */
    protected function applyFilters(Builder $query, array $filters, string $dateField = 'created_at'): Builder
    {
        // Status filter
        if (isset($filters['status']) && !empty($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        // Date range filter
        if (isset($filters['date_range']) && !empty($filters['date_range'])) {
            $field = $filters['date_field'] ?? $dateField;
            $fromDate = $this->parseDateRange($filters['date_range']);
            $query->where($field, '>=', $fromDate);
        }

        // Search filter (override in subclasses for specific fields)
        if (isset($filters['search']) && !empty($filters['search'])) {
            $this->applySearchFilter($query, $filters['search']);
        }

        // Limit filter
        if (isset($filters['limit']) && is_numeric($filters['limit'])) {
            $limit = max(1, min(100, (int)$filters['limit']));
            $query->limit($limit);
        } else {
            $query->limit(10); // Default limit
        }

        return $query;
    }

    /**
     * Apply search filter (override in subclasses for module-specific search)
     * 
     * @param Builder $query
     * @param string $search
     * @return void
     */
    protected function applySearchFilter(Builder $query, string $search): void
    {
        // Default implementation - subclasses should override
        // Example: $query->where('name', 'like', "%{$search}%");
    }

    /**
     * Format a number with thousands separator
     */
    protected function formatNumber($number): string
    {
        return number_format($number, 0);
    }

    /**
     * Format a decimal number
     */
    protected function formatDecimal($number, int $decimals = 2): string
    {
        return number_format($number, $decimals);
    }

    /**
     * Format a percentage
     */
    protected function formatPercent($value, int $decimals = 1): string
    {
        return number_format($value, $decimals) . '%';
    }

    /**
     * Check if user has permission to access this module
     */
    public function canAccess(User $user): bool
    {
        return $user->can($this->getRequiredPermission());
    }
}

