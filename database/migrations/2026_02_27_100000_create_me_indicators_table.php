<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('me_indicators', function (Blueprint $table) {
            $table->id();
            $table->string('code')->unique();
            $table->string('name');
            $table->enum('framework_type', ['output', 'outcome', 'impact']);
            $table->string('unit')->nullable();
            $table->enum('frequency', ['weekly', 'monthly', 'quarterly', 'semiannual', 'annual']);
            $table->text('description')->nullable();
            $table->boolean('is_active')->default(true);
            $table->boolean('disaggregation_required')->default(false);
            $table->decimal('threshold_warning', 5, 2)->default(70.00);
            $table->decimal('threshold_critical', 5, 2)->default(50.00);
            $table->timestamps();

            $table->index(['framework_type', 'is_active']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('me_indicators');
    }
};
