<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('procurement_tender_invites', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tender_id')->constrained('procurement_tenders')->cascadeOnDelete();
            $table->foreignId('supplier_id')->constrained('procurement_suppliers')->cascadeOnDelete();
            $table->timestamps();

            $table->unique(['tender_id', 'supplier_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('procurement_tender_invites');
    }
};
