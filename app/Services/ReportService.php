<?php

namespace App\Services;

use App\Models\Project;
use App\Models\Task;
use App\Models\User;
use App\Models\TaskComment;
use Carbon\Carbon;
use Illuminate\Support\Collection;

class ReportService
{
    protected Project $project;

    public function __construct(Project $project)
    {
        $this->project = $project;
    }

    public function generateProgressReport(string $dateRange = '30'): array
    {
        $startDate = $this->getStartDate($dateRange);
        $endDate = now();

        return [
            'project_info' => $this->getProjectInfo(),
            'summary_metrics' => $this->getSummaryMetrics($startDate),
            'progress_timeline' => $this->getProgressTimeline($startDate, $endDate),
            'team_performance' => $this->getTeamPerformance($startDate),
            'task_breakdown' => $this->getTaskBreakdown($startDate),
            'milestone_progress' => $this->getMilestoneProgress($startDate),
            'bottlenecks' => $this->identifyBottlenecks($startDate),
            'recommendations' => $this->getRecommendations($startDate),
            'generated_at' => now(),
            'report_period' => [
                'start_date' => $startDate,
                'end_date' => $endDate,
                'days' => $startDate ? $startDate->diffInDays($endDate) : null,
            ],
        ];
    }

    private function getProjectInfo(): array
    {
        return [
            'id' => $this->project->id,
            'name' => $this->project->name,
            'description' => $this->project->description,
            'created_at' => $this->project->created_at,
            'owner' => $this->project->owner->name,
            'total_tasks' => $this->project->tasks()->count(),
            'team_size' => $this->project->tasks()
                ->whereNotNull('assigned_to')
                ->distinct('assigned_to')
                ->count(),
        ];
    }

    private function getSummaryMetrics(?Carbon $startDate): array
    {
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

        // Calculate velocity (tasks completed per week)
        $weeksInPeriod = $startDate ? max($startDate->diffInWeeks(now()), 1) : 1;
        $velocity = round($completedTasks / $weeksInPeriod, 1);

        // Calculate average completion time
        $avgCompletionTime = $this->getAverageCompletionTime($startDate);

        return [
            'total_tasks' => $totalTasks,
            'completed_tasks' => $completedTasks,
            'active_tasks' => $activeTasks,
            'overdue_tasks' => $overdueTasks,
            'completion_rate' => $completionRate,
            'velocity' => $velocity,
            'avg_completion_time' => $avgCompletionTime,
        ];
    }

    private function getProgressTimeline(?Carbon $startDate, Carbon $endDate): array
    {
        $timeline = [];
        
        if (!$startDate) {
            $startDate = $this->project->created_at ?? Carbon::now()->subDays(30);
        }

        // Generate weekly progress points
        $current = $startDate->copy()->startOfWeek();
        
        while ($current <= $endDate) {
            $weekEnd = $current->copy()->endOfWeek();
            
            $tasksCreated = $this->project->tasks()
                ->whereBetween('created_at', [$current, $weekEnd])
                ->count();
                
            $tasksCompleted = $this->project->tasks()
                ->whereBetween('completed_at', [$current, $weekEnd])
                ->count();

            $cumulativeCompleted = $this->project->tasks()
                ->where('completed_at', '<=', $weekEnd)
                ->count();

            $timeline[] = [
                'week_start' => $current->format('Y-m-d'),
                'week_end' => $weekEnd->format('Y-m-d'),
                'tasks_created' => $tasksCreated,
                'tasks_completed' => $tasksCompleted,
                'cumulative_completed' => $cumulativeCompleted,
                'net_progress' => $tasksCompleted - $tasksCreated,
            ];
            
            $current->addWeek();
        }

        return $timeline;
    }

    private function getTeamPerformance(?Carbon $startDate): array
    {
        $query = $this->project->tasks()->whereNotNull('assigned_to');
        
        if ($startDate) {
            $query->where('created_at', '>=', $startDate);
        }

        $userTasks = $query->with(['assignedUser'])
            ->get()
            ->groupBy('assigned_to');

        $performance = [];
        
        foreach ($userTasks as $userId => $tasks) {
            $user = $tasks->first()->assignedUser;
            if (!$user) continue;

            $totalTasks = $tasks->count();
            $completedTasks = $tasks->where('completed_at', '!=', null)->count();
            $overdueTasks = $tasks->where('completed_at', null)
                ->where('due_date', '<', now())
                ->count();

            $totalTime = 0;
            $completedTasksWithTime = 0;
            
            foreach ($tasks->where('completed_at', '!=', null) as $task) {
                if ($task->created_at && $task->completed_at) {
                    $totalTime += $task->created_at->diffInHours($task->completed_at);
                    $completedTasksWithTime++;
                }
            }

            $avgCompletionTime = $completedTasksWithTime > 0 ? 
                round($totalTime / $completedTasksWithTime, 1) : 0;

            $completionRate = $totalTasks > 0 ? 
                round(($completedTasks / $totalTasks) * 100, 1) : 0;

            // Calculate productivity score
            $productivityScore = $this->calculateProductivityScore($completedTasks, $avgCompletionTime, $overdueTasks);

            $performance[] = [
                'user' => $user,
                'total_tasks' => $totalTasks,
                'completed_tasks' => $completedTasks,
                'overdue_tasks' => $overdueTasks,
                'completion_rate' => $completionRate,
                'avg_completion_time' => $avgCompletionTime,
                'productivity_score' => $productivityScore,
                'rating' => $this->getPerformanceRating($productivityScore),
            ];
        }

        // Sort by productivity score
        usort($performance, fn($a, $b) => $b['productivity_score'] <=> $a['productivity_score']);

        return $performance;
    }

    private function getTaskBreakdown(?Carbon $startDate): array
    {
        $query = $this->project->tasks();
        if ($startDate) {
            $query->where('created_at', '>=', $startDate);
        }

        $tasks = $query->with(['taskStatus', 'assignedUser'])->get();

        // Status breakdown
        $statusBreakdown = $tasks->groupBy('taskStatus.name')
            ->map(fn($tasks) => $tasks->count())
            ->toArray();

        // Priority breakdown
        $priorityBreakdown = $tasks->groupBy('priority')
            ->map(fn($tasks) => $tasks->count())
            ->toArray();

        // Assignment status
        $assignmentBreakdown = [
            'assigned' => $tasks->whereNotNull('assigned_to')->count(),
            'unassigned' => $tasks->whereNull('assigned_to')->count(),
        ];

        // Due date analysis
        $now = now();
        $dueDateBreakdown = [
            'overdue' => $tasks->where('due_date', '<', $now)->whereNull('completed_at')->count(),
            'due_today' => $tasks->whereDate('due_date', $now->format('Y-m-d'))->whereNull('completed_at')->count(),
            'due_this_week' => $tasks->whereBetween('due_date', [$now, $now->copy()->endOfWeek()])->whereNull('completed_at')->count(),
            'no_due_date' => $tasks->whereNull('due_date')->whereNull('completed_at')->count(),
        ];

        return [
            'status_breakdown' => $statusBreakdown,
            'priority_breakdown' => $priorityBreakdown,
            'assignment_breakdown' => $assignmentBreakdown,
            'due_date_breakdown' => $dueDateBreakdown,
        ];
    }

    private function getMilestoneProgress(?Carbon $startDate): array
    {
        // For now, we'll use task statuses as milestones
        // This can be extended to include actual milestone models later
        
        $taskStatuses = $this->project->tasks()
            ->with('taskStatus')
            ->get()
            ->groupBy('taskStatus.name');

        $milestones = [];
        
        foreach ($taskStatuses as $statusName => $tasks) {
            $totalTasks = $tasks->count();
            $completedTasks = $tasks->where('completed_at', '!=', null)->count();
            $progressPercentage = $totalTasks > 0 ? round(($completedTasks / $totalTasks) * 100, 1) : 0;

            $milestones[] = [
                'name' => $statusName,
                'total_tasks' => $totalTasks,
                'completed_tasks' => $completedTasks,
                'progress_percentage' => $progressPercentage,
                'status' => $this->getMilestoneStatus($progressPercentage),
            ];
        }

        return $milestones;
    }

    private function identifyBottlenecks(?Carbon $startDate): array
    {
        $bottlenecks = [];

        // Identify tasks stuck in specific statuses for too long
        $stuckTasks = $this->project->tasks()
            ->whereNull('completed_at')
            ->where('created_at', '<', now()->subDays(7))
            ->with(['taskStatus', 'assignedUser'])
            ->get();

        $stuckByStatus = $stuckTasks->groupBy('taskStatus.name');
        
        foreach ($stuckByStatus as $status => $tasks) {
            if ($tasks->count() >= 3) { // Consider it a bottleneck if 3+ tasks are stuck
                $bottlenecks[] = [
                    'type' => 'status_bottleneck',
                    'description' => "Cok sayıda görev '{$status}' durumunda bekliyor",
                    'affected_tasks' => $tasks->count(),
                    'severity' => $this->getBottleneckSeverity($tasks->count()),
                ];
            }
        }

        // Identify overloaded team members
        $assignedTasks = $stuckTasks->whereNotNull('assigned_to')
            ->groupBy('assigned_to');

        foreach ($assignedTasks as $userId => $tasks) {
            if ($tasks->count() >= 5) { // Consider overloaded if 5+ active tasks
                $user = $tasks->first()->assignedUser;
                $bottlenecks[] = [
                    'type' => 'workload_bottleneck',
                    'description' => "{$user->name} çok fazla aktif göreve sahip",
                    'affected_tasks' => $tasks->count(),
                    'severity' => $this->getBottleneckSeverity($tasks->count()),
                ];
            }
        }

        // Identify overdue task clusters
        $overdueTasks = $this->project->tasks()
            ->whereNull('completed_at')
            ->whereNotNull('due_date')
            ->where('due_date', '<', now())
            ->count();

        if ($overdueTasks >= 3) {
            $bottlenecks[] = [
                'type' => 'deadline_bottleneck',
                'description' => "Çok sayıda görev tarihi geçmiş ({$overdueTasks} görev)",
                'affected_tasks' => $overdueTasks,
                'severity' => $this->getBottleneckSeverity($overdueTasks),
            ];
        }

        return $bottlenecks;
    }

    private function getRecommendations(?Carbon $startDate): array
    {
        $recommendations = [];
        $metrics = $this->getSummaryMetrics($startDate);
        $bottlenecks = $this->identifyBottlenecks($startDate);

        // Completion rate recommendations
        if ($metrics['completion_rate'] < 60) {
            $recommendations[] = [
                'category' => 'productivity',
                'priority' => 'high',
                'title' => 'Düşük Tamamlanma Oranı',
                'description' => 'Proje tamamlanma oranı %' . $metrics['completion_rate'] . ' olarak düşük. Görev önceliklerini gözden geçirin.',
                'action' => 'Kritik görevleri belirleyin ve ekip kaynaklarını yeniden dağıtın.',
            ];
        }

        // Velocity recommendations
        if ($metrics['velocity'] < 2) {
            $recommendations[] = [
                'category' => 'velocity',
                'priority' => 'medium',
                'title' => 'Düşük Proje Hızı',
                'description' => 'Haftalık ortalama ' . $metrics['velocity'] . ' görev tamamlanıyor.',
                'action' => 'Sprint planlama toplantıları düzenleyin ve görev boyutlarını küçültün.',
            ];
        }

        // Overdue task recommendations
        if ($metrics['overdue_tasks'] > 0) {
            $recommendations[] = [
                'category' => 'deadlines',
                'priority' => 'high',
                'title' => 'Geciken Görevler',
                'description' => $metrics['overdue_tasks'] . ' görev tarihi geçmiş durumda.',
                'action' => 'Geciken görevleri acil olarak gözden geçirin ve yeni tarihler belirleyin.',
            ];
        }

        // Team workload recommendations
        $teamPerformance = $this->getTeamPerformance($startDate);
        $overloadedMembers = array_filter($teamPerformance, fn($member) => $member['total_tasks'] > 10);
        
        if (count($overloadedMembers) > 0) {
            $recommendations[] = [
                'category' => 'workload',
                'priority' => 'medium',
                'title' => 'Ekip Yük Dengesizliği',
                'description' => count($overloadedMembers) . ' ekip üyesi aşırı yüklenmiş durumda.',
                'action' => 'Görev dağılımını yeniden dengeleyin ve yeni kaynak planlaması yapın.',
            ];
        }

        // Add bottleneck-specific recommendations
        foreach ($bottlenecks as $bottleneck) {
            $recommendations[] = [
                'category' => 'bottleneck',
                'priority' => $bottleneck['severity'],
                'title' => 'Darboğaz Tespit Edildi',
                'description' => $bottleneck['description'],
                'action' => $this->getBottleneckAction($bottleneck['type']),
            ];
        }

        return $recommendations;
    }

    private function getStartDate(string $dateRange): ?Carbon
    {
        if ($dateRange === 'all') {
            return null;
        }

        return Carbon::now()->subDays((int)$dateRange);
    }

    private function getAverageCompletionTime(?Carbon $startDate): float
    {
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

    private function calculateProductivityScore(int $completedTasks, float $avgTime, int $overdueTasks): int
    {
        $baseScore = min($completedTasks * 10, 100); // Max 100 points from completed tasks
        $timeBonus = $avgTime > 0 ? min(50 / $avgTime, 20) : 0; // Bonus for faster completion
        $penaltyForOverdue = $overdueTasks * 5; // Penalty for overdue tasks
        
        return max(0, (int)($baseScore + $timeBonus - $penaltyForOverdue));
    }

    private function getPerformanceRating(int $score): string
    {
        if ($score >= 80) return 'Mükemmel';
        if ($score >= 60) return 'İyi';
        if ($score >= 40) return 'Orta';
        if ($score >= 20) return 'Düşük';
        return 'Çok Düşük';
    }

    private function getMilestoneStatus(float $percentage): string
    {
        if ($percentage >= 100) return 'Tamamlandı';
        if ($percentage >= 75) return 'Yaklaşıyor';
        if ($percentage >= 50) return 'Devam Ediyor';
        if ($percentage >= 25) return 'Başladı';
        return 'Beklemede';
    }

    private function getBottleneckSeverity(int $count): string
    {
        if ($count >= 10) return 'high';
        if ($count >= 5) return 'medium';
        return 'low';
    }

    private function getBottleneckAction(string $type): string
    {
        return match($type) {
            'status_bottleneck' => 'Bu durumdaki görevlerin nedenlerini araştırın ve süreç iyileştirmesi yapın.',
            'workload_bottleneck' => 'Aşırı yüklenmiş ekip üyelerinden diğer üyelere görev aktarın.',
            'deadline_bottleneck' => 'Geciken görevleri önceliklendirin ve acil eylem planı oluşturun.',
            default => 'Bu sorunu çözmek için uygun aksiyon alın.',
        };
    }
}