<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Filament\Models\Contracts\FilamentUser;
use Filament\Panel;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Spatie\Permission\Traits\HasRoles;

class User extends Authenticatable implements FilamentUser
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasFactory, Notifiable, HasRoles;

    public function canAccessPanel(Panel $panel): bool
    {
        return true;
    }

    public function employee(): \Illuminate\Database\Eloquent\Relations\HasOne
    {
        return $this->hasOne(Employee::class);
    }

    public function sharedFolders(): HasMany
    {
        return $this->hasMany(SharedFolder::class, 'owner_id');
    }

    public function sharedFiles(): HasMany
    {
        return $this->hasMany(SharedFile::class, 'uploaded_by');
    }

    public function fileSharesCreated(): HasMany
    {
        return $this->hasMany(FileShare::class, 'created_by');
    }

    public function fileAccessLogs(): HasMany
    {
        return $this->hasMany(FileAccessLog::class);
    }

    // ── HR Role Helpers ────────────────────────────────────────────
    // These methods provide readable, semantic checks across the codebase.
    // Use these instead of hardcoding role name strings everywhere.

    public function isSuperAdmin(): bool
    {
        return $this->hasRole('super_admin');
    }

    public function isHrDirector(): bool
    {
        return $this->hasAnyRole(['hr_director', 'super_admin']);
    }

    public function isHrOfficer(): bool
    {
        return $this->hasAnyRole(['hr_officer', 'hr_director', 'super_admin']);
    }

    public function isHrSupervisor(): bool
    {
        return $this->hasAnyRole(['hr_supervisor', 'hr_officer', 'hr_director', 'super_admin']);
    }

    // ── Procurement Role Helpers ───────────────────────────────────
    // Mirror the HR pattern; super_admin always has access.

    /** Requester: can create purchase requisitions */
    public function isProcurementRequester(): bool
    {
        return $this->hasAnyRole(['procurement_requester', 'procurement_officer', 'procurement_director', 'super_admin']);
    }

    /** Supervisor: approves requisitions at Stage 1 */
    public function isProcurementSupervisor(): bool
    {
        return $this->hasAnyRole(['procurement_supervisor', 'procurement_officer', 'procurement_director', 'super_admin']);
    }

    /** Department Head: approves requisitions at Stage 2 */
    public function isProcurementDeptHead(): bool
    {
        return $this->hasAnyRole(['procurement_dept_head', 'procurement_director', 'super_admin']);
    }

    /** Procurement Officer: manages tenders, suppliers, PO approvals */
    public function isProcurementOfficer(): bool
    {
        return $this->hasAnyRole(['procurement_officer', 'procurement_director', 'super_admin']);
    }

    /** Evaluation Committee: evaluates bids */
    public function isProcurementEvaluator(): bool
    {
        return $this->hasAnyRole(['procurement_evaluator', 'procurement_officer', 'procurement_director', 'super_admin']);
    }

    /** Store / Warehouse: receives and inspects goods */
    public function isProcurementStore(): bool
    {
        return $this->hasAnyRole(['procurement_store', 'procurement_officer', 'procurement_director', 'super_admin']);
    }

    /** Finance Officer: approves invoices, payments, budget checks */
    public function isProcurementFinance(): bool
    {
        return $this->hasAnyRole(['procurement_finance', 'finance_officer', 'procurement_director', 'super_admin']);
    }

    /** Director: final approval for POs, invoices, payments */
    public function isProcurementDirector(): bool
    {
        return $this->hasAnyRole(['procurement_director', 'super_admin']);
    }

    /** Auditor: read-only access to all procurement records */
    public function isProcurementAuditor(): bool
    {
        return $this->hasAnyRole(['procurement_auditor', 'procurement_director', 'super_admin']);
    }

    // ── Finance Role Helpers ───────────────────────────────────────
    // Mirror the HR / Procurement pattern; super_admin always has full access.

    /** Finance Officer: create/edit vouchers, journals, petty cash */
    public function isFinanceOfficer(): bool
    {
        return $this->hasAnyRole(['finance_officer', 'finance_manager', 'cfo', 'super_admin']);
    }

    /** Finance Manager: approve vouchers, manage budgets */
    public function isFinanceManager(): bool
    {
        return $this->hasAnyRole(['finance_manager', 'cfo', 'super_admin']);
    }

    /** CFO: final approval, post-to-GL, all financial reports */
    public function isCFO(): bool
    {
        return $this->hasAnyRole(['cfo', 'super_admin']);
    }

    /** Finance Auditor: read-only on all finance records and audit trail */
    public function isFinanceAuditor(): bool
    {
        return $this->hasAnyRole(['finance_auditor', 'internal_auditor', 'external_auditor', 'cfo', 'super_admin']);
    }

    /** Cashier: petty cash payments and cash receipt vouchers only */
    public function isCashier(): bool
    {
        return $this->hasAnyRole(['cashier', 'finance_officer', 'finance_manager', 'cfo', 'super_admin']);
    }

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
        ];
    }
}
