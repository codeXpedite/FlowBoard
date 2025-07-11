<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class TaskStatus extends Model
{
    protected $fillable = [
        'name',
        'slug',
        'color',
        'sort_order',
        'project_id',
    ];

    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    public function tasks(): HasMany
    {
        return $this->hasMany(Task::class)->orderBy('sort_order');
    }

    public static function getDefaultStatuses(): array
    {
        return [
            ['name' => 'To Do', 'slug' => 'to_do', 'color' => '#6B7280', 'sort_order' => 1],
            ['name' => 'In Progress', 'slug' => 'in_progress', 'color' => '#F59E0B', 'sort_order' => 2],
            ['name' => 'Review', 'slug' => 'review', 'color' => '#8B5CF6', 'sort_order' => 3],
            ['name' => 'Done', 'slug' => 'done', 'color' => '#10B981', 'sort_order' => 4],
        ];
    }
}
