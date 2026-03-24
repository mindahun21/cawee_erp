<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('brt_progress_updates', function (Blueprint $table): void {
            if (! Schema::hasColumn('brt_progress_updates', 'alert_status')) {
                $table->enum('alert_status', ['open', 'in_review', 'escalated', 'resolved'])
                    ->nullable()
                    ->after('high_risk_flag');
            }

            if (! Schema::hasColumn('brt_progress_updates', 'assigned_to')) {
                $table->foreignId('assigned_to')
                    ->nullable()
                    ->constrained('users')
                    ->nullOnDelete()
                    ->after('alert_status');
            }

            if (! Schema::hasColumn('brt_progress_updates', 'resolved_at')) {
                $table->timestamp('resolved_at')->nullable()->after('assigned_to');
            }

            if (! Schema::hasColumn('brt_progress_updates', 'resolution_note')) {
                $table->text('resolution_note')->nullable()->after('resolved_at');
            }
        });
    }

    public function down(): void
    {
        Schema::table('brt_progress_updates', function (Blueprint $table): void {
            if (Schema::hasColumn('brt_progress_updates', 'assigned_to')) {
                $table->dropConstrainedForeignId('assigned_to');
            }

            $columnsToDrop = [];
            foreach (['alert_status', 'resolved_at', 'resolution_note'] as $column) {
                if (Schema::hasColumn('brt_progress_updates', $column)) {
                    $columnsToDrop[] = $column;
                }
            }

            if ($columnsToDrop !== []) {
                $table->dropColumn($columnsToDrop);
            }
        });
    }
};
