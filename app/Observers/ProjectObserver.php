<?php

namespace App\Observers;

use App\Models\Project;
use App\Services\CacheService;

class ProjectObserver
{
    protected $cacheService;

    public function __construct(CacheService $cacheService)
    {
        $this->cacheService = $cacheService;
    }

    /**
     * Handle the Project "created" event.
     */
    public function created(Project $project): void
    {
        $this->invalidateProjectCache($project);
    }

    /**
     * Handle the Project "updated" event.
     */
    public function updated(Project $project): void
    {
        $this->invalidateProjectCache($project);
    }

    /**
     * Handle the Project "deleted" event.
     */
    public function deleted(Project $project): void
    {
        $this->invalidateProjectCache($project);
    }

    /**
     * Handle the Project "restored" event.
     */
    public function restored(Project $project): void
    {
        $this->invalidateProjectCache($project);
    }

    /**
     * Handle the Project "force deleted" event.
     */
    public function forceDeleted(Project $project): void
    {
        $this->invalidateProjectCache($project);
    }

    /**
     * Invalidate project-related cache
     */
    protected function invalidateProjectCache(Project $project): void
    {
        $this->cacheService->invalidateProject($project->id, $project->owner_id);
    }
}