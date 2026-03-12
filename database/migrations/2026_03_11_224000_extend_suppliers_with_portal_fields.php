<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('procurement_suppliers', function (Blueprint $table) {
            // Authentication
            $table->string('password')->nullable()->after('email');
            $table->boolean('portal_access')->default(false)->after('password');
            $table->timestamp('email_verified_at')->nullable()->after('portal_access');
            $table->rememberToken()->after('email_verified_at');

            // Vendor identity
            $table->string('vendor_code', 50)->nullable()->after('code');
            $table->string('vat_number', 50)->nullable()->after('tin_number');
            $table->string('website', 200)->nullable()->after('phone');
            $table->string('contact_person_title', 50)->nullable()->after('contact_person');
            $table->string('contact_phone', 50)->nullable()->after('contact_person_title');
            $table->string('contact_email', 150)->nullable()->after('contact_phone');

            // Address
            $table->string('city', 100)->nullable()->after('address');
            $table->string('state', 100)->nullable()->after('city');
            $table->string('zip_code', 20)->nullable()->after('state');
            $table->string('country', 100)->nullable()->default('Ethiopia')->after('zip_code');

            // Shipping / billing
            $table->text('billing_address')->nullable()->after('country');
            $table->text('shipping_address')->nullable()->after('billing_address');
            $table->boolean('same_as_billing')->default(true)->after('shipping_address');

            // Financial
            $table->string('currency', 10)->nullable()->default('ETB')->after('bank_account');
            $table->string('payment_terms', 100)->nullable()->after('currency');
            $table->string('bank_branch', 150)->nullable()->after('bank_account');
            $table->string('bank_swift', 50)->nullable()->after('bank_branch');
            $table->string('bank_iban', 100)->nullable()->after('bank_swift');

            // Return / legal
            $table->text('return_policy')->nullable()->after('payment_terms');
            $table->string('default_language', 10)->nullable()->default('en')->after('return_policy');

            // Meta
            $table->string('logo_path', 300)->nullable()->after('notes');
            $table->date('registration_date')->nullable()->after('logo_path');
            $table->date('contract_expiry_date')->nullable()->after('registration_date');
        });
    }

    public function down(): void
    {
        Schema::table('procurement_suppliers', function (Blueprint $table) {
            $table->dropColumn([
                'password', 'portal_access', 'email_verified_at', 'remember_token',
                'vendor_code', 'vat_number', 'website', 'contact_person_title',
                'contact_phone', 'contact_email', 'city', 'state', 'zip_code', 'country',
                'billing_address', 'shipping_address', 'same_as_billing',
                'currency', 'payment_terms', 'bank_branch', 'bank_swift', 'bank_iban',
                'return_policy', 'default_language', 'logo_path',
                'registration_date', 'contract_expiry_date',
            ]);
        });
    }
};
