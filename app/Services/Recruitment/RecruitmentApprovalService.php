<?php

namespace App\Services\Recruitment;

use App\Models\Recruitment\RecruitmentApprovalRecord;
use App\Models\Recruitment\RecruitmentApprovalWorkflow;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class RecruitmentApprovalService
{
    /**
     * Get the latest-cycle approval trail for this document.
     */
    public static function trail(Model $document, string $documentType): Collection
    {
        $latestCycle = RecruitmentApprovalRecord::query()
            ->where('approvable_type', get_class($document))
            ->where('approvable_id', $document->getKey())
            ->max('submission_cycle') ?? 1;

        return RecruitmentApprovalRecord::query()
            ->where('approvable_type', get_class($document))
            ->where('approvable_id', $document->getKey())
            ->where('submission_cycle', $latestCycle)
            ->orderBy('stage_order')
            ->get();
    }

    /**
     * Get the full history across all submission cycles, grouped by cycle.
     */
    public static function historyTrail(Model $document): Collection
    {
        return RecruitmentApprovalRecord::query()
            ->where('approvable_type', get_class($document))
            ->where('approvable_id', $document->getKey())
            ->orderBy('submission_cycle')
            ->orderBy('stage_order')
            ->get()
            ->groupBy('submission_cycle');
    }

    /**
     * Initialize a new submission cycle from the active workflow.
     */
    public static function initialise(Model $document, string $documentType): void
    {
        $workflow = RecruitmentApprovalWorkflow::query()
            ->where('document_type', $documentType)
            ->where('is_active', true)
            ->first();

        if (! $workflow) {
            return;
        }

        $stages = $workflow->stages()->orderBy('stage_order')->get();

        if ($stages->isEmpty()) {
            return;
        }

        $nextCycle = (RecruitmentApprovalRecord::query()
            ->where('approvable_type', get_class($document))
            ->where('approvable_id', $document->getKey())
            ->max('submission_cycle') ?? 0) + 1;

        $records = $stages->map(fn ($stage) => [
            'approvable_type'  => get_class($document),
            'approvable_id'    => $document->getKey(),
            'stage_id'         => $stage->id,
            'submission_cycle' => $nextCycle,
            'stage_order'      => $stage->stage_order,
            'stage_name'       => $stage->stage_name,
            'required_role'    => $stage->required_role,
            'status'           => 'Pending',
            'created_at'       => now(),
            'updated_at'       => now(),
        ]);

        RecruitmentApprovalRecord::insert($records->toArray());
    }

    /**
     * Find the pending record for the current user in the latest cycle.
     */
    public static function pendingRecordFor(User $user, Model $document, string $documentType): ?RecruitmentApprovalRecord
    {
        $latestCycle = RecruitmentApprovalRecord::query()
            ->where('approvable_type', get_class($document))
            ->where('approvable_id', $document->getKey())
            ->max('submission_cycle') ?? 1;

        // If any stage in the current cycle is rejected, no more approvals
        $hasRejection = RecruitmentApprovalRecord::query()
            ->where('approvable_type', get_class($document))
            ->where('approvable_id', $document->getKey())
            ->where('submission_cycle', $latestCycle)
            ->where('status', 'Rejected')
            ->exists();

        if ($hasRejection) {
            return null;
        }

        $pending = RecruitmentApprovalRecord::query()
            ->where('approvable_type', get_class($document))
            ->where('approvable_id', $document->getKey())
            ->where('submission_cycle', $latestCycle)
            ->where('status', 'Pending')
            ->orderBy('stage_order')
            ->first();

        if (! $pending) {
            return null;
        }

        if ($user->hasRole('super_admin') || $user->hasRole($pending->required_role)) {
            return $pending;
        }

        return null;
    }

    public static function canApprove(User $user, Model $document, string $documentType): bool
    {
        return self::pendingRecordFor($user, $document, $documentType) !== null;
    }

    public static function isFullyApproved(Model $document, string $documentType): bool
    {
        $trail = self::trail($document, $documentType);

        if ($trail->isEmpty()) {
            return false;
        }

        return $trail->every(fn ($record) => $record->status === 'Approved');
    }

    public static function isRejected(Model $document, string $documentType): bool
    {
        return self::trail($document, $documentType)->where('status', 'Rejected')->isNotEmpty();
    }

    public static function currentStageLabel(Model $document, string $documentType): string
    {
        $trail = self::trail($document, $documentType);

        if ($trail->isEmpty()) {
            return 'No Workflow';
        }

        if ($trail->where('status', 'Rejected')->isNotEmpty()) {
            return 'Rejected';
        }

        $nextPending = $trail->where('status', 'Pending')->first();
        if ($nextPending) {
            return 'Awaiting ' . $nextPending->stage_name;
        }

        return 'Fully Approved';
    }

    public static function approve(Model $document, string $documentType, int $stageOrder, User $user, ?string $notes = null): void
    {
        $latestCycle = RecruitmentApprovalRecord::query()
            ->where('approvable_type', get_class($document))
            ->where('approvable_id', $document->getKey())
            ->max('submission_cycle') ?? 1;

        $record = RecruitmentApprovalRecord::where('approvable_type', get_class($document))
            ->where('approvable_id', $document->getKey())
            ->where('submission_cycle', $latestCycle)
            ->where('stage_order', $stageOrder)
            ->firstOrFail();

        $record->update([
            'status'     => 'Approved',
            'decided_by' => $user->id,
            'decided_at' => now(),
            'notes'      => $notes,
        ]);

        if (self::isFullyApproved($document, $documentType)) {
            $document->update(['status' => 'Approved']);
            if (method_exists($document, 'onFullyApproved')) {
                $document->onFullyApproved();
            }
        }
    }

    public static function reject(Model $document, string $documentType, int $stageOrder, User $user, string $notes): void
    {
        $latestCycle = RecruitmentApprovalRecord::query()
            ->where('approvable_type', get_class($document))
            ->where('approvable_id', $document->getKey())
            ->max('submission_cycle') ?? 1;

        $record = RecruitmentApprovalRecord::where('approvable_type', get_class($document))
            ->where('approvable_id', $document->getKey())
            ->where('submission_cycle', $latestCycle)
            ->where('stage_order', $stageOrder)
            ->firstOrFail();

        $record->update([
            'status'     => 'Rejected',
            'decided_by' => $user->id,
            'decided_at' => now(),
            'notes'      => $notes,
        ]);

        // Set status back to Draft so the creator can edit and resubmit
        $document->update(['status' => $document::STATUS_DRAFT]);
    }

    public static function reset(Model $document, string $documentType): void
    {
        RecruitmentApprovalRecord::where('approvable_type', get_class($document))
            ->where('approvable_id', $document->id)
            ->delete();
    }

    /**
     * Get the rejection notes from the most recent rejected approval record.
     */
    public static function previousRejectionNotes(Model $document): ?string
    {
        $rejected = RecruitmentApprovalRecord::query()
            ->where('approvable_type', get_class($document))
            ->where('approvable_id', $document->getKey())
            ->where('status', 'Rejected')
            ->orderByDesc('submission_cycle')
            ->orderByDesc('stage_order')
            ->first();

        return $rejected?->notes;
    }

    /**
     * Whether this document has been through at least one rejection.
     */
    public static function hasBeenRejected(Model $document): bool
    {
        return RecruitmentApprovalRecord::query()
            ->where('approvable_type', get_class($document))
            ->where('approvable_id', $document->getKey())
            ->where('status', 'Rejected')
            ->exists();
    }

    /**
     * Get the timestamp of the most recent rejection decision.
     */
    public static function lastRejectedAt(Model $document): ?\Carbon\Carbon
    {
        $record = RecruitmentApprovalRecord::query()
            ->where('approvable_type', get_class($document))
            ->where('approvable_id', $document->getKey())
            ->where('status', 'Rejected')
            ->orderByDesc('decided_at')
            ->first();

        return $record?->decided_at;
    }

    /**
     * Whether the document was edited after its most recent rejection.
     */
    public static function wasEditedAfterRejection(Model $document): bool
    {
        $rejectedAt = self::lastRejectedAt($document);

        if (! $rejectedAt) {
            return false;
        }

        return $document->updated_at->gt($rejectedAt);
    }

    /**
     * Render the full multi-cycle approval trail as HTML.
     */
    public static function renderApprovalTrailHtml(Model $document, string $documentType): string
    {
        $allRecords = RecruitmentApprovalRecord::query()
            ->where('approvable_type', get_class($document))
            ->where('approvable_id', $document->getKey())
            ->orderBy('submission_cycle')
            ->orderBy('stage_order')
            ->get()
            ->groupBy('submission_cycle');

        if ($allRecords->isEmpty()) {
            return '<div class="text-gray-500 italic text-sm">No workflow stages defined or started.</div>';
        }

        $latestCycle = $allRecords->keys()->max();
        $html = '<div class="space-y-6">';

        foreach ($allRecords as $cycle => $records) {
            $isLatest = ($cycle === $latestCycle);

            if ($allRecords->count() > 1) {
                $cycleLabel = "Submission #{$cycle}" . ($isLatest ? ' (current)' : '');
                $borderColor = $isLatest ? 'border-blue-300 bg-blue-50/30' : 'border-gray-200 bg-gray-50/30';
                $html .= "<div class='rounded-lg border {$borderColor} p-4 space-y-3'>";
                $html .= "<h3 class='text-sm font-bold text-gray-700 mb-2'>{$cycleLabel}</h3>";
            } else {
                $html .= "<div class='space-y-3'>";
            }

            foreach ($records as $record) {
                $html .= self::renderSingleRecord($record);
            }

            $html .= '</div>';
        }

        $html .= '</div>';

        return $html;
    }

    private static function renderSingleRecord(RecruitmentApprovalRecord $record): string
    {
        $statusColor = match ($record->status) {
            'Approved' => 'text-green-600 bg-green-50 drop-shadow-sm',
            'Rejected' => 'text-red-600 bg-red-50 drop-shadow-sm',
            default    => 'text-gray-600 bg-gray-50',
        };

        $icon = match ($record->status) {
            'Approved' => '<svg class="w-5 h-5 text-green-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path></svg>',
            'Rejected' => '<svg class="w-5 h-5 text-red-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg>',
            default    => '<svg class="w-5 h-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>',
        };

        $decidedBy = $record->decidedBy ? "by <strong>{$record->decidedBy->name}</strong>" : '';
        $decidedAt = $record->decided_at ? "on {$record->decided_at->format('M d, Y H:i')}" : '';
        $notes = $record->notes
            ? "<div class='text-sm text-gray-700 mt-2 bg-white p-2 border rounded shadow-sm border-gray-200'><strong>Notes:</strong> {$record->notes}</div>"
            : '';

        $html = "
            <div class='flex items-start bg-white p-3 border rounded-lg shadow-sm w-full border-gray-200'>
                <div class='flex-shrink-0 mt-0.5'>{$icon}</div>
                <div class='ml-3 flex-1'>
                    <div class='flex justify-between items-center'>
                        <h4 class='text-sm font-medium text-gray-900'>{$record->stage_name}</h4>
                        <span class='px-2 py-1 text-xs font-semibold rounded-full {$statusColor} border border-current opacity-75'>{$record->status}</span>
                    </div>
                    <p class='text-xs text-gray-500 mt-1'>Required Role: <code>{$record->required_role}</code></p>";

        if ($record->status !== 'Pending') {
            $html .= "<p class='text-xs text-gray-600 mt-1'>{$record->status} {$decidedBy} {$decidedAt}</p>";
        }

        $html .= $notes;
        $html .= '</div></div>';

        return $html;
    }
}
