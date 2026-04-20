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
        // Columns 'note' and 'image' are now created in the original items table migration.
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Handled in table drop.
    }
};
