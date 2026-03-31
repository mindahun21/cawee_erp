<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('donor_interactions', function (Blueprint $blueprint) {
            $blueprint->id();
            $blueprint->foreignId('donor_id')->constrained()->cascadeOnDelete();
            $blueprint->string('interaction_type'); // call, email, meeting, note, etc.
            $blueprint->datetime('interaction_date');
            $blueprint->string('subject')->nullable();
            $blueprint->text('notes')->nullable();
            $blueprint->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $blueprint->timestamps();
            $blueprint->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('donor_interactions');
    }
};
