<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('finance_account_sub_classifications', function (Blueprint $table) {
            $table->id();
            $table->string('name', 100);                         // e.g. "Cash and Cash Equivalents"
            $table->string('code', 30)->unique()->nullable();    // optional short code e.g. "CCE"
            $table->enum('classification', [                     // links to the parent top-level group
                'asset',
                'liability',
                'equity',
                'income',
                'expense',
            ]);
            $table->text('description')->nullable();
            $table->unsignedInteger('sort_order')->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        // Add nullable FK on chart_of_accounts so each account can optionally
        // be assigned to a sub-classification.
        Schema::table('finance_chart_of_accounts', function (Blueprint $table) {
            $table->foreignId('sub_classification_id')
                  ->nullable()
                  ->after('account_type_id')
                  ->constrained('finance_account_sub_classifications')
                  ->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('finance_chart_of_accounts', function (Blueprint $table) {
            $table->dropForeign(['sub_classification_id']);
            $table->dropColumn('sub_classification_id');
        });

        Schema::dropIfExists('finance_account_sub_classifications');
    }
};
