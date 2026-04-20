<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('finance_perdiem_settlements', function (Blueprint $table) {
            $table->id();
            $table->foreignId('perdiem_request_id')->unique()->constrained('finance_perdiem_requests')->cascadeOnDelete();
            $table->date('settlement_date');
            $table->unsignedSmallInteger('actual_days');
            $table->decimal('actual_amount', 18, 2)->default(0);
            $table->decimal('advance_paid', 18, 2)->default(0);
            $table->decimal('balance_to_recover', 18, 2)->default(0);  // actual - advance (negative = employee owes back)
            $table->enum('status', ['draft', 'approved', 'closed'])->default('draft');
            $table->json('document_attachments')->nullable();
            $table->foreignId('journal_entry_id')->nullable()->constrained('finance_journal_entries')->nullOnDelete();
            $table->foreignId('approved_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('approved_at')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('finance_perdiem_settlements');
    }
};
