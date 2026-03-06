<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::disableForeignKeyConstraints();

        Schema::dropIfExists('monitoring_indicator_disaggregations');
        Schema::dropIfExists('monitoring_indicator_actuals');
        Schema::dropIfExists('monitoring_indicators');

        Schema::enableForeignKeyConstraints();
    }

    public function down(): void
    {
        // Legacy monitoring schema was intentionally removed.
    }
};

