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
        Schema::create('donation_types', function (Blueprint $table) {
            $table->id();
            $table->string('name', 100);
            $table->string('code', 50)->unique();
            $table->text('description')->nullable();
            $table->boolean('has_pledge_management')->default(false);
            $table->boolean('is_recurring')->default(false);
            $table->boolean('is_in_kind')->default(false);
            $table->boolean('supports_gift_aid')->default(false);
            $table->boolean('requires_pledge_amount')->default(false);
            $table->boolean('requires_in_kind_description')->default(false);
            $table->string('receipt_template')->nullable();
            $table->boolean('tax_deductible')->default(true);
            $table->integer('sort_order')->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('donation_types');
    }
};
