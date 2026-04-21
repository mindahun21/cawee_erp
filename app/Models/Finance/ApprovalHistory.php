<?php

namespace App\Models\Finance;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class ApprovalHistory extends Model
{
    protected $table = 'finance_approval_histories';

    protected $fillable = [
        'approvable_type', 'approvable_id',
        'stage_number', 'stage_name', 'action',
        'actor_id', 'comments', 'actioned_at',
        'previous_status', 'new_status',
    ];

    protected function casts(): array
    {
        return ['actioned_at' => 'datetime'];
    }

    public static function actions(): array
    {
        return [
            'approved'   => 'Approved',
            'rejected'   => 'Rejected',
            'returned'   => 'Returned for Revision',
            'noted'      => 'Noted',
            'forwarded'  => 'Forwarded',
        ];
    }

    public function approvable(): MorphTo
    {
        return $this->morphTo('approvable', 'approvable_type', 'approvable_id');
    }

    public function actor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'actor_id');
    }

    /** Record a new approval history entry from a context. */
    public static function log(
        Model $document,
        string $action,
        string $stageName,
        int $stageNumber,
        string $previousStatus,
        string $newStatus,
        ?string $comments = null
    ): self {
        return self::create([
            'approvable_type'  => get_class($document),
            'approvable_id'    => $document->getKey(),
            'stage_number'     => $stageNumber,
            'stage_name'       => $stageName,
            'action'           => $action,
            'actor_id'         => auth()->id(),
            'comments'         => $comments,
            'actioned_at'      => now(),
            'previous_status'  => $previousStatus,
            'new_status'       => $newStatus,
        ]);
    }
}
