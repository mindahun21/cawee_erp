<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('campaigns', function (Blueprint $table) {
            $table->id();
            $table->string('title', 150);
            $table->text('description')->nullable();
            $table->decimal('goal_amount', 12, 2);
            $table->foreignId('currency_id')->constrained();
            $table->date('start_date');
            $table->date('end_date');
            $table->decimal('budget', 12, 2)->default(0.00);
            $table->enum('status', ['planned', 'active', 'completed'])->default('planned');
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('donations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('donor_id')->constrained()->cascadeOnDelete();
            $table->foreignId('campaign_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('donation_type_id')->constrained();
            $table->decimal('amount', 12, 2);
            $table->foreignId('currency_id')->constrained();
            $table->date('donation_date');
            $table->boolean('is_recurring')->default(false);
            $table->decimal('pledge_amount', 12, 2)->nullable();
            $table->text('in_kind_description')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('donations');
        Schema::dropIfExists('campaigns');
    }
};
