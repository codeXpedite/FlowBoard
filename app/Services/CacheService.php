<?php

namespace App\Services;

use App\Models\Project;
use App\Models\Task;
use App\Models\User;
use Illuminate\Support\Facades\Cache;

class CacheService
{
    const PROJECT_CACHE_TTL = 3600; // 1 hour
    const TASK_CACHE_TTL = 1800; // 30 minutes
    const USER_CACHE_TTL = 7200; // 2 hours
    const ANALYTICS_CACHE_TTL = 900; // 15 minutes

    /**
     * Get or cache project data with related models
     */
    public function getProject(int $projectId, int $userId): ?Project
    {
        $cacheKey = "project:{$projectId}:user:{$userId}";
        
        return Cache::remember($cacheKey, self::PROJECT_CACHE_TTL, function () use ($projectId, $userId) {
            return Project::with([
                'taskStatuses' => function($query) {
                    $query->orderBy('sort_order');
                },
                'tasks' => function($query) {
                    $query->with(['assignedUser', 'createdBy', 'tags', 'subtasks'])
                          ->whereNull('parent_task_id')
                          ->orderBy('sort_order');
                },
                'owner',
                'tags'
            ])
            ->where('id', $projectId)
            ->where('owner_id', $userId)
            ->first();
        });
    }

    /**
     * Get or cache project task statistics
     */
    public function getProjectStats(int $projectId): array
    {
        $cacheKey = "project_stats:{$projectId}";
        
        return Cache::remember($cacheKey, self::ANALYTICS_CACHE_TTL, function () use ($projectId) {
            $project = Project::find($projectId);
            if (!$project) {
                return [];
            }

            $tasks = $project->tasks();
            
            return [
                'total_tasks' => $tasks->count(),
                'completed_tasks' => $tasks->whereNotNull('completed_at')->count(),
                'pending_tasks' => $tasks->whereNull('completed_at')->count(),
                'overdue_tasks' => $tasks->whereNull('completed_at')
                                       ->whereDate('due_date', '<', now())
                                       ->count(),
                'tasks_by_priority' => [
                    'urgent' => $tasks->where('priority', 'urgent')->count(),
                    'high' => $tasks->where('priority', 'high')->count(),
                    'medium' => $tasks->where('priority', 'medium')->count(),
                    'low' => $tasks->where('priority', 'low')->count(),
                ],
                'tasks_by_status' => $project->taskStatuses()
                    ->withCount('tasks')
                    ->get()
                    ->pluck('tasks_count', 'name')
                    ->toArray(),
            ];
        });
    }

    /**
     * Get or cache user's project list
     */
    public function getUserProjects(int $userId): \Illuminate\Database\Eloquent\Collection
    {
        $cacheKey = "user_projects:{$userId}";
        
        return Cache::remember($cacheKey, self::PROJECT_CACHE_TTL, function () use ($userId) {
            return Project::where('owner_id', $userId)
                ->with(['taskStatuses', 'owner'])
                ->withCount(['tasks', 'activeTasks', 'completedTasks'])
                ->orderBy('created_at', 'desc')
                ->get();
        });
    }

    /**
     * Get or cache task details with relations
     */
    public function getTask(int $taskId): ?Task
    {
        $cacheKey = "task:{$taskId}";
        
        return Cache::remember($cacheKey, self::TASK_CACHE_TTL, function () use ($taskId) {
            return Task::with([
                'project',
                'taskStatus',
                'assignedUser',
                'createdBy',
                'comments.user',
                'tags',
                'subtasks',
                'parentTask'
            ])->find($taskId);
        });
    }

    /**
     * Get or cache user analytics
     */
    public function getUserAnalytics(int $userId): array
    {
        $cacheKey = "user_analytics:{$userId}";
        
        return Cache::remember($cacheKey, self::ANALYTICS_CACHE_TTL, function () use ($userId) {
            $user = User::find($userId);
            if (!$user) {
                return [];
            }

            $assignedTasks = Task::where('assigned_to', $userId);
            $createdTasks = Task::where('created_by', $userId);
            
            return [
                'assigned_tasks_count' => $assignedTasks->count(),
                'completed_tasks_count' => $assignedTasks->whereNotNull('completed_at')->count(),
                'pending_tasks_count' => $assignedTasks->whereNull('completed_at')->count(),
                'created_tasks_count' => $createdTasks->count(),
                'overdue_tasks_count' => $assignedTasks->whereNull('completed_at')
                                                      ->whereDate('due_date', '<', now())
                                                      ->count(),
                'completion_rate' => $assignedTasks->count() > 0 
                    ? round(($assignedTasks->whereNotNull('completed_at')->count() / $assignedTasks->count()) * 100, 2)
                    : 0,
            ];
        });
    }

    /**
     * Get or cache GitHub repository data
     */
    public function getGitHubRepoStats(int $projectId): array
    {
        $cacheKey = "github_repo_stats:{$projectId}";
        
        return Cache::remember($cacheKey, self::ANALYTICS_CACHE_TTL, function () use ($projectId) {
            $project = Project::find($projectId);
            if (!$project) {
                return [];
            }

            $githubTasks = $project->tasks()->whereNotNull('github_repository_id');
            
            return [
                'total_github_tasks' => $githubTasks->count(),
                'synced_tasks' => $githubTasks->whereNotNull('github_issue_id')->count(),
                'auto_created_tasks' => $githubTasks->where('auto_created', true)->count(),
                'repositories_count' => $project->githubRepositories()->count(),
            ];
        });
    }

    /**
     * Cache project team members
     */
    public function getProjectUsers(int $projectId): \Illuminate\Database\Eloquent\Collection
    {
        $cacheKey = "project_users:{$projectId}";
        
        return Cache::remember($cacheKey, self::USER_CACHE_TTL, function () use ($projectId) {
            return Task::where('project_id', $projectId)
                ->with('assignedUser')
                ->whereNotNull('assigned_to')
                ->get()
                ->pluck('assignedUser')
                ->unique('id')
                ->filter()
                ->sortBy('name');
        });
    }

    /**
     * Cache project tags
     */
    public function getProjectTags(int $projectId): \Illuminate\Database\Eloquent\Collection
    {
        $cacheKey = "project_tags:{$projectId}";
        
        return Cache::remember($cacheKey, self::PROJECT_CACHE_TTL, function () use ($projectId) {
            $project = Project::find($projectId);
            return $project ? $project->tags()->orderBy('name')->get() : collect();
        });
    }

    /**
     * Invalidate project-related cache
     */
    public function invalidateProject(int $projectId, int $userId = null): void
    {
        Cache::forget("project:{$projectId}:user:{$userId}");
        Cache::forget("project_stats:{$projectId}");
        Cache::forget("project_users:{$projectId}");
        Cache::forget("project_tags:{$projectId}");
        Cache::forget("github_repo_stats:{$projectId}");
        
        if ($userId) {
            Cache::forget("user_projects:{$userId}");
            Cache::forget("user_analytics:{$userId}");
        }
    }

    /**
     * Invalidate task-related cache
     */
    public function invalidateTask(int $taskId, int $projectId = null, int $userId = null): void
    {
        Cache::forget("task:{$taskId}");
        
        if ($projectId) {
            $this->invalidateProject($projectId, $userId);
        }
    }

    /**
     * Invalidate user-related cache
     */
    public function invalidateUser(int $userId): void
    {
        Cache::forget("user_projects:{$userId}");
        Cache::forget("user_analytics:{$userId}");
        
        // Clear all projects this user owns
        $projects = Project::where('owner_id', $userId)->pluck('id');
        foreach ($projects as $projectId) {
            $this->invalidateProject($projectId, $userId);
        }
    }

    /**
     * Get cache statistics
     */
    public function getCacheStats(): array
    {
        // This would require a cache driver that supports statistics
        // For now, return basic info
        return [
            'cache_driver' => config('cache.default'),
            'project_cache_ttl' => self::PROJECT_CACHE_TTL,
            'task_cache_ttl' => self::TASK_CACHE_TTL,
            'user_cache_ttl' => self::USER_CACHE_TTL,
            'analytics_cache_ttl' => self::ANALYTICS_CACHE_TTL,
        ];
    }

    /**
     * Clear all application cache
     */
    public function clearAllCache(): void
    {
        Cache::flush();
    }

    /**
     * Warm up cache for a project
     */
    public function warmUpProject(int $projectId, int $userId): void
    {
        // Pre-load commonly accessed data
        $this->getProject($projectId, $userId);
        $this->getProjectStats($projectId);
        $this->getProjectUsers($projectId);
        $this->getProjectTags($projectId);
        $this->getGitHubRepoStats($projectId);
    }
}