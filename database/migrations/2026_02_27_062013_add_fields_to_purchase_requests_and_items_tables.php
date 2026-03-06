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
        Schema::table('purchase_requests', function (Blueprint $table) {
            $table->string('code')->unique()->nullable()->after('id');
            $table->string('name')->nullable()->after('code');
            $table->foreignId('project_id')->nullable()->constrained()->onDelete('set null')->after('name');
            $table->decimal('sale_estimate', 15, 2)->default(0)->after('project_id');
            $table->string('type')->nullable()->after('sale_estimate');
            $table->foreignId('currency_id')->nullable()->constrained()->onDelete('set null')->after('type');
            $table->foreignId('department_id')->nullable()->constrained()->onDelete('set null')->after('currency_id');
            $table->string('sale_invoice_id')->nullable()->after('department_id');
            $table->boolean('share_to_vendors')->default(false)->after('sale_invoice_id');
            $table->decimal('subtotal', 15, 2)->default(0)->after('total_amount');
            $table->decimal('tax_amount', 15, 2)->default(0)->after('subtotal');
        });

        Schema::table('purchase_request_items', function (Blueprint $table) {
            $table->foreignId('item_id')->nullable()->constrained()->onDelete('set null')->after('purchase_request_id');
            $table->decimal('unit_price', 15, 2)->default(0)->after('specification');
            $table->decimal('subtotal', 15, 2)->default(0)->after('unit_price');
            $table->foreignId('tax_id')->nullable()->constrained()->onDelete('set null')->after('subtotal');
            $table->decimal('tax_value', 15, 2)->default(0)->after('tax_id');
            $table->decimal('total', 15, 2)->default(0)->after('tax_value');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('purchase_request_items', function (Blueprint $table) {
            $table->dropForeign(['item_id']);
            $table->dropForeign(['tax_id']);
            $table->dropColumn(['item_id', 'unit_price', 'subtotal', 'tax_id', 'tax_value', 'total']);
        });

        Schema::table('purchase_requests', function (Blueprint $table) {
            $table->dropForeign(['project_id']);
            $table->dropForeign(['currency_id']);
            $table->dropForeign(['department_id']);
            $table->dropColumn(['code', 'name', 'project_id', 'sale_estimate', 'type', 'currency_id', 'department_id', 'sale_invoice_id', 'share_to_vendors', 'subtotal', 'tax_amount']);
        });
    }
};
