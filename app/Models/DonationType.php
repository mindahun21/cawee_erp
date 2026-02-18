<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class DonationType extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'code',
        'description',
        'has_pledge_management',
        'is_recurring',
        'is_in_kind',
        'supports_gift_aid',
        'requires_pledge_amount',
        'requires_in_kind_description',
        'receipt_template',
        'tax_deductible',
        'sort_order',
        'is_active',
    ];

    protected $casts = [
        'has_pledge_management' => 'boolean',
        'is_recurring' => 'boolean',
        'is_in_kind' => 'boolean',
        'supports_gift_aid' => 'boolean',
        'requires_pledge_amount' => 'boolean',
        'requires_in_kind_description' => 'boolean',
        'tax_deductible' => 'boolean',
        'is_active' => 'boolean',
    ];

    public function donations(): HasMany
    {
        return $this->hasMany(Donation::class);
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true)->orderBy('sort_order', 'asc');
    }

    public function scopeRecurring($query)
    {
        return $query->where('is_recurring', true);
    }

    public function scopePledge($query)
    {
        return $query->where('has_pledge_management', true);
    }

    public function scopeInKind($query)
    {
        return $query->where('is_in_kind', true);
    }

    public function scopeGiftAid($query)
    {
        return $query->where('supports_gift_aid', true);
    }

    public function getConfig(): array
    {
        return [
            'requires_pledge_amount' => (bool)$this->requires_pledge_amount,
            'requires_in_kind_description' => (bool)$this->requires_in_kind_description,
            'is_recurring' => (bool)$this->is_recurring,
            'has_pledge_management' => (bool)$this->has_pledge_management,
            'is_in_kind' => (bool)$this->is_in_kind,
            'supports_gift_aid' => (bool)$this->supports_gift_aid,
            'tax_deductible' => (bool)$this->tax_deductible,
            'receipt_template' => $this->receipt_template,
        ];
    }
}
