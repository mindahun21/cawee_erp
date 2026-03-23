<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('recruitment_channels', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('type');
            // Values: online | offline | agency | internal

            $table->string('status');
            // Values: active | inactive

            $table->foreignId('responsible_person_id')
                  ->nullable()
                  ->constrained('users')
                  ->nullOnDelete();

            $table->string('language')->default('en');
            // Values: en | am

            $table->string('submit_button_text')->default('Submit');

            $table->text('success_message')->nullable();
            // Message shown to candidate after successful portal form submission

            $table->boolean('notify_on_submission')->default(true);

            $table->string('notification_target')->default('specific_staff');
            // Values: specific_staff | staff_with_roles | responsible_person

            $table->foreignId('notification_person_id')
                  ->nullable()
                  ->constrained('users')
                  ->nullOnDelete();

            $table->json('form_schema')->nullable();
            // Stores the ordered list of fields HR has added to this channel's form

            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('recruitment_channels');
    }
};
