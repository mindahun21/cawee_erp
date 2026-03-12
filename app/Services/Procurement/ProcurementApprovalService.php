<?php

namespace App\Services\Procurement;

use App\Models\Procurement\ProcurementApprovalRecord;
use App\Models\Procurement\ProcurementApprovalWorkflow;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;
use Illuminate\Support\HtmlString;

/**
 * ProcurementApprovalService
 *
 * Central service for the dynamic, configurable approval workflow system.
 * Resources call into this service instead of hard-coding approval logic.
 *
 * Usage:
 *   $trail  = ProcurementApprovalService::trail($invoice, 'invoice');
 *   $can    = ProcurementApprovalService::canAct(auth()->user(), $invoice, 'invoice');
 *   ProcurementApprovalService::approve($invoice, 'invoice', $stageOrder, auth()->user(), $notes);
 */
class ProcurementApprovalService
{
    // ── Trail ─────────────────────────────────────────────────────────

    /**
     * Returns the approval trail for a document:
     *  - If no records exist yet, initialises them from the active workflow.
     *  - Returns in stage_order order.
     */
    public static function trail(Model $model, string $documentType): Collection
    {
        $morphType = $model::class;
        $morphId   = $model->getKey();

        $records = ProcurementApprovalRecord::where('approvable_type', $morphType)
            ->where('approvable_id', $morphId)
            ->orderBy('stage_order')
            ->get();

        $isDraft = match ($documentType) {
            'invoice', 'tender' => $model->status === 'Draft',
            'payment' => $model->status === 'Scheduled',
            'purchase_order', 'requisition' => $model->overall_status === 'Draft',
            'goods_receipt' => in_array($model->status, ['Draft', 'Inspecting']),
            'contract' => $model->status === 'Draft',
            'bid' => in_array($model->status, ['Submitted', 'Shortlisted', 'Under Review']),
            default => false,
        };

        if ($records->isEmpty() && !$isDraft) {
            self::initialise($model, $documentType);
            $records = ProcurementApprovalRecord::where('approvable_type', $morphType)
                ->where('approvable_id', $morphId)
                ->with('decidedBy')
                ->orderBy('stage_order')
                ->get();
        } else {
            $records->load('decidedBy');
        }

        return $records;
    }

    /**
     * Initialises pending approval records from the active workflow.
     * Called automatically by trail() if records don't exist yet.
     */
    public static function initialise(Model $model, string $documentType): void
    {
        $workflow = ProcurementApprovalWorkflow::activeFor($documentType);
        if (! $workflow) {
            return;
        }

        $morphType = $model::class;
        $morphId   = $model->getKey();

        foreach ($workflow->stages as $stage) {
            ProcurementApprovalRecord::firstOrCreate(
                [
                    'approvable_type' => $morphType,
                    'approvable_id'   => $morphId,
                    'stage_order'     => $stage->stage_order,
                ],
                [
                    'stage_id'      => $stage->id,
                    'stage_name'    => $stage->stage_name,
                    'required_role' => $stage->required_role,
                    'status'        => 'Pending',
                ]
            );
        }
    }

    // ── Predicates ────────────────────────────────────────────────────

    /**
     * Can the given user act (approve or reject) on this document right now?
     * Returns the pending record the user can act on, or null.
     */
    public static function pendingRecordFor(User $user, Model $model, string $documentType): ?ProcurementApprovalRecord
    {
        // 💡 Gracefully initialize trail if missing for old submitted documents
        self::trail($model, $documentType);

        $morphType = $model::class;
        $morphId   = $model->getKey();

        $pending = ProcurementApprovalRecord::where('approvable_type', $morphType)
            ->where('approvable_id', $morphId)
            ->where('status', 'Pending')
            ->with('stage')          // eager-load for can_reject check
            ->orderBy('stage_order')
            ->get();

        foreach ($pending as $record) {
            // All earlier stages must be approved first
            $earlier = ProcurementApprovalRecord::where('approvable_type', $morphType)
                ->where('approvable_id', $morphId)
                ->where('stage_order', '<', $record->stage_order)
                ->where('status', '!=', 'Approved')
                ->exists();

            if ($earlier) {
                continue; // earlier stage not approved yet
            }

            if ($user->hasRole($record->required_role) || $user->isSuperAdmin()) {
                return $record;
            }
        }

        return null;
    }

    public static function canApprove(User $user, Model $model, string $documentType): bool
    {
        return self::pendingRecordFor($user, $model, $documentType) !== null;
    }

    public static function isFullyApproved(Model $model, string $documentType): bool
    {
        $morphType = $model::class;
        $morphId   = $model->getKey();

        $records = ProcurementApprovalRecord::where('approvable_type', $morphType)
            ->where('approvable_id', $morphId)
            ->get();

        if ($records->isEmpty()) {
            return false;
        }

        return $records->every(fn ($r) => $r->status === 'Approved');
    }

    public static function isRejected(Model $model, string $documentType): bool
    {
        return ProcurementApprovalRecord::where('approvable_type', $model::class)
            ->where('approvable_id', $model->getKey())
            ->where('status', 'Rejected')
            ->exists();
    }

    /**
     * Human-readable current stage label for list view badges.
     */
    public static function currentStageLabel(Model $model, string $documentType): string
    {
        // Use trail() so that existing submitted records without a trail are gracefully initialized
        $records = self::trail($model, $documentType);

        if ($records->isEmpty()) {
            return 'Not Started';
        }

        foreach ($records as $record) {
            if ($record->status === 'Rejected') {
                return "Rejected at {$record->stage_name}";
            }
            if ($record->status === 'Pending') {
                return "Awaiting {$record->stage_name}";
            }
        }

        return 'Fully Approved ✓';
    }

    // ── Actions ───────────────────────────────────────────────────────

    public static function approve(
        Model $model,
        string $documentType,
        int $stageOrder,
        User $user,
        ?string $notes = null
    ): void {
        $morphType = $model::class;
        $morphId   = $model->getKey();

        ProcurementApprovalRecord::where('approvable_type', $morphType)
            ->where('approvable_id', $morphId)
            ->where('stage_order', $stageOrder)
            ->update([
                'status'     => 'Approved',
                'decided_by' => $user->id,
                'decided_at' => now(),
                'notes'      => $notes,
            ]);
    }

    public static function reject(
        Model $model,
        string $documentType,
        int $stageOrder,
        User $user,
        ?string $notes = null
    ): void {
        $morphType = $model::class;
        $morphId   = $model->getKey();

        ProcurementApprovalRecord::where('approvable_type', $morphType)
            ->where('approvable_id', $morphId)
            ->where('stage_order', $stageOrder)
            ->update([
                'status'     => 'Rejected',
                'decided_by' => $user->id,
                'decided_at' => now(),
                'notes'      => $notes,
            ]);
    }

    // ── HTML Rendering (used by Filament form Placeholders) ──────────

    /**
     * Returns an HtmlString rendering the approval trail for embedding
     * in any Filament Placeholder component.
     */
    public static function renderApprovalTrailHtml(?Model $record, string $documentType): HtmlString
    {
        $trail = collect();
        if ($record?->exists) {
            $trail = self::trail($record, $documentType);
        }

        if ($trail->isEmpty()) {
            $activeWorkflow = \App\Models\Procurement\ProcurementApprovalWorkflow::activeFor($documentType);

            if ($activeWorkflow && $activeWorkflow->stages->count() > 0) {
                $rows = '';
                foreach ($activeWorkflow->stages as $stage) {
                    $rows .= <<<HTML
                    <div class="fi-approval-stage fi-approval-pending-submission">
                        <div class="fi-approval-left">
                            <span class="fi-approval-icon" style="color:#9ca3af;">◉</span>
                            <div>
                                <div class="fi-approval-stage-name">{$stage->name}</div>
                                <div class="fi-approval-stage-meta">Stage {$stage->stage_order} &nbsp;·&nbsp; {$stage->required_role}</div>
                            </div>
                        </div>
                        <div class="fi-approval-right">
                            <span class="fi-approval-badge fi-approval-badge-pending">Pending Submission</span>
                            <div class="fi-approval-subtext">Workflow starts once submitted</div>
                        </div>
                    </div>
                    HTML;
                }
                return new HtmlString(self::wrapTrail($rows));
            }

            return new HtmlString(
                '<p class="fi-approval-empty-msg">No approval workflow configured for this document type. ' .
                'Set one up under <strong>Procurement → Settings → Approval Workflows</strong>.</p>'
            );
        }

        $rows = '';
        foreach ($trail as $step) {
            [$badgeClass, $iconColor, $icon] = match ($step->status) {
                'Approved' => ['fi-approval-badge-approved', '#16a34a', '✓'],
                'Rejected' => ['fi-approval-badge-rejected', '#dc2626', '✗'],
                default    => ['fi-approval-badge-awaiting', '#d97706', '○'],
            };

            $stageClass = match ($step->status) {
                'Approved' => 'fi-approval-stage-approved',
                'Rejected' => 'fi-approval-stage-rejected',
                default    => 'fi-approval-stage-awaiting',
            };

            $decider   = e($step->decidedBy?->name ?? '—');
            $decidedAt = $step->decided_at ? $step->decided_at->format('d M Y, H:i') : '—';
            $notes     = $step->notes
                ? '<div class="fi-approval-notes">💬 ' . e($step->notes) . '</div>'
                : '';

            $rows .= <<<HTML
            <div class="fi-approval-stage {$stageClass}">
                <div class="fi-approval-left">
                    <span class="fi-approval-icon" style="color:{$iconColor};">{$icon}</span>
                    <div>
                        <div class="fi-approval-stage-name">{$step->stage_name}</div>
                        <div class="fi-approval-stage-meta">Stage {$step->stage_order} &nbsp;·&nbsp; {$step->required_role}</div>
                    </div>
                </div>
                <div class="fi-approval-right">
                    <span class="fi-approval-badge {$badgeClass}">{$step->status}</span>
                    <div class="fi-approval-subtext">{$decider} &nbsp;·&nbsp; {$decidedAt}</div>
                    {$notes}
                </div>
            </div>
            HTML;
        }

        return new HtmlString(self::wrapTrail($rows));
    }

    private static function wrapTrail(string $rows): string
    {
        return <<<HTML
        <style>
            .fi-approval-trail { display: flex; flex-direction: column; gap: 8px; padding: 4px 0; }
            .fi-approval-stage {
                display: flex; align-items: center; justify-content: space-between;
                padding: 12px 16px; border-radius: 8px; border: 1px solid;
            }
            .fi-approval-stage-approved  { border-color: #bbf7d0; }
            .fi-approval-stage-rejected  { border-color: #fecaca; }
            .fi-approval-stage-awaiting  { border-color: #fde68a; }
            .fi-approval-pending-submission { border-color: #e5e7eb; border-style: dashed; opacity: 0.85; }
            .fi-approval-left  { display: flex; align-items: center; gap: 12px; }
            .fi-approval-right { text-align: right; }
            .fi-approval-icon  { font-size: 1.1rem; min-width: 22px; text-align: center; font-weight: 700; }
            .fi-approval-stage-name { font-weight: 600; font-size: .875rem; color: #ffffff; }
            .fi-approval-stage-meta { font-size: .75rem; color: #d1d5db; margin-top: 2px; }
            .fi-approval-badge {
                display: inline-block; font-size: .7rem; font-weight: 700;
                padding: 2px 10px; border-radius: 9999px; letter-spacing: .03em; text-transform: uppercase;
            }
            .fi-approval-badge-approved  { background: #16a34a; color: #ffffff; }
            .fi-approval-badge-rejected  { background: #dc2626; color: #ffffff; }
            .fi-approval-badge-awaiting  { background: #d97706; color: #ffffff; }
            .fi-approval-badge-pending   { background: #6b7280; color: #ffffff; }
            .fi-approval-subtext { font-size: .73rem; color: #9ca3af; margin-top: 3px; }
            .fi-approval-notes  { font-size: .72rem; color: #d1d5db; font-style: italic; margin-top: 4px; }
            .fi-approval-empty-msg { font-size: .8rem; color: #9ca3af; padding: 8px 0; }
        </style>
        <div class="fi-approval-trail">{$rows}</div>
        HTML;
    }

    /**
     * Reset all approval records (e.g. when document is re-submitted after rejection).
     */
    public static function reset(Model $model, string $documentType): void
    {
        ProcurementApprovalRecord::where('approvable_type', $model::class)
            ->where('approvable_id', $model->getKey())
            ->update(['status' => 'Pending', 'decided_by' => null, 'decided_at' => null, 'notes' => null]);
    }
}
