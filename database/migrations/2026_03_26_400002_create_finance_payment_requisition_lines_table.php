<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('finance_payment_requisition_lines', function (Blueprint $table) {
            $table->id();

            $table->foreignId('payment_requisition_id')
                ->constrained('finance_payment_requisitions')
                ->cascadeOnDelete();

            $table->foreignId('chart_of_account_id')
                ->constrained('finance_chart_of_accounts')
                ->restrictOnDelete();

            $table->string('description');
            $table->decimal('quantity', 10, 3)->default(1);
            $table->decimal('unit_price', 18, 2);
            $table->decimal('line_total', 18, 2)->comment('quantity × unit_price');

            // Optional line-level dimension override
            $table->string('activity_code', 50)->nullable();
            $table->unsignedBigInteger('project_id')->nullable()->index();
            $table->unsignedBigInteger('donor_id')->nullable()->index();

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('finance_payment_requisition_lines');
    }
};
