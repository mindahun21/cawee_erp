<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('asset_assignments', function (Blueprint $table) {
            if (!Schema::hasColumn('asset_assignments', 'assignment_no')) {
                $table->string('assignment_no')->nullable()->unique()->after('id');
            }
            if (!Schema::hasColumn('asset_assignments', 'purpose')) {
                $table->string('purpose')->nullable()->after('department_id');
            }
            if (!Schema::hasColumn('asset_assignments', 'quantity')) {
                $table->integer('quantity')->default(1)->after('asset_id');
            }
            if (!Schema::hasColumn('asset_assignments', 'attachments')) {
                $table->json('attachments')->nullable()->after('remarks');
            }
        });
    }

    public function down(): void
    {
        Schema::table('asset_assignments', function (Blueprint $table) {
            $table->dropColumn(['assignment_no', 'purpose', 'quantity', 'attachments']);
        });
    }
};
