<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Task extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'title',
        'description',
        'priority',
        'project_id',
        'task_status_id',
        'assigned_to',
        'created_by',
        'parent_task_id',
        'depth',
        'path',
        'is_subtask',
        'subtask_order',
        'sort_order',
        'due_date',
        'completed_at',
        'tags',
        'github_data',
        'github_repository_id',
        'github_issue_number',
        'github_issue_id',
        'github_branch',
        'auto_created',
    ];

    protected $casts = [
        'due_date' => 'datetime',
        'completed_at' => 'datetime',
        'tags' => 'array',
        'github_data' => 'array',
        'auto_created' => 'boolean',
        'is_subtask' => 'boolean',
    ];

    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    public function taskStatus(): BelongsTo
    {
        return $this->belongsTo(TaskStatus::class);
    }

    public function assignedUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_to');
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function parentTask(): BelongsTo
    {
        return $this->belongsTo(Task::class, 'parent_task_id');
    }

    public function subtasks(): HasMany
    {
        return $this->hasMany(Task::class, 'parent_task_id')->orderBy('subtask_order');
    }

    public function comments(): HasMany
    {
        return $this->hasMany(TaskComment::class)->orderBy('created_at', 'desc');
    }

    public function tags(): BelongsToMany
    {
        return $this->belongsToMany(Tag::class);
    }

    public function githubRepository(): BelongsTo
    {
        return $this->belongsTo(GitHubRepository::class);
    }

    public function getIsCompletedAttribute(): bool
    {
        return !is_null($this->completed_at);
    }

    public function getIsOverdueAttribute(): bool
    {
        return $this->due_date && $this->due_date->isPast() && !$this->is_completed;
    }

    public function getPriorityColorAttribute(): string
    {
        return match($this->priority) {
            'urgent' => '#EF4444',
            'high' => '#F97316',
            'medium' => '#F59E0B',
            'low' => '#10B981',
            default => '#6B7280'
        };
    }

    public function markCompleted(): void
    {
        $this->update(['completed_at' => now()]);
    }

    public function markIncomplete(): void
    {
        $this->update(['completed_at' => null]);
    }

    public function moveToStatus(TaskStatus $status): void
    {
        $this->update(['task_status_id' => $status->id]);
        
        if ($status->slug === 'done') {
            $this->markCompleted();
        } else {
            $this->markIncomplete();
        }
    }

    public function isFromGitHub(): bool
    {
        return !is_null($this->github_repository_id);
    }

    public function getGitHubUrl(): ?string
    {
        if ($this->github_data && isset($this->github_data['html_url'])) {
            return $this->github_data['html_url'];
        }
        
        return null;
    }

    public function allSubtasks(): HasMany
    {
        return $this->hasMany(Task::class, 'parent_task_id');
    }

    // Scope for main tasks (non-subtasks)
    public function scopeMainTasks($query)
    {
        return $query->where('is_subtask', false)->orWhereNull('parent_task_id');
    }

    // Scope for subtasks only
    public function scopeSubtasks($query)
    {
        return $query->where('is_subtask', true);
    }

    // Get all ancestor tasks
    public function getAncestors()
    {
        $ancestors = collect();
        $current = $this->parentTask;
        
        while ($current) {
            $ancestors->prepend($current);
            $current = $current->parentTask;
        }
        
        return $ancestors;
    }

    // Get all descendant tasks
    public function getDescendants()
    {
        $descendants = collect();
        
        foreach ($this->subtasks as $subtask) {
            $descendants->push($subtask);
            $descendants = $descendants->merge($subtask->getDescendants());
        }
        
        return $descendants;
    }

    // Check if task has subtasks
    public function hasSubtasks(): bool
    {
        return $this->subtasks()->count() > 0;
    }

    // Get subtask completion percentage
    public function getSubtaskCompletionPercentage(): int
    {
        $totalSubtasks = $this->subtasks()->count();
        
        if ($totalSubtasks === 0) {
            return 0;
        }
        
        $completedSubtasks = $this->subtasks()->whereNotNull('completed_at')->count();
        
        return round(($completedSubtasks / $totalSubtasks) * 100);
    }

    // Generate path for hierarchy
    public function updatePath(): void
    {
        if ($this->parent_task_id) {
            $parent = $this->parentTask;
            $this->path = $parent->path ? $parent->path . '/' . $this->id : (string) $this->id;
            $this->depth = $parent->depth + 1;
        } else {
            $this->path = (string) $this->id;
            $this->depth = 0;
        }
        
        $this->save();
        
        // Update all subtasks' paths
        foreach ($this->subtasks as $subtask) {
            $subtask->updatePath();
        }
    }

    // Add a subtask
    public function addSubtask(array $data): Task
    {
        $data['parent_task_id'] = $this->id;
        $data['project_id'] = $this->project_id;
        $data['is_subtask'] = true;
        $data['depth'] = $this->depth + 1;
        $data['subtask_order'] = $this->subtasks()->count();
        
        $subtask = Task::create($data);
        $subtask->updatePath();
        
        return $subtask;
    }

    // Move subtask to different order
    public function moveSubtask(Task $subtask, int $newOrder): void
    {
        if ($subtask->parent_task_id !== $this->id) {
            return; // Not our subtask
        }
        
        $oldOrder = $subtask->subtask_order;
        
        if ($newOrder > $oldOrder) {
            // Moving down
            $this->subtasks()
                ->where('subtask_order', '>', $oldOrder)
                ->where('subtask_order', '<=', $newOrder)
                ->decrement('subtask_order');
        } else {
            // Moving up
            $this->subtasks()
                ->where('subtask_order', '>=', $newOrder)
                ->where('subtask_order', '<', $oldOrder)
                ->increment('subtask_order');
        }
        
        $subtask->update(['subtask_order' => $newOrder]);
    }

    // Get root task
    public function getRootTask(): Task
    {
        $current = $this;
        while ($current->parentTask) {
            $current = $current->parentTask;
        }
        return $current;
    }

    // Check if task can have subtasks (max depth limit)
    public function canHaveSubtasks(): bool
    {
        return $this->depth < 3; // Maximum 3 levels deep
    }

    public function scopeAutoCreated($query)
    {
        return $query->where('auto_created', true);
    }

    public function scopeFromGitHub($query)
    {
        return $query->whereNotNull('github_repository_id');
    }
}
