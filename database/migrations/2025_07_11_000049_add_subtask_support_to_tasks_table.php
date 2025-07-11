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
        Schema::table('tasks', function (Blueprint $table) {
            // Check if columns don't already exist
            if (!Schema::hasColumn('tasks', 'depth')) {
                $table->integer('depth')->default(0)->after('parent_task_id');
            }
            if (!Schema::hasColumn('tasks', 'path')) {
                $table->string('path')->nullable()->after('depth');
            }
            if (!Schema::hasColumn('tasks', 'is_subtask')) {
                $table->boolean('is_subtask')->default(false)->after('path');
            }
            if (!Schema::hasColumn('tasks', 'subtask_order')) {
                $table->integer('subtask_order')->default(0)->after('is_subtask');
            }
        });
        
        // Add indexes separately
        Schema::table('tasks', function (Blueprint $table) {
            $table->index(['parent_task_id', 'subtask_order']);
            $table->index(['project_id', 'parent_task_id']);
            $table->index(['is_subtask', 'parent_task_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('tasks', function (Blueprint $table) {
            $table->dropIndex(['parent_task_id', 'subtask_order']);
            $table->dropIndex(['project_id', 'parent_task_id']);
            $table->dropIndex(['is_subtask', 'parent_task_id']);
            
            $table->dropForeign(['parent_task_id']);
            $table->dropColumn(['parent_task_id', 'depth', 'path', 'is_subtask', 'subtask_order']);
        });
    }
};