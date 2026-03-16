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
        Schema::table('procurement_bids', function (Blueprint $table) {
            $table->enum('status', [
                'Submitted', 'Under Review', 'Pending Approval', 'Shortlisted', 'Awarded', 'Rejected'
            ])->default('Submitted')->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('procurement_bids', function (Blueprint $table) {
            $table->enum('status', [
                'Submitted', 'Under Review', 'Shortlisted', 'Awarded', 'Rejected'
            ])->default('Submitted')->change();
        });
    }
};
