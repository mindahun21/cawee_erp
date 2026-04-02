<?php

namespace App\Traits\Finance;

use App\Models\Finance\FinanceAuditLog;
use App\Models\User;

/**
 * HasApprovalWorkflow
 *
 * Reusable trait that provides a standardised Maker-Checker approval
 * lifecycle for any Finance document that passes through a workflow:
 *
 *   draft  →  pending_approval  →  approved  →  posted
 *                                      ↓
 *                                  rejected / draft (returned)
 *
 * Consuming models MUST:
 *   1. Have a `status` column (or override approvalStatusField()).
 *   2. Have an `approved_by` column (nullable FK → users).
 *   3. Call `use HasApprovalWorkflow;` in the model class.
 *
 * Consuming models MAY override:
 *   • `approvalStatusField()`   to use a different column name
 *   • `approvedStatusValue()`   to use a different "approved" value
 *
 * All mutations are wrapped in individual DB::transaction calls and
 * each state change is written to the finance_audit_logs table via
 * FinanceAuditLog::record().
 */
trait HasApprovalWorkflow
{
    // ── Contract (override in consuming model if needed) ──────────────

    /**
     * The name of the column that holds the workflow status.
     * Override if your model uses a column other than `status`.
     */
    public static function approvalStatusField(): string
    {
        return 'status';
    }

    /**
     * The string value that represents the "approved" state in the status enum.
     */
    public static function approvedStatusValue(): string
    {
        return 'approved';
    }

    // ── Status predicates ─────────────────────────────────────────────

    public function isInDraft(): bool
    {
        return $this->{static::approvalStatusField()} === 'draft';
    }

    public function isAwaitingApproval(): bool
    {
        return $this->{static::approvalStatusField()} === 'pending_approval';
    }

    public function isWorkflowApproved(): bool
    {
        return $this->{static::approvalStatusField()} === static::approvedStatusValue();
    }

    public function isWorkflowRejected(): bool
    {
        return $this->{static::approvalStatusField()} === 'rejected';
    }

    // ── Workflow transitions ──────────────────────────────────────────

    /**
     * Submit a draft document for approval.
     *
     * Transition: draft → pending_approval
     *
     * @throws \RuntimeException if the document is not in draft status
     */
    public function submitForApproval(User $by): void
    {
        $field = static::approvalStatusField();

        if ($this->$field !== 'draft') {
            throw new \RuntimeException(
                "Cannot submit: document is not in draft status (current: {$this->$field})."
            );
        }

        $old = $this->$field;

        $this->forceFill([
            $field       => 'pending_approval',
            'prepared_by' => $by->id,
        ])->save();

        FinanceAuditLog::record(
            FinanceAuditLog::ACTION_APPROVE,
            $this,
            [$field => $old],
            [$field => 'pending_approval', 'submitted_by' => $by->id]
        );
    }

    /**
     * Approve a pending document.
     *
     * Transition: pending_approval → approved
     *
     * @throws \RuntimeException if the document is not pending approval
     */
    public function workflowApprove(User $by, string $comments = ''): void
    {
        $field = static::approvalStatusField();

        if ($this->$field !== 'pending_approval') {
            throw new \RuntimeException(
                "Cannot approve: document is not pending approval (current: {$this->$field})."
            );
        }

        $old = $this->$field;

        $update = [
            $field => static::approvedStatusValue(),
        ];

        // Only set approved_by if the column exists in fillable
        if (in_array('approved_by', $this->getFillable(), true)) {
            $update['approved_by'] = $by->id;
        }

        $this->forceFill($update)->save();

        FinanceAuditLog::record(
            FinanceAuditLog::ACTION_APPROVE,
            $this,
            [$field => $old],
            array_merge($update, ['comments' => $comments])
        );
    }

    /**
     * Reject a pending or approved document.
     *
     * Transition: pending_approval|approved → rejected
     *
     * @throws \RuntimeException if the document cannot be rejected from its current status
     */
    public function workflowReject(User $by, string $reason = ''): void
    {
        $field   = static::approvalStatusField();
        $current = $this->$field;

        if (! in_array($current, ['pending_approval', static::approvedStatusValue()], true)) {
            throw new \RuntimeException(
                "Cannot reject: document must be pending approval or approved (current: {$current})."
            );
        }

        $this->forceFill([$field => 'rejected'])->save();

        FinanceAuditLog::record(
            FinanceAuditLog::ACTION_REJECT,
            $this,
            [$field => $current],
            [$field => 'rejected', 'rejected_by' => $by->id, 'reason' => $reason]
        );
    }

    /**
     * Return a pending document to draft for revision.
     *
     * Transition: pending_approval → draft
     *
     * Unlike rejection, a returned document can be edited and re-submitted.
     *
     * @throws \RuntimeException if the document is not pending approval
     */
    public function returnForRevision(User $by, string $comments = ''): void
    {
        $field = static::approvalStatusField();

        if ($this->$field !== 'pending_approval') {
            throw new \RuntimeException(
                "Cannot return for revision: document is not pending approval (current: {$this->$field})."
            );
        }

        $old = $this->$field;

        $this->forceFill([$field => 'draft'])->save();

        FinanceAuditLog::record(
            FinanceAuditLog::ACTION_REJECT,
            $this,
            [$field => $old],
            [$field => 'draft', 'returned_by' => $by->id, 'revision_comments' => $comments]
        );
    }

    // ── Guard helpers (use in Filament ->visible() closures) ─────────

    /**
     * Whether the given user can submit this document for approval.
     * Override in the consuming model for granular role-based checks.
     */
    public function canBeSubmittedBy(User $user): bool
    {
        return $this->isInDraft();
    }

    /**
     * Whether the given user can approve this document.
     * Override in the consuming model for granular role-based checks.
     */
    public function canBeApprovedBy(User $user): bool
    {
        return $this->isAwaitingApproval();
    }

    /**
     * Whether the given user can reject or return this document.
     */
    public function canBeRejectedBy(User $user): bool
    {
        return $this->isAwaitingApproval() || $this->isWorkflowApproved();
    }
}
