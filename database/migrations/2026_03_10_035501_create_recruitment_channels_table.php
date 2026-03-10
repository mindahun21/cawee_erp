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
        Schema::create('recruitment_channels', function (Blueprint $table) {
            $table->id();
            $table->foreignId('added_by')
                ->nullable()
                ->constrained('users');
            $table->foreignId('staff_member_id')
                ->nullable()
                ->constrained('users');

            // $table->foreignId('role_id')
            //     ->nullable()
            //     ->constrained('roles');

            $table->foreignId('responsible_person_id')
                ->nullable()
                ->constrained('users');
            $table->json('form_fields')->nullable();
            $table->string('form_name');
            $table->string('form_type');
            $table->string('language');
            $table->string('submit_button');
            $table->text('message');
            $table->string('status');
            $table->boolean('notify')->default(false);
            $table->string('assignment_type')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('recruitment_channels');
    }
};
