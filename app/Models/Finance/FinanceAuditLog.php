<?php

namespace App\Models\Finance;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

/**
 * Finance Audit Log — immutable, append-only ledger of every significant
 * action performed on any Finance model.
 *
 * Design notes:
 *  • No updated_at column — audit rows are written once and never changed.
 *  • The static record() factory handles all writes; never call create() directly.
 *  • Boot logic in HasFinanceAuditLog fires automatically via Eloquent events.
 *  • Sensitive exceptions are swallowed silently so auditing never breaks a TX.
 *
 * @property int         $id
 * @property string      $auditable_type
 * @property int         $auditable_id
 * @property string      $action
 * @property array|null  $old_values
 * @property array|null  $new_values
 * @property int|null    $user_id
 * @property string|null $ip_address
 * @property string|null $user_agent
 * @property \Carbon\Carbon $created_at
 */
class FinanceAuditLog extends Model
{
    protected $table = 'finance_audit_logs';

    /**
     * This table has no updated_at column — logs are immutable.
     */
    const UPDATED_AT = null;

    protected $fillable = [
        'auditable_type',
        'auditable_id',
        'action',
        'old_values',
        'new_values',
        'user_id',
        'ip_address',
        'user_agent',
    ];

    protected function casts(): array
    {
        return [
            'auditable_id' => 'integer',
            'old_values'   => 'array',
            'new_values'   => 'array',
        ];
    }

    // ── Action constants ──────────────────────────────────────────────

    const ACTION_CREATE  = 'create';
    const ACTION_UPDATE  = 'update';
    const ACTION_DELETE  = 'delete';
    const ACTION_APPROVE = 'approve';
    const ACTION_REJECT  = 'reject';
    const ACTION_POST    = 'post';
    const ACTION_REVERSE = 'reverse';
    const ACTION_LOCK    = 'lock';
    const ACTION_UNLOCK  = 'unlock';

    // ── Helpers ───────────────────────────────────────────────────────

    /**
     * Human-readable labels for all supported actions.
     */
    public static function actions(): array
    {
        return [
            self::ACTION_CREATE  => 'Created',
            self::ACTION_UPDATE  => 'Updated',
            self::ACTION_DELETE  => 'Deleted',
            self::ACTION_APPROVE => 'Approved',
            self::ACTION_REJECT  => 'Rejected',
            self::ACTION_POST    => 'Posted to GL',
            self::ACTION_REVERSE => 'Reversed',
            self::ACTION_LOCK    => 'Locked',
            self::ACTION_UNLOCK  => 'Unlocked',
        ];
    }

    /**
     * Badge color mapping for Filament table/infolist display.
     */
    public static function actionColor(string $action): string
    {
        return match ($action) {
            self::ACTION_CREATE  => 'success',
            self::ACTION_UPDATE  => 'info',
            self::ACTION_DELETE  => 'danger',
            self::ACTION_APPROVE => 'success',
            self::ACTION_REJECT  => 'danger',
            self::ACTION_POST    => 'primary',
            self::ACTION_REVERSE => 'warning',
            self::ACTION_LOCK    => 'danger',
            self::ACTION_UNLOCK  => 'warning',
            default              => 'gray',
        };
    }

    // ── Static factory ────────────────────────────────────────────────

    /**
     * Create an immutable audit log entry for a Finance model action.
     *
     * This method is intentionally fault-tolerant: any exception thrown during
     * log creation is caught and ignored so that auditing NEVER prevents the
     * primary business transaction from completing.
     *
     * @param  string                                      $action     One of the ACTION_* constants
     * @param  \Illuminate\Database\Eloquent\Model         $model      The subject model instance
     * @param  array<string, mixed>|null                   $oldValues  State before the change
     * @param  array<string, mixed>|null                   $newValues  State after the change
     */
    public static function record(
        string $action,
        Model  $model,
        ?array $oldValues = null,
        ?array $newValues = null,
    ): ?self {
        try {
            // Strip sensitive / binary fields that should not appear in audit logs
            $scrub = ['password', 'remember_token', 'api_token'];

            if ($oldValues !== null) {
                $oldValues = array_diff_key($oldValues, array_flip($scrub));
            }

            if ($newValues !== null) {
                $newValues = array_diff_key($newValues, array_flip($scrub));
            }

            return static::create([
                'auditable_type' => get_class($model),
                'auditable_id'   => $model->getKey(),
                'action'         => $action,
                'old_values'     => $oldValues ?: null,
                'new_values'     => $newValues ?: null,
                'user_id'        => auth()->id(),
                'ip_address'     => request()?->ip(),
                'user_agent'     => request()?->userAgent(),
            ]);
        } catch (\Throwable) {
            // Audit failure must never break a business transaction
            return null;
        }
    }

    // ── Scopes ────────────────────────────────────────────────────────

    /**
     * Scope: audit logs for a specific model instance.
     */
    public function scopeForModel($query, Model $model): void
    {
        $query
            ->where('auditable_type', get_class($model))
            ->where('auditable_id', $model->getKey());
    }

    /**
     * Scope: audit logs for a specific action type.
     */
    public function scopeOfAction($query, string $action): void
    {
        $query->where('action', $action);
    }

    // ── Relationships ─────────────────────────────────────────────────

    /**
     * The Finance model that was audited (polymorphic).
     */
    public function auditable(): MorphTo
    {
        return $this->morphTo();
    }

    /**
     * The user who performed the audited action.
     */
    public function actor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }
}
