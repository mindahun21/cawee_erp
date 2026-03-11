<?php

namespace App\Models\Procurement;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ProcurementApprovalWorkflow extends Model
{
    protected $fillable = ['document_type', 'name', 'description', 'is_active'];

    protected function casts(): array
    {
        return ['is_active' => 'boolean'];
    }

    public function stages(): HasMany
    {
        return $this->hasMany(ProcurementApprovalStage::class, 'workflow_id')
            ->orderBy('stage_order');
    }

    // ── Static helpers ────────────────────────────────────────────────

    /**
     * All supported document types with their human-readable labels.
     */
    public static function documentTypes(): array
    {
        return [
            'invoice'       => 'Supplier Invoice',
            'purchase_order'=> 'Purchase Order',
            'requisition'   => 'Purchase Requisition',
            'payment'       => 'Payment',
            'goods_receipt' => 'Goods Receipt',
            'tender'        => 'Tender / RFQ',
            'bid'           => 'Bid Submission',
            'contract'      => 'Contract',
        ];
    }

    /**
     * Returns available procurement roles for stage configuration.
     */
    public static function availableRoles(): array
    {
        return [
            'procurement_requester'  => 'Procurement Requester',
            'procurement_supervisor' => 'Procurement Supervisor',
            'procurement_dept_head'  => 'Department Head',
            'procurement_officer'    => 'Procurement Officer',
            'procurement_evaluator'  => 'Bid Evaluator',
            'procurement_store'      => 'Store / Warehouse',
            'procurement_finance'    => 'Finance Officer',
            'procurement_director'   => 'Procurement Director',
            'procurement_auditor'    => 'Auditor',
            'super_admin'            => 'Super Admin',
        ];
    }

    /**
     * Returns the active workflow for a given document type, or null if none configured.
     */
    public static function activeFor(string $documentType): ?self
    {
        return static::where('document_type', $documentType)
            ->where('is_active', true)
            ->with('stages')
            ->first();
    }
}
