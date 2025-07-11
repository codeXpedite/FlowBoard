<?php

namespace App\Observers;

use App\Models\Task;
use App\Services\CacheService;

class TaskObserver
{
    protected $cacheService;

    public function __construct(CacheService $cacheService)
    {
        $this->cacheService = $cacheService;
    }

    /**
     * Handle the Task "created" event.
     */
    public function created(Task $task): void
    {
        $this->invalidateTaskCache($task);
    }

    /**
     * Handle the Task "updated" event.
     */
    public function updated(Task $task): void
    {
        $this->invalidateTaskCache($task);
    }

    /**
     * Handle the Task "deleted" event.
     */
    public function deleted(Task $task): void
    {
        $this->invalidateTaskCache($task);
    }

    /**
     * Handle the Task "restored" event.
     */
    public function restored(Task $task): void
    {
        $this->invalidateTaskCache($task);
    }

    /**
     * Handle the Task "force deleted" event.
     */
    public function forceDeleted(Task $task): void
    {
        $this->invalidateTaskCache($task);
    }

    /**
     * Invalidate task-related cache
     */
    protected function invalidateTaskCache(Task $task): void
    {
        $this->cacheService->invalidateTask($task->id, $task->project_id);
        
        if ($task->assigned_to) {
            $this->cacheService->invalidateUser($task->assigned_to);
        }
        
        if ($task->created_by) {
            $this->cacheService->invalidateUser($task->created_by);
        }
    }
}