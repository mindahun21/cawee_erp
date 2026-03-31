<?php

namespace App\Services\Recruitment;

use App\Contracts\Recruitment\Approvable;
use App\Models\Recruitment\RecruitmentApprovalRecord;
use App\Models\Recruitment\RecruitmentApprovalWorkflow;
use App\Models\User;
use Filament\Actions\Action as NotificationAction;
use Filament\Notifications\Notification as FilamentNotification;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;

class RecruitmentApprovalService
{
    /**
     * Get base query for records related to a document.
     */
    private static function recordQuery(Model $document): Builder
    {
        return RecruitmentApprovalRecord::query()
            ->where('approvable_type', get_class($document))
            ->where('approvable_id', $document->getKey());
    }

    /**
     * Get the latest submission cycle.
     */
    private static function getLatestCycle(Model $document): int
    {
        return self::recordQuery($document)->max('submission_cycle') ?? 1;
    }

    public static function trail(Model $document, string $documentType): Collection
    {
        return self::recordQuery($document)
            ->where('submission_cycle', self::getLatestCycle($document))
            ->orderBy('stage_order')
            ->get();
    }

    public static function historyTrail(Model $document): Collection
    {
        return self::recordQuery($document)
            ->orderBy('submission_cycle')
            ->orderBy('stage_order')
            ->get()
            ->groupBy('submission_cycle');
    }

    public static function initialise(Model $document, string $documentType): void
    {
        $workflow = null;
        if (! empty($document->approval_workflow_id)) {
            $workflow = RecruitmentApprovalWorkflow::find($document->approval_workflow_id);
        }

        if (! $workflow) {
            $workflow = RecruitmentApprovalWorkflow::query()
                ->where('document_type', $documentType)
                ->where('is_active', true)
                ->first();
        }

        if (! $workflow) {
            return;
        }

        $stages = $workflow->stages()->orderBy('stage_order')->get();
        if ($stages->isEmpty()) {
            return;
        }

        $nextCycle = (self::recordQuery($document)->max('submission_cycle') ?? 0) + 1;

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

    public static function pendingRecordFor(User $user, Model $document, string $documentType): ?RecruitmentApprovalRecord
    {
        $latestCycle = self::getLatestCycle($document);

        $hasRejection = self::recordQuery($document)
            ->where('submission_cycle', $latestCycle)
            ->where('status', 'Rejected')
            ->exists();

        if ($hasRejection) {
            return null;
        }

        $pending = self::recordQuery($document)
            ->where('submission_cycle', $latestCycle)
            ->where('status', 'Pending')
            ->orderBy('stage_order')
            ->first();

        if (! $pending) {
            return null;
        }

        if ($user->hasRole($pending->required_role)) {
            $hasApprovedInCurrentCycle = self::recordQuery($document)
                ->where('submission_cycle', $latestCycle)
                ->where('status', 'Approved')
                ->where('decided_by', $user->id)
                ->exists();

            if ($hasApprovedInCurrentCycle) {
                return null;
            }

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
        $record = self::recordQuery($document)
            ->where('submission_cycle', self::getLatestCycle($document))
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
        $record = self::recordQuery($document)
            ->where('submission_cycle', self::getLatestCycle($document))
            ->where('stage_order', $stageOrder)
            ->firstOrFail();

        $record->update([
            'status'     => 'Rejected',
            'decided_by' => $user->id,
            'decided_at' => now(),
            'notes'      => $notes,
        ]);

        if ($document instanceof Approvable) {
            $document->onRejected();
        } elseif (method_exists($document, 'onRejected')) {
            $document->onRejected();
        } else {
            $document->update(['status' => 'Draft']);
        }
    }

    public static function reset(Model $document, string $documentType): void
    {
        self::recordQuery($document)->delete();
    }

    public static function previousRejectionNotes(Model $document): ?string
    {
        $rejected = self::recordQuery($document)
            ->where('status', 'Rejected')
            ->orderByDesc('submission_cycle')
            ->orderByDesc('stage_order')
            ->first();

        return $rejected?->notes;
    }

    public static function hasBeenRejected(Model $document): bool
    {
        return self::recordQuery($document)
            ->where('status', 'Rejected')
            ->exists();
    }

    public static function lastRejectedAt(Model $document): ?\Carbon\Carbon
    {
        $record = self::recordQuery($document)
            ->where('status', 'Rejected')
            ->orderByDesc('decided_at')
            ->first();

        return $record?->decided_at;
    }

    public static function wasEditedAfterRejection(Model $document): bool
    {
        $rejectedAt = self::lastRejectedAt($document);
        if (! $rejectedAt) {
            return false;
        }
        return $document->updated_at->gt($rejectedAt);
    }


    public static function submitForApproval(Model&Approvable $document): void
    {
        DB::transaction(function () use ($document) {
            self::initialise($document, $document->approvalDocumentType());
            $document->update(['status' => $document->submittedStatus()]);
            self::notifyNextStageApprover($document);
        });
    }

    public static function approveStage(Model&Approvable $document, User $user, ?string $notes = null): bool
    {
        return DB::transaction(function () use ($document, $user, $notes) {
            $pending = self::pendingRecordFor($user, $document, $document->approvalDocumentType());

            if (! $pending) {
                return false;
            }

            self::approve($document, $document->approvalDocumentType(), $pending->stage_order, $user, $notes);

            $fullyApproved = self::isFullyApproved($document, $document->approvalDocumentType());

            if (! $fullyApproved) {
                self::notifyNextStageApprover($document);
            }

            return $fullyApproved;
        });
    }

    public static function rejectStage(Model&Approvable $document, User $user, string $notes): void
    {
        DB::transaction(function () use ($document, $user, $notes) {
            $pending = self::pendingRecordFor($user, $document, $document->approvalDocumentType());

            if (! $pending) {
                return;
            }

            self::reject($document, $document->approvalDocumentType(), $pending->stage_order, $user, $notes);
        });
    }

    private static function notifyNextStageApprover(Model&Approvable $document): void
    {
        $nextPending = self::nextPendingRecord($document, $document->approvalDocumentType());

        if (! $nextPending) {
            return;
        }

        $approvers = User::role($nextPending->required_role)->get();
        $docLabel = ucwords(str_replace('_', ' ', $document->approvalDocumentType()));
        $title = $document->title ?? $document->getKey();

        // Support dynamic resolution if implemented via OCP
        $viewUrl = method_exists($document, 'getApprovalViewUrl') 
            ? $document->getApprovalViewUrl() 
            : '#';

        $mailable = method_exists($document, 'getApprovalSubmittedMailable') 
            ? $document->getApprovalSubmittedMailable($viewUrl) 
            : null;

        if (! $mailable) {
            if ($document->approvalDocumentType() === 'recruitment_plan') {
                $viewUrl = \App\Filament\Resources\Recruitment\RecruitmentPlans\RecruitmentPlanResource::getUrl('view', ['record' => $document]);
                $mailable = new \App\Mail\Recruitment\RecruitmentPlanSubmittedMail($document, $viewUrl);
            } elseif ($document->approvalDocumentType() === 'recruitment_campaign') {
                $viewUrl = \App\Filament\Resources\Recruitment\RecruitmentCampaigns\RecruitmentCampaignResource::getUrl('view', ['record' => $document]);
                $mailable = new \App\Mail\Recruitment\RecruitmentCampaignSubmittedMail($document, $viewUrl);
            } elseif ($document->approvalDocumentType() === 'recruitment_interview_schedule') {
                $viewUrl = \App\Filament\Resources\Recruitment\RecruitmentInterviewSchedules\RecruitmentInterviewScheduleResource::getUrl('view', ['record' => $document]);
                $mailable = new \App\Mail\Recruitment\RecruitmentInterviewScheduleSubmittedMail($document, $viewUrl);
            } elseif ($document->approvalDocumentType() === 'recruitment_offer') {
                $viewUrl = \App\Filament\Resources\Recruitment\RecruitmentOffers\RecruitmentOfferResource::getUrl('view', ['record' => $document]);
                $mailable = new \App\Mail\Recruitment\RecruitmentOfferSubmittedMail($document, $viewUrl);
            }
        }

        foreach ($approvers as $approver) {
            if (isset($mailable)) {
                Mail::to($approver->email)->queue($mailable);
            }

            FilamentNotification::make()
                ->title("{$docLabel} Awaiting Your Approval")
                ->body("Stage \"{$nextPending->stage_name}\" for \"{$title}\" requires your review.")
                ->icon('heroicon-o-clock')
                ->iconColor('warning')
                ->actions([
                    NotificationAction::make('review')
                        ->label('Review')
                        ->url($viewUrl)
                        ->markAsRead(),
                ])
                ->sendToDatabase($approver);
        }
    }

    public static function nextPendingRecord(Model $document, string $documentType): ?RecruitmentApprovalRecord
    {
        return self::recordQuery($document)
            ->where('submission_cycle', self::getLatestCycle($document))
            ->where('status', 'Pending')
            ->orderBy('stage_order')
            ->first();
    }

    public static function renderApprovalTrailHtml(Model $document, string $documentType): string
    {
        return view('components.recruitment.approval-trail', [
            'document' => $document,
            'documentType' => $documentType
        ])->render();
    }
}
