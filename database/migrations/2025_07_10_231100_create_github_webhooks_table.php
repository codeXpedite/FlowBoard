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
        Schema::create('github_webhooks', function (Blueprint $table) {
            $table->id();
            $table->foreignId('github_repository_id')->constrained()->onDelete('cascade');
            $table->string('event_type'); // issues, push, pull_request, etc.
            $table->string('action')->nullable(); // opened, closed, synchronize, etc.
            $table->string('github_delivery_id')->unique(); // X-GitHub-Delivery header
            $table->json('payload'); // Full webhook payload
            $table->enum('status', ['pending', 'processed', 'failed', 'skipped'])->default('pending');
            $table->text('error_message')->nullable();
            $table->json('processing_result')->nullable(); // Result of processing (created tasks, etc.)
            $table->timestamp('processed_at')->nullable();
            $table->timestamps();

            $table->index(['github_repository_id', 'event_type']);
            $table->index(['status', 'created_at']);
            $table->index('github_delivery_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('github_webhooks');
    }
};