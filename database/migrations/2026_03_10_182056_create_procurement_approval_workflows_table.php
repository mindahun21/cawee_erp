<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('procurement_approval_workflows', function (Blueprint $table) {
            $table->id();
            // e.g. 'invoice', 'purchase_order', 'requisition', 'payment', 'goods_receipt', 'tender', 'bid', 'contract'
            $table->string('document_type')->unique();
            $table->string('name');                          // e.g. "Supplier Invoice Approval"
            $table->text('description')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('procurement_approval_workflows');
    }
};
