<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('recruitment_evaluation_form_template_lines', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('template_id');
            $table->unsignedBigInteger('group_criteria_id');
            $table->unsignedBigInteger('criteria_id');
            $table->unsignedTinyInteger('proportion');
            $table->unsignedInteger('sort_order')->default(0);
            
            $table->foreign('template_id', 'reftl_tmpl_fk')->references('id')->on('recruitment_evaluation_form_templates')->cascadeOnDelete();
            $table->foreign('group_criteria_id', 'reftl_grp_fk')->references('id')->on('recruitment_evaluation_criterias')->restrictOnDelete();
            $table->foreign('criteria_id', 'reftl_crit_fk')->references('id')->on('recruitment_evaluation_criterias')->restrictOnDelete();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('recruitment_evaluation_form_template_lines');
    }
};
