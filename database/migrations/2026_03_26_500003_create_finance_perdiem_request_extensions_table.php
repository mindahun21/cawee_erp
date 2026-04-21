<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('finance_perdiem_request_extensions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('perdiem_request_id')->constrained('finance_perdiem_requests')->cascadeOnDelete();
            $table->date('extension_date');
            $table->unsignedTinyInteger('additional_days');
            $table->date('new_end_date');
            $table->decimal('additional_amount', 18, 2)->default(0);
            $table->text('reason');
            $table->enum('status', ['draft', 'pending', 'approved', 'rejected'])->default('draft');
            $table->foreignId('approved_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('approved_at')->nullable();
            $table->timestamps();

            $table->index('perdiem_request_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('finance_perdiem_request_extensions');
    }
};
