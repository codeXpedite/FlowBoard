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
        Schema::create('project_templates', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->text('description')->nullable();
            $table->string('category')->default('general'); // general, software, marketing, etc.
            $table->string('color')->default('#3B82F6');
            $table->json('default_task_statuses'); // Array of status objects
            $table->json('default_tasks')->nullable(); // Predefined tasks for this template
            $table->json('default_settings')->nullable(); // Default project settings
            $table->boolean('is_public')->default(true); // Can be used by all users
            $table->foreignId('created_by')->nullable()->constrained('users')->onDelete('set null');
            $table->integer('usage_count')->default(0); // How many times this template was used
            $table->json('tags')->nullable(); // Tags for categorization
            $table->timestamps();
            
            $table->index(['category', 'is_public']);
            $table->index('usage_count');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('project_templates');
    }
};