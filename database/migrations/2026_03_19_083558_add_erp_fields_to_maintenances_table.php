<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('maintenances', function (Blueprint $table) {
            $table->string('status')->default('scheduled')->after('title'); // scheduled, in_progress, completed, cancelled
            $table->string('priority')->default('normal')->after('status'); // urgent, high, normal, low
            $table->text('description')->nullable()->after('priority');
            $table->foreignId('performed_by_id')->nullable()->constrained('employees')->onDelete('set null')->after('supplier_id');
            $table->date('next_scheduled_date')->nullable()->after('completion_date');
            $table->decimal('downtime_hours', 8, 2)->nullable()->after('cost');
        });
    }

    public function down(): void
    {
        Schema::table('maintenances', function (Blueprint $table) {
            $table->dropForeign(['performed_by_id']);
            $table->dropColumn(['status', 'priority', 'description', 'performed_by_id', 'next_scheduled_date', 'downtime_hours']);
        });
    }
};
