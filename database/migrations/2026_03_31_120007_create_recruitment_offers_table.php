<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('recruitment_offers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('application_id')
                  ->constrained('recruitment_applications')
                  ->cascadeOnDelete();
            $table->decimal('offered_salary', 14, 2)->nullable();
            $table->string('offer_letter_path', 500)->nullable();
            $table->date('offer_date');
            $table->date('offer_expiry_date')->nullable();
            $table->string('status')->default('draft');
            // draft | submitted | approved | accepted | declined | expired | withdrawn
            $table->timestamp('responded_at')->nullable();
            $table->text('decline_reason')->nullable();
            $table->foreignId('issued_by')->constrained('users');
            $table->foreignId('approval_workflow_id')
                  ->nullable()
                  ->constrained('recruitment_approval_workflows')
                  ->nullOnDelete();
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->unique('application_id'); // One offer per application
            $table->index('status');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('recruitment_offers');
    }
};
