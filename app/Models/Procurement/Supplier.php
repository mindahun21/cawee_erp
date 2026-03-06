<?php

namespace App\Models\Procurement;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Supplier extends Model
{
    use SoftDeletes;

    protected $table = 'procurement_suppliers';

    protected $fillable = [
        'name', 'code', 'email', 'phone', 'address', 'tin_number',
        'bank_name', 'bank_account', 'contact_person', 'category', 'status', 'notes',
    ];

    // ── Scopes ─────────────────────────────────────────────────────
    public function scopeActive($query) { return $query->where('status', 'Active'); }

    // ── Relationships ───────────────────────────────────────────────
    public function bids(): HasMany { return $this->hasMany(Bid::class); }
    public function purchaseOrders(): HasMany { return $this->hasMany(PurchaseOrder::class); }
    public function invoices(): HasMany { return $this->hasMany(Invoice::class); }
    public function payments(): HasMany { return $this->hasMany(Payment::class); }

    // ── Computed ────────────────────────────────────────────────────
    public function getStatusColorAttribute(): string
    {
        return match ($this->status) {
            'Active'      => 'success',
            'Inactive'    => 'gray',
            'Blacklisted' => 'danger',
            default       => 'gray',
        };
    }
}
