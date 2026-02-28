<?php

namespace App\Models\ME;

use Illuminate\Database\Eloquent\Model;

class MeAuditLog extends Model
{
    protected $table = 'me_audit_logs';

    public $timestamps = false;

    protected $fillable = [
        'table_name',
        'record_id',
        'action',
        'changes',
        'user_id',
        'created_at',
    ];

    protected $casts = [
        'record_id' => 'integer',
        'changes' => 'array',
        'user_id' => 'integer',
        'created_at' => 'datetime',
    ];
}
