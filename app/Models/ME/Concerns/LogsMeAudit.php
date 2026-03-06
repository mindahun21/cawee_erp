<?php

namespace App\Models\ME\Concerns;

use App\Models\ME\MeAuditLog;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;

trait LogsMeAudit
{
    public static function bootLogsMeAudit(): void
    {
        static::created(function (Model $model): void {
            static::writeMeAuditLog($model, 'create', [
                'new' => $model->getAttributes(),
            ]);
        });

        static::updated(function (Model $model): void {
            $changes = collect($model->getChanges())
                ->except(['updated_at'])
                ->mapWithKeys(function ($newValue, string $attribute) use ($model): array {
                    return [
                        $attribute => [
                            'old' => $model->getPrevious()[$attribute] ?? null,
                            'new' => $newValue,
                        ],
                    ];
                })
                ->all();

            if ($changes === []) {
                return;
            }

            static::writeMeAuditLog($model, 'update', $changes);
        });

        static::deleted(function (Model $model): void {
            static::writeMeAuditLog($model, 'delete', [
                'old' => $model->getOriginal(),
            ]);
        });
    }

    protected static function writeMeAuditLog(Model $model, string $action, ?array $changes = null): void
    {
        MeAuditLog::query()->create([
            'table_name' => $model->getTable(),
            'record_id' => $model->getKey(),
            'action' => $action,
            'changes' => $changes,
            'user_id' => Auth::id(),
            'created_at' => now(),
        ]);
    }
}
