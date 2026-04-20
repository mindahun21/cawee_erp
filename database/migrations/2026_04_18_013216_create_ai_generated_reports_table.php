<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ai_generated_reports', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->string('conversation_id')->nullable()->index();
            $table->string('title');
            $table->text('prompt');
            $table->json('report_json');
            $table->string('module_context', 100)->nullable();
            $table->boolean('is_saved')->default(false);
            $table->timestamps();
            
            $table->index(['user_id', 'is_saved']);
            $table->index('created_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ai_generated_reports');
    }
};
