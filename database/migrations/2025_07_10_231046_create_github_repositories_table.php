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
        Schema::create('github_repositories', function (Blueprint $table) {
            $table->id();
            $table->foreignId('project_id')->constrained()->onDelete('cascade');
            $table->string('repository_name'); // owner/repo format
            $table->string('github_id')->unique(); // GitHub's internal repository ID
            $table->string('full_name'); // Full repository name (owner/repo)
            $table->text('description')->nullable();
            $table->string('default_branch')->default('main');
            $table->boolean('private')->default(false);
            $table->string('clone_url')->nullable();
            $table->string('html_url');
            $table->string('webhook_secret')->nullable();
            $table->string('webhook_id')->nullable(); // GitHub webhook ID
            $table->json('webhook_events')->nullable(); // Array of enabled webhook events
            $table->boolean('active')->default(true);
            $table->json('settings')->nullable(); // Repository-specific settings
            $table->timestamp('last_sync_at')->nullable();
            $table->timestamps();

            $table->index(['project_id', 'active']);
            $table->index('github_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('github_repositories');
    }
};