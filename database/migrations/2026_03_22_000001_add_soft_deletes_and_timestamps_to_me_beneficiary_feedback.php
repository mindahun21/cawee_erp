<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Add soft deletes to me_beneficiary_feedback
        Schema::table('me_beneficiary_feedback', function (Blueprint $table) {
            if (! Schema::hasColumn('me_beneficiary_feedback', 'deleted_at')) {
                $table->softDeletes()->after('metadata');
            }
        });

        // Add timestamps if missing (table was created without them originally)
        Schema::table('me_beneficiary_feedback', function (Blueprint $table) {
            if (! Schema::hasColumn('me_beneficiary_feedback', 'created_at')) {
                $table->timestamps()->after('submitted_at');
            }
        });
    }

    public function down(): void
    {
        Schema::table('me_beneficiary_feedback', function (Blueprint $table) {
            $table->dropSoftDeletes();
        });
    }
};
