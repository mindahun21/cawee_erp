<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('procurement_approval_stages', function (Blueprint $table) {
            $table->id();
            $table->foreignId('workflow_id')
                ->constrained('procurement_approval_workflows')
                ->cascadeOnDelete();
            $table->unsignedTinyInteger('stage_order');      // 1, 2, 3 …
            $table->string('stage_name');                    // "Finance Manager", "Director", "CEO"
            $table->string('required_role');                 // 'procurement_finance', 'procurement_director' …
            $table->boolean('can_reject')->default(true);
            $table->timestamps();

            $table->unique(['workflow_id', 'stage_order']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('procurement_approval_stages');
    }
};
