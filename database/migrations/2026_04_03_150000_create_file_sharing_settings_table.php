<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('file_sharing_settings', function (Blueprint $table) {
            $table->id();
            $table->string('key', 100)->unique();
            $table->string('group', 60)->default('general');
            $table->string('label', 140);
            $table->text('value')->nullable();
            $table->string('data_type', 20)->default('string');
            $table->text('description')->nullable();
            $table->timestamps();
        });

        DB::table('file_sharing_settings')->insert([
            [
                'key' => 'max_file_size_mb',
                'group' => 'general',
                'label' => 'Maximum File Size (MB)',
                'value' => '25',
                'data_type' => 'integer',
                'description' => 'Maximum upload size allowed for a shared file.',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'key' => 'allowed_file_types',
                'group' => 'general',
                'label' => 'Allowed File Types',
                'value' => json_encode(['pdf', 'doc', 'docx', 'xls', 'xlsx', 'png', 'jpg', 'jpeg', 'txt', 'csv', 'zip']),
                'data_type' => 'json',
                'description' => 'List of allowed file extensions.',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'key' => 'default_link_expiry_days',
                'group' => 'sharing',
                'label' => 'Default Link Expiry (Days)',
                'value' => '7',
                'data_type' => 'integer',
                'description' => 'Auto-set expiry days for new shares when expiry is not provided.',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'key' => 'public_sharing_enabled',
                'group' => 'sharing',
                'label' => 'Enable Public Sharing',
                'value' => 'true',
                'data_type' => 'boolean',
                'description' => 'Allow or block creation of public share links.',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'key' => 'require_public_password',
                'group' => 'security',
                'label' => 'Require Password for Public Shares',
                'value' => 'false',
                'data_type' => 'boolean',
                'description' => 'Require password protection for every public share link.',
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ]);
    }

    public function down(): void
    {
        Schema::dropIfExists('file_sharing_settings');
    }
};
