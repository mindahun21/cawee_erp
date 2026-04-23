<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('finance_perdiem_tax_rules', function (Blueprint $table) {
            $table->id();
            $table->foreignId('perdiem_type_id')->constrained('finance_perdiem_types')->cascadeOnDelete();
            $table->decimal('threshold_amount', 18, 2)->default(0)->comment('Daily rate above this is taxable');
            $table->decimal('tax_rate', 6, 4)->default(0)->comment('e.g. 0.1000 = 10%');
            $table->enum('tax_type', ['income_tax', 'withholding', 'none'])->default('none');
            $table->date('effective_date');
            $table->date('expiry_date')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->index('perdiem_type_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('finance_perdiem_tax_rules');
    }
};
