<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('file_shares', function (Blueprint $table): void {
            $table->foreignId('shared_with_employee_id')
                ->nullable()
                ->after('shared_with_user_id')
                ->constrained('employees')
                ->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('file_shares', function (Blueprint $table): void {
            $table->dropConstrainedForeignId('shared_with_employee_id');
        });
    }
};
