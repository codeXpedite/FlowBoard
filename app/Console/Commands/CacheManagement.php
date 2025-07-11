<?php

namespace App\Console\Commands;

use App\Services\CacheService;
use App\Models\Project;
use App\Models\User;
use Illuminate\Console\Command;

class CacheManagement extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'cache:manage 
                            {action : The action to perform (clear|warm|stats)}
                            {--project= : Project ID for project-specific operations}
                            {--user= : User ID for user-specific operations}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Manage application cache for projects and tasks';

    protected CacheService $cacheService;

    public function __construct(CacheService $cacheService)
    {
        parent::__construct();
        $this->cacheService = $cacheService;
    }

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $action = $this->argument('action');

        switch ($action) {
            case 'clear':
                return $this->clearCache();
            
            case 'warm':
                return $this->warmCache();
            
            case 'stats':
                return $this->showStats();
            
            default:
                $this->error('Invalid action. Use: clear, warm, or stats');
                return 1;
        }
    }

    protected function clearCache(): int
    {
        $projectId = $this->option('project');
        $userId = $this->option('user');

        if ($projectId && $userId) {
            $this->cacheService->invalidateProject($projectId, $userId);
            $this->info("Cleared cache for project {$projectId} and user {$userId}");
        } elseif ($projectId) {
            $this->cacheService->invalidateProject($projectId);
            $this->info("Cleared cache for project {$projectId}");
        } elseif ($userId) {
            $this->cacheService->invalidateUser($userId);
            $this->info("Cleared cache for user {$userId}");
        } else {
            $this->cacheService->clearAllCache();
            $this->info('Cleared all application cache');
        }

        return 0;
    }

    protected function warmCache(): int
    {
        $projectId = $this->option('project');
        $userId = $this->option('user');

        if ($projectId && $userId) {
            $this->warmProjectCache($projectId, $userId);
        } elseif ($projectId) {
            $this->info('User ID required for project cache warming');
            return 1;
        } else {
            $this->warmAllCache();
        }

        return 0;
    }

    protected function warmProjectCache(int $projectId, int $userId): void
    {
        $this->info("Warming cache for project {$projectId}...");
        
        try {
            $this->cacheService->warmUpProject($projectId, $userId);
            $this->info('✓ Project cache warmed successfully');
        } catch (\Exception $e) {
            $this->error("Failed to warm project cache: {$e->getMessage()}");
        }
    }

    protected function warmAllCache(): void
    {
        $this->info('Warming cache for all projects...');
        
        $bar = $this->output->createProgressBar();
        $bar->start();

        Project::with('owner')->chunk(50, function ($projects) use ($bar) {
            foreach ($projects as $project) {
                try {
                    $this->cacheService->warmUpProject($project->id, $project->owner_id);
                    $bar->advance();
                } catch (\Exception $e) {
                    $this->newLine();
                    $this->warn("Failed to warm cache for project {$project->id}: {$e->getMessage()}");
                }
            }
        });

        $bar->finish();
        $this->newLine();
        $this->info('✓ All project caches warmed successfully');
    }

    protected function showStats(): int
    {
        $stats = $this->cacheService->getCacheStats();

        $this->info('Cache Configuration:');
        $this->table(
            ['Setting', 'Value'],
            [
                ['Cache Driver', $stats['cache_driver']],
                ['Project Cache TTL', $stats['project_cache_ttl'] . ' seconds'],
                ['Task Cache TTL', $stats['task_cache_ttl'] . ' seconds'],
                ['User Cache TTL', $stats['user_cache_ttl'] . ' seconds'],
                ['Analytics Cache TTL', $stats['analytics_cache_ttl'] . ' seconds'],
            ]
        );

        // Show some usage statistics
        $projectCount = Project::count();
        $userCount = User::count();

        $this->info("\nApplication Statistics:");
        $this->table(
            ['Metric', 'Count'],
            [
                ['Total Projects', $projectCount],
                ['Total Users', $userCount],
                ['Potential Cache Keys', $projectCount * 5 + $userCount * 2], // Rough estimate
            ]
        );

        return 0;
    }
}
