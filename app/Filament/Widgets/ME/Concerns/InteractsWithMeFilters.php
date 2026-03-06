<?php

namespace App\Filament\Widgets\ME\Concerns;

trait InteractsWithMeFilters
{
    protected function getMeFilters(): array
    {
        $filters = $this->pageFilters ?? [];

        return [
            'date_from' => $filters['date_from'] ?? null,
            'date_to' => $filters['date_to'] ?? null,
            'framework_type' => $filters['framework_type'] ?? null,
            'location' => $filters['location'] ?? null,
        ];
    }
}
