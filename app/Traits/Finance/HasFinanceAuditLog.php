<?php

namespace App\Traits\Finance;

use App\Models\Finance\FinanceAuditLog;
use Illuminate\Database\Eloquent\Model;

/**
 * HasFinanceAuditLog
 *
 * Attach this trait to any Finance Eloquent model to get automatic,
 * immutable audit logging on every create / update / delete event.
 *
 * The log is written to `finance_audit_logs` via the FinanceAuditLog model.
 * Failures are silently swallowed so that audit issues never abort a
 * financial transaction.
 *
 * Usage:
 *   class JournalEntry extends Model
 *   {
 *       use HasFinanceAuditLog;
 *       ...
 *   }
 */
trait HasFinanceAuditLog
{
    // ── Boot ──────────────────────────────────────────────────────────────

    protected static function bootHasFinanceAuditLog(): void
    {
        // Skip auto-logging during database seeding / migrations so that the
        // mass-insert of reference data does not produce thousands of log rows.
        // The check is intentionally loose: if running in console AND not in a
        // unit-test context, we bail out early.
        if (app()->runningInConsole() && ! app()->runningUnitTests()) {
            return;
        }

        // ── CREATE ────────────────────────────────────────────────────────
        static::created(static function (Model $model): void {
            static::writeAuditLog('create', $model, null, $model->toArray());
        });

        // ── UPDATE ────────────────────────────────────────────────────────
        static::updated(static function (Model $model): void {
            $changes = $model->getChanges();

            // Strip the automatic timestamp fields — they add noise without value
            unset($changes['updated_at'], $changes['created_at']);

            if (empty($changes)) {
                return;
            }

            // Build a "before" snapshot using only the fields that changed
            $original = array_intersect_key($model->getOriginal(), $changes);

            static::writeAuditLog('update', $model, $original, $changes);
        });

        // ── DELETE (soft or hard) ─────────────────────────────────────────
        static::deleted(static function (Model $model): void {
            static::writeAuditLog('delete', $model, $model->toArray(), null);
        });
    }

    // ── Internal helper ───────────────────────────────────────────────────

    /**
     * Write a single audit-log row, swallowing any exception so that a
     * logging failure can never break a live financial transaction.
     *
     * @param  string      $action     One of: create | update | delete
     * @param  Model       $model      The Eloquent model instance being audited
     * @param  array|null  $oldValues  State before the change (null for creates)
     * @param  array|null  $newValues  State after the change  (null for deletes)
     */
    private static function writeAuditLog(
        string $action,
        Model  $model,
        ?array $oldValues,
        ?array $newValues,
    ): void {
        try {
            FinanceAuditLog::record($action, $model, $oldValues, $newValues);
        } catch (\Throwable) {
            // Intentionally silent — audit must never break the business transaction.
            // In production, consider logging to a fallback channel here.
        }
    }

    // ── Public helpers (callable from Resource actions) ───────────────────

    /**
     * Record a custom Finance action (approve / reject / post / reverse / lock)
     * against this model instance.
     *
     * @param  string      $action
     * @param  array|null  $oldValues
     * @param  array|null  $newValues
     */
    public function recordAuditAction(
        string $action,
        ?array $oldValues = null,
        ?array $newValues = null,
    ): void {
        static::writeAuditLog($action, $this, $oldValues, $newValues);
    }

    // ── Relationship ──────────────────────────────────────────────────────

    /**
     * All audit-log entries for this model instance.
     * Ordered newest-first so that the most recent action is always first.
     */
    public function auditLogs(): \Illuminate\Database\Eloquent\Relations\MorphMany
    {
        return $this->morphMany(FinanceAuditLog::class, 'auditable')
            ->orderByDesc('created_at');
    }
}
