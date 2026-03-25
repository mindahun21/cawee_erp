<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('recruitment_applications', function (Blueprint $table) {
            // Application-specific fields moved here from candidate profile
            $table->text('introduce_yourself')->nullable()->after('cover_letter');
            $table->decimal('desired_salary', 14, 2)->nullable()->after('introduce_yourself');
        });
    }

    public function down(): void
    {
        Schema::table('recruitment_applications', function (Blueprint $table) {
            $table->dropColumn(['introduce_yourself', 'desired_salary']);
        });
    }
};
