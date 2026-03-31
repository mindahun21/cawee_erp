<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('pledges', function (Blueprint $blueprint) {
            $blueprint->id();
            $blueprint->foreignId('donor_id')->constrained()->cascadeOnDelete();
            $blueprint->foreignId('campaign_id')->nullable()->constrained()->nullOnDelete();
            $blueprint->decimal('total_amount', 12, 2);
            $blueprint->foreignId('currency_id')->constrained()->cascadeOnDelete();
            $blueprint->date('start_date');
            $blueprint->date('end_date')->nullable();
            $blueprint->string('frequency')->default('one_time'); // monthly, quarterly, yearly, etc.
            $blueprint->string('status')->default('active'); // active, completed, cancelled, overdue
            $blueprint->text('notes')->nullable();
            $blueprint->timestamps();
            $blueprint->softDeletes();
        });

        Schema::table('donations', function (Blueprint $blueprint) {
            $blueprint->foreignId('pledge_id')->nullable()->constrained()->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('donations', function (Blueprint $blueprint) {
            $blueprint->dropConstrainedForeignId('pledge_id');
        });
        Schema::dropIfExists('pledges');
    }
};
