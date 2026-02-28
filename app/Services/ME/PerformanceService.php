<?php

namespace App\Services\ME;

class PerformanceService
{
    public function computeProgress(float $actualValue, float $targetValue): float
    {
        if ($targetValue <= 0) {
            return 0.0;
        }

        return round(($actualValue / $targetValue) * 100, 2);
    }

    public function statusFromProgress(float $progressPercent): string
    {
        if ($progressPercent >= 90) {
            return 'on_track';
        }

        if ($progressPercent >= 70) {
            return 'needs_attention';
        }

        return 'off_track';
    }
}
