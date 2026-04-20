<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('finance_audit_logs', function (Blueprint $table) {
            $table->id();

            // ── Polymorphic subject ───────────────────────────────────────────
            $table->string('auditable_type', 150);
            $table->unsignedBigInteger('auditable_id');

            // ── Action recorded ───────────────────────────────────────────────
            $table->enum('action', [
                'create',
                'update',
                'delete',
                'approve',
                'reject',
                'post',
                'reverse',
                'lock',
                'unlock',
            ]);

            // ── Change snapshot ───────────────────────────────────────────────
            $table->json('old_values')->nullable();
            $table->json('new_values')->nullable();

            // ── Actor & request metadata ──────────────────────────────────────
            $table->foreignId('user_id')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();

            $table->string('ip_address', 45)->nullable();
            $table->string('user_agent', 500)->nullable();

            // ── Immutable timestamp (no updated_at) ───────────────────────────
            $table->timestamp('created_at')->useCurrent();

            // ── Indexes for fast look-up by subject or actor ──────────────────
            $table->index(['auditable_type', 'auditable_id'], 'finance_audit_logs_auditable_index');
            $table->index('user_id', 'finance_audit_logs_user_index');
            $table->index('action',  'finance_audit_logs_action_index');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('finance_audit_logs');
    }
};
