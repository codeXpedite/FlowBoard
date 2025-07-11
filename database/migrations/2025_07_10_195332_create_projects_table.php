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
        Schema::create('projects', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->text('description')->nullable();
            $table->string('status')->default('active'); // active, archived, completed
            $table->string('color', 7)->default('#3B82F6'); // hex color for UI
            $table->foreignId('owner_id')->constrained('users')->onDelete('cascade');
            $table->json('settings')->nullable(); // project-specific settings
            $table->timestamp('archived_at')->nullable();
            $table->timestamps();
            $table->softDeletes();
            
            $table->index(['owner_id', 'status']);
            $table->index('status');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('projects');
    }
};
