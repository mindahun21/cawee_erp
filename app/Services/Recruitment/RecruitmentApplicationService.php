<?php

namespace App\Services\Recruitment;

use App\Events\Recruitment\CandidateHired;
use App\Models\Recruitment\RecruitmentApplication;
use App\Models\Recruitment\RecruitmentApplicationStatusLog;
use App\Models\User;
use Exception;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

class RecruitmentApplicationService
{
    /**
     * Define the allowed state transitions.
     * Maps 'current_state' => ['allowed', 'next', 'states']
     */
    protected array $allowedTransitions = [
        RecruitmentApplication::STATUS_APPLIED => [
            RecruitmentApplication::STATUS_UNDER_REVIEW,
            RecruitmentApplication::STATUS_REJECTED,
            RecruitmentApplication::STATUS_WITHDRAWN,
        ],
        RecruitmentApplication::STATUS_UNDER_REVIEW => [
            RecruitmentApplication::STATUS_SHORTLISTED,
            RecruitmentApplication::STATUS_REJECTED,
            RecruitmentApplication::STATUS_WITHDRAWN,
        ],
        RecruitmentApplication::STATUS_SHORTLISTED => [
            RecruitmentApplication::STATUS_INTERVIEW_SCHEDULED,
            RecruitmentApplication::STATUS_REJECTED,
            RecruitmentApplication::STATUS_WITHDRAWN,
        ],
        RecruitmentApplication::STATUS_INTERVIEW_SCHEDULED => [
            RecruitmentApplication::STATUS_INTERVIEWED,
            RecruitmentApplication::STATUS_REJECTED,
            RecruitmentApplication::STATUS_WITHDRAWN,
        ],
        RecruitmentApplication::STATUS_INTERVIEWED => [
            RecruitmentApplication::STATUS_SELECTED,
            RecruitmentApplication::STATUS_WAITLISTED,
            RecruitmentApplication::STATUS_REJECTED,
            RecruitmentApplication::STATUS_WITHDRAWN,
        ],
        RecruitmentApplication::STATUS_WAITLISTED => [
            RecruitmentApplication::STATUS_SELECTED,
            RecruitmentApplication::STATUS_REJECTED,
            RecruitmentApplication::STATUS_WITHDRAWN,
        ],
        RecruitmentApplication::STATUS_SELECTED => [
            RecruitmentApplication::STATUS_OFFER_PENDING,
            RecruitmentApplication::STATUS_REJECTED,
            RecruitmentApplication::STATUS_WITHDRAWN,
        ],
        RecruitmentApplication::STATUS_OFFER_PENDING => [
            RecruitmentApplication::STATUS_OFFER_ACCEPTED,
            RecruitmentApplication::STATUS_OFFER_DECLINED, // Candidate rejected offer
            RecruitmentApplication::STATUS_SELECTED,       // Fallback on expiry/revision
            RecruitmentApplication::STATUS_REJECTED,       // Direct rejection after offer fail
            RecruitmentApplication::STATUS_WITHDRAWN,      // Candidate withdrew application
        ],
        RecruitmentApplication::STATUS_OFFER_ACCEPTED => [
            RecruitmentApplication::STATUS_HIRED,
            RecruitmentApplication::STATUS_WITHDRAWN, // Rare: candidate backs out before first day
        ],

        // Terminal states
        RecruitmentApplication::STATUS_OFFER_DECLINED => [],
        RecruitmentApplication::STATUS_HIRED => [],
        RecruitmentApplication::STATUS_REJECTED => [],
        RecruitmentApplication::STATUS_WITHDRAWN => [],
    ];

    /**
     * Transition an application to a new state, enforcing allowed transitions.
     *
     * @throws InvalidArgumentException|Exception
     */
    public function transition(
        RecruitmentApplication $application,
        string $toStatus,
        ?User $byUser = null,
        ?string $reason = null
    ): bool {
        $fromStatus = $application->status;

        // same status -> no op
        if (strtolower($fromStatus) === strtolower($toStatus)) {
            return true;
        }

        // Validate allowed transitions
        if (! $this->canTransition($fromStatus, $toStatus)) {
            throw new InvalidArgumentException("Cannot transition application from '{$fromStatus}' to '{$toStatus}'.");
        }

        DB::beginTransaction();
        try {
            // Perform the status update
            $application->status = $toStatus;
            $application->save();

            // Store in audit log
            RecruitmentApplicationStatusLog::create([
                'application_id' => $application->id,
                'from_status' => $fromStatus,
                'to_status' => $toStatus,
                'changed_by' => $byUser?->id,
                'reason' => $reason,
            ]);

            // Dispatch domain events
            if ($toStatus === RecruitmentApplication::STATUS_HIRED) {
                event(new CandidateHired($application));
            }

            DB::commit();

            return true;

        } catch (Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }

    /**
     * Check if a transition is valid.
     */
    public function canTransition(string $from, string $to): bool
    {
        $from = strtolower($from);
        $to = strtolower($to);

        return in_array($to, $this->allowedTransitions[$from] ?? []);
    }

    /**
     * Get allowed next statuses for an application.
     */
    public function getAllowedNextStatuses(RecruitmentApplication $application): array
    {
        return $this->allowedTransitions[strtolower($application->status)] ?? [];
    }
}
