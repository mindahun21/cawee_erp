<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class PurchaseRequest extends Model
{
    protected $fillable = [
        'code',
        'name',
        'project_id',
        'requester_id',
        'payment_center',
        'purpose',
        'budget_code',
        'status',
        'sale_estimate',
        'type',
        'currency_id',
        'department_id',
        'sale_invoice_id',
        'share_to_vendors',
        'description',
        'subtotal',
        'tax_amount',
        'total_amount',
        'request_date',
    ];

    protected $casts = [
        'request_date' => 'date',
        'total_amount' => 'decimal:2',
        'subtotal' => 'decimal:2',
        'tax_amount' => 'decimal:2',
        'sale_estimate' => 'decimal:2',
        'share_to_vendors' => 'boolean',
    ];

    protected static function booted()
    {
        static::creating(function ($model) {
            if (empty($model->code)) {
                $year = date('Y');
                $deptCode = $model->department?->name ?? 'GEN';
                $deptShort = strtoupper(substr($deptCode, 0, 3));
                $count = static::whereYear('created_at', $year)->count() + 1;
                $model->code = sprintf('#PR-%05d-%d-%s', $count, $year, $deptShort);
            }
        });
    }

    public function project()
    {
        return $this->belongsTo(Project::class);
    }

    public function currency()
    {
        return $this->belongsTo(Currency::class);
    }

    public function department()
    {
        return $this->belongsTo(Department::class);
    }

    public function requester()
    {
        return $this->belongsTo(User::class, 'requester_id');
    }

    public function items()
    {
        return $this->hasMany(PurchaseRequestItem::class);
    }

    public function purchaseOrder()
    {
        return $this->hasOne(PurchaseOrder::class);
    }

    public function recalculateTotal()
    {
        $this->subtotal = $this->items()->sum('subtotal');
        $this->tax_amount = $this->items()->sum('tax_value');
        $this->total_amount = $this->items()->sum('total');
        $this->save();
    }
}
