<?php

namespace App\Models\Procurement;

use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class Supplier extends Authenticatable
{
    use SoftDeletes, Notifiable;

    protected $table = 'procurement_suppliers';

    protected $fillable = [
        // Core identity
        'name', 'code', 'vendor_code', 'category', 'status', 'notes', 'attachments',
        // Auth
        'email', 'password', 'portal_access', 'email_verified_at',
        // Contact
        'phone', 'website', 'contact_person', 'contact_person_title',
        'contact_phone', 'contact_email',
        // Address
        'address', 'city', 'state', 'zip_code', 'country',
        // Billing / shipping
        'billing_address', 'shipping_address', 'same_as_billing',
        // Tax / legal
        'tin_number', 'vat_number',
        // Bank
        'bank_name', 'bank_account', 'bank_branch', 'bank_swift', 'bank_iban',
        // Financial
        'currency', 'payment_terms', 'payment_term_id', 'return_policy', 'default_language',
        // Meta
        'logo_path', 'registration_date', 'contract_expiry_date',
    ];

    protected $hidden = ['password', 'remember_token'];

    protected function casts(): array
    {
        return [
            'portal_access'         => 'boolean',
            'same_as_billing'       => 'boolean',
            'email_verified_at'     => 'datetime',
            'registration_date'     => 'date',
            'contract_expiry_date'  => 'date',
            'attachments'           => 'array',
            'password'              => 'hashed',
        ];
    }

    // ── Scopes ─────────────────────────────────────────────────────
    public function scopeActive($query) { return $query->where('status', 'Active'); }

    // ── Relationships ───────────────────────────────────────────────
    public function bids(): HasMany         { return $this->hasMany(Bid::class); }
    public function purchaseOrders(): HasMany { return $this->hasMany(PurchaseOrder::class); }
    public function invoices(): HasMany     { return $this->hasMany(Invoice::class); }
    public function payments(): HasMany     { return $this->hasMany(Payment::class); }

    public function paymentTerm()
    {
        return $this->belongsTo(PaymentTerm::class, 'payment_term_id');
    }

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

    public function getDisplayNameAttribute(): string
    {
        return $this->name . ($this->code ? " ({$this->code})" : '');
    }
}
