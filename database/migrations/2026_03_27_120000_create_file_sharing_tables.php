<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('shared_folders', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('parent_id')->nullable()->constrained('shared_folders')->nullOnDelete();
            $table->string('name');
            $table->text('description')->nullable();
            $table->enum('visibility', ['private', 'internal', 'client', 'public'])->default('internal');
            $table->foreignId('owner_id')->nullable()->constrained('users')->nullOnDelete();
            $table->nullableMorphs('context');
            $table->timestamps();
            $table->softDeletes();

            $table->index(['parent_id', 'name']);
            $table->index(['visibility', 'owner_id']);
        });

        Schema::create('shared_files', function (Blueprint $table): void {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->foreignId('folder_id')->nullable()->constrained('shared_folders')->nullOnDelete();
            $table->string('display_name');
            $table->string('original_name')->nullable();
            $table->string('disk', 50)->default('local');
            $table->string('path');
            $table->string('extension', 20)->nullable();
            $table->string('mime_type')->nullable();
            $table->unsignedBigInteger('size_bytes')->default(0);
            $table->string('checksum', 64)->nullable();
            $table->unsignedInteger('version_no')->default(1);
            $table->enum('visibility', ['private', 'internal', 'client', 'public'])->default('internal');
            $table->boolean('is_locked')->default(false);
            $table->foreignId('uploaded_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('expires_at')->nullable();
            $table->nullableMorphs('context');
            $table->timestamps();
            $table->softDeletes();

            $table->index(['folder_id', 'display_name']);
            $table->index(['visibility', 'uploaded_by']);
        });

        Schema::create('shared_file_versions', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('shared_file_id')->constrained('shared_files')->cascadeOnDelete();
            $table->unsignedInteger('version_no');
            $table->string('disk', 50);
            $table->string('path');
            $table->string('original_name')->nullable();
            $table->string('extension', 20)->nullable();
            $table->string('mime_type')->nullable();
            $table->unsignedBigInteger('size_bytes')->default(0);
            $table->string('checksum', 64)->nullable();
            $table->text('change_note')->nullable();
            $table->foreignId('uploaded_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->unique(['shared_file_id', 'version_no']);
        });

        Schema::create('file_shares', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('shared_file_id')->nullable()->constrained('shared_files')->cascadeOnDelete();
            $table->foreignId('shared_folder_id')->nullable()->constrained('shared_folders')->cascadeOnDelete();
            $table->enum('share_type', ['staff', 'client', 'public'])->default('staff');
            $table->enum('access_level', ['view', 'download', 'upload', 'manage'])->default('view');
            $table->foreignId('shared_with_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('shared_with_email')->nullable();
            $table->string('share_token', 100)->nullable()->unique();
            $table->string('password')->nullable();
            $table->unsignedInteger('max_downloads')->nullable();
            $table->unsignedInteger('download_count')->default(0);
            $table->timestamp('expires_at')->nullable();
            $table->boolean('is_active')->default(true);
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index(['share_type', 'is_active']);
            $table->index(['shared_with_user_id', 'shared_with_email']);
        });

        Schema::create('file_access_logs', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('shared_file_id')->nullable()->constrained('shared_files')->nullOnDelete();
            $table->foreignId('file_share_id')->nullable()->constrained('file_shares')->nullOnDelete();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->enum('action', ['uploaded', 'previewed', 'downloaded', 'shared', 'revoked', 'deleted'])->default('previewed');
            $table->string('ip_address', 45)->nullable();
            $table->text('user_agent')->nullable();
            $table->text('notes')->nullable();
            $table->nullableMorphs('context');
            $table->timestamp('accessed_at')->useCurrent();
            $table->timestamps();

            $table->index(['action', 'accessed_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('file_access_logs');
        Schema::dropIfExists('file_shares');
        Schema::dropIfExists('shared_file_versions');
        Schema::dropIfExists('shared_files');
        Schema::dropIfExists('shared_folders');
    }
};
