<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('finance_budget_codes', function (Blueprint $table) {
            $table->id();
            $table->string('code', 80)->unique();
            $table->string('description', 255)->nullable();
            $table->string('cost_category', 120)->nullable();
            $table->boolean('is_active')->default(true);
            $table->text('notes')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['is_active', 'code']);
            $table->index('cost_category');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('finance_budget_codes');
    }
};
