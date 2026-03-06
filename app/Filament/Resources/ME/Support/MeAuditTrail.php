<?php

namespace App\Filament\Resources\ME\Support;

use App\Models\ME\MeAuditLog;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Section;
use Illuminate\Database\Eloquent\Model;

class MeAuditTrail
{
    public static function section(string $tableName): Section
    {
        return Section::make('Audit Trail')
            ->schema([
                TextEntry::make('me_audit_trail')
                    ->label('Recent Activity')
                    ->state(fn (?Model $record): string => static::render($record, $tableName))
                    ->html(),
            ])
            ->collapsed();
    }

    private static function render(?Model $record, string $tableName): string
    {
        if (! $record) {
            return 'No audit entries.';
        }

        $logs = MeAuditLog::query()
            ->where('table_name', $tableName)
            ->where('record_id', $record->getKey())
            ->latest('created_at')
            ->limit(20)
            ->get();

        if ($logs->isEmpty()) {
            return 'No audit entries.';
        }

        $rows = $logs
            ->map(function (MeAuditLog $log): string {
                $timestamp = optional($log->created_at)->format('Y-m-d H:i:s') ?? '-';
                $userId = $log->user_id ?? 'system';

                return sprintf(
                    '<tr><td style="padding:6px 8px;">%s</td><td style="padding:6px 8px;">%s</td><td style="padding:6px 8px;">%s</td></tr>',
                    e(strtoupper($log->action)),
                    e((string) $userId),
                    e($timestamp)
                );
            })
            ->implode('');

        return sprintf(
            '<table style="width:100%%;border-collapse:collapse;"><thead><tr><th style="text-align:left;padding:6px 8px;">Action</th><th style="text-align:left;padding:6px 8px;">User</th><th style="text-align:left;padding:6px 8px;">Time</th></tr></thead><tbody>%s</tbody></table>',
            $rows
        );
    }
}
