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
        Schema::create('task_statuses', function (Blueprint $table) {
            $table->id();
            $table->string('name'); // To Do, In Progress, Done, etc.
            $table->string('slug')->unique(); // to_do, in_progress, done
            $table->string('color', 7)->default('#6B7280'); // hex color
            $table->integer('sort_order')->default(0);
            $table->foreignId('project_id')->constrained()->onDelete('cascade');
            $table->timestamps();
            
            $table->index(['project_id', 'sort_order']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('task_statuses');
    }
};
