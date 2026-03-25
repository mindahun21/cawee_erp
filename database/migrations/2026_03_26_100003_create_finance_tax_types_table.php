<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('finance_tax_types', function (Blueprint $table) {
            $table->id();
            $table->string('code', 30)->unique();
            $table->string('name', 100);
            $table->enum('category', [
                'withholding_tax',
                'vat',
                'income_tax',
                'pension',
                'other',
            ]);
            $table->decimal('default_rate', 6, 4)->default(0);      // e.g. 0.1500 = 15%
            $table->boolean('is_automatic')->default(false);         // auto-calculated on vouchers
            $table->boolean('applies_to_individuals')->default(true);
            $table->boolean('applies_to_organizations')->default(true);
            $table->text('description')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('finance_tax_types');
    }
};
