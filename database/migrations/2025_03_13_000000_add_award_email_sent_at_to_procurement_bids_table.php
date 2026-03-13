<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('procurement_bids', function (Blueprint $table) {
            $table->timestamp('award_email_sent_at')->nullable()->after('notes');
        });
    }

    public function down(): void
    {
        Schema::table('procurement_bids', function (Blueprint $table) {
            $table->dropColumn('award_email_sent_at');
        });
    }
};

