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
      if(!Schema::hasTable('recruitment_channels')) {
        Schema::create('recruitment_channels', function (Blueprint $table) {
            $table->id();
            $table->string('name');                  // LinkedIn, Internal Referral …
            $table->string('type');                  // online | offline | agency | internal
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });
    }}

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('recruitment_channels');
    }
};
