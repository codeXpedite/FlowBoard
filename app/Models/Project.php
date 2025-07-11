<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Project extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'name',
        'description',
        'status',
        'color',
        'owner_id',
        'template_id',
        'settings',
        'archived_at',
    ];

    protected $casts = [
        'settings' => 'array',
        'archived_at' => 'datetime',
    ];

    public function owner(): BelongsTo
    {
        return $this->belongsTo(User::class, 'owner_id');
    }

    public function tasks(): HasMany
    {
        return $this->hasMany(Task::class);
    }

    public function taskStatuses(): HasMany
    {
        return $this->hasMany(TaskStatus::class)->orderBy('sort_order');
    }

    public function activeTasks(): HasMany
    {
        return $this->hasMany(Task::class)->whereNull('completed_at');
    }

    public function tags(): HasMany
    {
        return $this->hasMany(Tag::class);
    }

    public function githubRepositories(): HasMany
    {
        return $this->hasMany(GitHubRepository::class);
    }

    public function completedTasks(): HasMany
    {
        return $this->hasMany(Task::class)->whereNotNull('completed_at');
    }

    public function getIsArchivedAttribute(): bool
    {
        return $this->status === 'archived' || !is_null($this->archived_at);
    }

    public function archive(): void
    {
        $this->update([
            'status' => 'archived',
            'archived_at' => now(),
        ]);
    }

    public function unarchive(): void
    {
        $this->update([
            'status' => 'active',
            'archived_at' => null,
        ]);
    }

    public function template(): BelongsTo
    {
        return $this->belongsTo(ProjectTemplate::class, 'template_id');
    }
}
