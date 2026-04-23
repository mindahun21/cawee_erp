<?php

namespace App\Services\Recruitment;

use App\Models\Recruitment\RecruitmentPlan;
use Illuminate\Support\Facades\Log;

class RecruitmentPlanService
{
    /**
     * Update the status of a recruitment plan, ensuring it's a valid transition.
     */
    public function updateStatus(RecruitmentPlan $plan, string $newStatus, ?string $notes = null): bool
    {
        $updateData = ['status' => $newStatus];
        if ($notes !== null) {
            $updateData['notes'] = $notes;
        }

        return $plan->update($updateData);
    }
}
