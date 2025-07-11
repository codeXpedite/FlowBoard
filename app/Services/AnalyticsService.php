<?php

namespace App\Services;

use App\Models\Project;
use App\Models\Task;
use App\Models\User;
use App\Models\TaskComment;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class AnalyticsService
{
    protected Project $project;

    public function __construct(Project $project)
    {
        $this->project = $project;
    }

    public function getOverviewMetrics(string $dateRange): array
    {
        $startDate = $this->getStartDate($dateRange);
        
        $query = $this->project->tasks();
        if ($startDate) {
            $query->where('created_at', '>=', $startDate);
        }

        $totalTasks = $query->count();
        $completedTasks = $query->whereNotNull('completed_at')->count();
        $activeTasks = $query->whereNull('completed_at')->count();
        $overdueTasks = $query->whereNull('completed_at')
            ->whereNotNull('due_date')
            ->where('due_date', '<', now())
            ->count();

        $completionRate = $totalTasks > 0 ? round(($completedTasks / $totalTasks) * 100, 1) : 0;

        // Calculate average completion time
        $avgCompletionTime = $this->getAverageCompletionTime($dateRange);

        // Get activity trend (last 7 days)
        $activityTrend = $this->getActivityTrend($dateRange);

        return [
            'total_tasks' => $totalTasks,
            'completed_tasks' => $completedTasks,
            'active_tasks' => $activeTasks,
            'overdue_tasks' => $overdueTasks,
            'completion_rate' => $completionRate,
            'avg_completion_time' => $avgCompletionTime,
            'activity_trend' => $activityTrend,
        ];
    }

    public function getTaskCompletionData(string $dateRange): array
    {
        $startDate = $this->getStartDate($dateRange);
        
        // Get completion data by day
        $query = $this->project->tasks()->whereNotNull('completed_at');
        
        if ($startDate) {
            $query->where('completed_at', '>=', $startDate);
        }

        $completionsByDay = $query->selectRaw('DATE(completed_at) as date, COUNT(*) as count')
            ->groupBy('date')
            ->orderBy('date')
            ->get()
            ->pluck('count', 'date')
            ->toArray();

        // Get completion data by status
        $completionsByStatus = $this->project->tasks()
            ->whereNotNull('completed_at')
            ->when($startDate, fn($q) => $q->where('completed_at', '>=', $startDate))
            ->with('taskStatus')
            ->get()
            ->groupBy('taskStatus.name')
            ->map(fn($tasks) => $tasks->count())
            ->toArray();

        // Get completion data by priority
        $completionsByPriority = $this->project->tasks()
            ->whereNotNull('completed_at')
            ->when($startDate, fn($q) => $q->where('completed_at', '>=', $startDate))
            ->get()
            ->groupBy('priority')
            ->map(fn($tasks) => $tasks->count())
            ->toArray();

        return [
            'daily_completions' => $completionsByDay,
            'completions_by_status' => $completionsByStatus,
            'completions_by_priority' => $completionsByPriority,
        ];
    }

    public function getUserPerformanceData(string $dateRange, ?string $userId = null): array
    {
        $startDate = $this->getStartDate($dateRange);
        
        $query = $this->project->tasks()->whereNotNull('assigned_to');
        
        if ($startDate) {
            $query->where('created_at', '>=', $startDate);
        }

        if ($userId) {
            $query->where('assigned_to', $userId);
        }

        $users = $query->with(['assignedUser'])
            ->get()
            ->groupBy('assigned_to');

        $performanceData = [];

        foreach ($users as $userTasks) {
            $user = $userTasks->first()->assignedUser;
            if (!$user) continue;

            $totalTasks = $userTasks->count();
            $completedTasks = $userTasks->where('completed_at', '!=', null)->count();
            $activeTasks = $totalTasks - $completedTasks;
            
            $totalTime = 0;
            $completedTasksWithTime = 0;
            
            foreach ($userTasks->where('completed_at', '!=', null) as $task) {
                if ($task->created_at && $task->completed_at) {
                    $totalTime += $task->created_at->diffInHours($task->completed_at);
                    $completedTasksWithTime++;
                }
            }

            $avgCompletionTime = $completedTasksWithTime > 0 ? 
                round($totalTime / $completedTasksWithTime, 1) : 0;

            $completionRate = $totalTasks > 0 ? 
                round(($completedTasks / $totalTasks) * 100, 1) : 0;

            $performanceData[] = [
                'user' => $user,
                'total_tasks' => $totalTasks,
                'completed_tasks' => $completedTasks,
                'active_tasks' => $activeTasks,
                'completion_rate' => $completionRate,
                'avg_completion_time' => $avgCompletionTime,
            ];
        }

        // Sort by completion rate
        usort($performanceData, fn($a, $b) => $b['completion_rate'] <=> $a['completion_rate']);

        return $performanceData;
    }

    public function getProductivityTrends(string $dateRange): array
    {
        $startDate = $this->getStartDate($dateRange);
        $endDate = now();
        
        if (!$startDate) {
            $startDate = Carbon::now()->subDays(30);
        }

        // Generate date range
        $period = Carbon::parse($startDate)->daysUntil($endDate);
        
        $trends = [];
        
        foreach ($period as $date) {
            $dayStart = $date->startOfDay();
            $dayEnd = $date->copy()->endOfDay();
            
            $tasksCreated = $this->project->tasks()
                ->whereBetween('created_at', [$dayStart, $dayEnd])
                ->count();
                
            $tasksCompleted = $this->project->tasks()
                ->whereBetween('completed_at', [$dayStart, $dayEnd])
                ->count();
                
            $commentsAdded = TaskComment::whereHas('task', function($query) {
                    $query->where('project_id', $this->project->id);
                })
                ->whereBetween('created_at', [$dayStart, $dayEnd])
                ->count();

            $trends[] = [
                'date' => $date->format('Y-m-d'),
                'tasks_created' => $tasksCreated,
                'tasks_completed' => $tasksCompleted,
                'comments_added' => $commentsAdded,
                'productivity_score' => ($tasksCompleted * 2) + $commentsAdded,
            ];
        }

        return $trends;
    }

    private function getStartDate(string $dateRange): ?Carbon
    {
        if ($dateRange === 'all') {
            return null;
        }

        return Carbon::now()->subDays((int)$dateRange);
    }

    private function getAverageCompletionTime(string $dateRange): float
    {
        $startDate = $this->getStartDate($dateRange);
        
        $query = $this->project->tasks()
            ->whereNotNull('completed_at')
            ->whereNotNull('created_at');
            
        if ($startDate) {
            $query->where('completed_at', '>=', $startDate);
        }

        $completedTasks = $query->get();
        
        if ($completedTasks->isEmpty()) {
            return 0;
        }

        $totalHours = 0;
        $validTasks = 0;

        foreach ($completedTasks as $task) {
            $hours = $task->created_at->diffInHours($task->completed_at);
            if ($hours >= 0) {
                $totalHours += $hours;
                $validTasks++;
            }
        }

        return $validTasks > 0 ? round($totalHours / $validTasks, 1) : 0;
    }

    private function getActivityTrend(string $dateRange): array
    {
        $days = $dateRange === 'all' ? 7 : min((int)$dateRange, 7);
        $startDate = Carbon::now()->subDays($days);
        
        $trend = [];
        
        for ($i = $days; $i >= 0; $i--) {
            $date = Carbon::now()->subDays($i);
            $dayStart = $date->startOfDay();
            $dayEnd = $date->copy()->endOfDay();
            
            $activity = $this->project->tasks()
                ->whereBetween('completed_at', [$dayStart, $dayEnd])
                ->count();
                
            $trend[] = [
                'date' => $date->format('M d'),
                'count' => $activity,
            ];
        }

        return $trend;
    }
}