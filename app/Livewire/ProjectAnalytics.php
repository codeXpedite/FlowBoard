<?php

namespace App\Livewire;

use App\Models\Project;
use App\Models\Task;
use App\Models\User;
use App\Models\TaskComment;
use App\Services\AnalyticsService;
use Livewire\Component;
use Illuminate\Support\Facades\Gate;
use Carbon\Carbon;

class ProjectAnalytics extends Component
{
    public Project $project;
    public $dateRange = '30'; // days
    public $selectedMetric = 'overview';
    public $selectedUser = '';
    public $showAdvancedFilters = false;

    protected $queryString = [
        'dateRange' => ['except' => '30'],
        'selectedMetric' => ['except' => 'overview'],
        'selectedUser' => ['except' => ''],
    ];

    public function mount($project)
    {
        $this->project = Project::where('id', $project)
            ->where('owner_id', auth()->id())
            ->firstOrFail();
    }

    public function toggleAdvancedFilters()
    {
        $this->showAdvancedFilters = !$this->showAdvancedFilters;
    }

    public function getOverviewMetrics()
    {
        $analyticsService = new AnalyticsService($this->project);
        return $analyticsService->getOverviewMetrics($this->dateRange);
    }

    public function getTaskCompletionData()
    {
        $analyticsService = new AnalyticsService($this->project);
        return $analyticsService->getTaskCompletionData($this->dateRange);
    }

    public function getUserPerformanceData()
    {
        $analyticsService = new AnalyticsService($this->project);
        return $analyticsService->getUserPerformanceData($this->dateRange, $this->selectedUser);
    }

    public function getProductivityTrends()
    {
        $analyticsService = new AnalyticsService($this->project);
        return $analyticsService->getProductivityTrends($this->dateRange);
    }

    public function getTaskStatusDistribution()
    {
        $query = $this->project->tasks();
        
        if ($this->dateRange !== 'all') {
            $startDate = Carbon::now()->subDays((int)$this->dateRange);
            $query->where('created_at', '>=', $startDate);
        }

        return $query->with('taskStatus')
            ->get()
            ->groupBy('taskStatus.name')
            ->map(function ($tasks) {
                return $tasks->count();
            })
            ->toArray();
    }

    public function getPriorityDistribution()
    {
        $query = $this->project->tasks();
        
        if ($this->dateRange !== 'all') {
            $startDate = Carbon::now()->subDays((int)$this->dateRange);
            $query->where('created_at', '>=', $startDate);
        }

        $priorityLabels = [
            'high' => 'Yüksek',
            'medium' => 'Orta', 
            'low' => 'Düşük',
        ];

        return $query->get()
            ->groupBy('priority')
            ->map(function ($tasks, $priority) use ($priorityLabels) {
                return [
                    'label' => $priorityLabels[$priority] ?? ucfirst($priority),
                    'count' => $tasks->count(),
                    'color' => $this->getPriorityColor($priority),
                ];
            })
            ->toArray();
    }

    public function getTopPerformers()
    {
        $startDate = $this->dateRange !== 'all' 
            ? Carbon::now()->subDays((int)$this->dateRange)
            : null;

        $query = Task::where('project_id', $this->project->id)
            ->whereNotNull('assigned_to')
            ->whereNotNull('completed_at');

        if ($startDate) {
            $query->where('completed_at', '>=', $startDate);
        }

        $completedTasks = $query->with(['assignedUser'])
            ->get()
            ->groupBy('assigned_to');

        $performers = [];
        foreach ($completedTasks as $userId => $tasks) {
            $user = $tasks->first()->assignedUser;
            if ($user) {
                $totalTime = 0;
                $taskCount = $tasks->count();
                
                foreach ($tasks as $task) {
                    if ($task->created_at && $task->completed_at) {
                        $totalTime += $task->created_at->diffInHours($task->completed_at);
                    }
                }

                $performers[] = [
                    'user' => $user,
                    'completed_tasks' => $taskCount,
                    'avg_completion_time' => $taskCount > 0 ? round($totalTime / $taskCount, 1) : 0,
                    'total_time' => $totalTime,
                ];
            }
        }

        return collect($performers)
            ->sortByDesc('completed_tasks')
            ->take(5)
            ->values()
            ->toArray();
    }

    public function getRecentActivity()
    {
        $startDate = $this->dateRange !== 'all' 
            ? Carbon::now()->subDays((int)$this->dateRange)
            : Carbon::now()->subDays(30);

        $activities = collect();

        // Task completions
        $completedTasks = Task::where('project_id', $this->project->id)
            ->whereNotNull('completed_at')
            ->where('completed_at', '>=', $startDate)
            ->with(['assignedUser', 'taskStatus'])
            ->orderBy('completed_at', 'desc')
            ->take(10)
            ->get();

        foreach ($completedTasks as $task) {
            $activities->push([
                'type' => 'task_completed',
                'title' => "Task tamamlandı: {$task->title}",
                'user' => $task->assignedUser?->name ?? 'Bilinmeyen',
                'date' => $task->completed_at,
                'icon' => 'check-circle',
                'color' => 'green',
            ]);
        }

        // Recent comments
        $recentComments = TaskComment::whereHas('task', function($query) {
                $query->where('project_id', $this->project->id);
            })
            ->where('created_at', '>=', $startDate)
            ->with(['user', 'task'])
            ->orderBy('created_at', 'desc')
            ->take(10)
            ->get();

        foreach ($recentComments as $comment) {
            $activities->push([
                'type' => 'comment_added',
                'title' => "Yorum eklendi: {$comment->task->title}",
                'user' => $comment->user->name,
                'date' => $comment->created_at,
                'icon' => 'chat',
                'color' => 'blue',
            ]);
        }

        return $activities->sortByDesc('date')->take(15)->values()->toArray();
    }

    public function getTimeToCompletion()
    {
        $query = Task::where('project_id', $this->project->id)
            ->whereNotNull('completed_at')
            ->whereNotNull('created_at');

        if ($this->dateRange !== 'all') {
            $startDate = Carbon::now()->subDays((int)$this->dateRange);
            $query->where('completed_at', '>=', $startDate);
        }

        $completedTasks = $query->get();
        
        if ($completedTasks->isEmpty()) {
            return null;
        }

        $totalHours = 0;
        $validTasks = 0;

        foreach ($completedTasks as $task) {
            $hours = $task->created_at->diffInHours($task->completed_at);
            if ($hours >= 0) { // Ensure positive values
                $totalHours += $hours;
                $validTasks++;
            }
        }

        return [
            'average_hours' => $validTasks > 0 ? round($totalHours / $validTasks, 1) : 0,
            'total_completed' => $validTasks,
            'fastest_completion' => $completedTasks->min(function($task) {
                return $task->created_at->diffInHours($task->completed_at);
            }),
            'slowest_completion' => $completedTasks->max(function($task) {
                return $task->created_at->diffInHours($task->completed_at);
            }),
        ];
    }

    private function getPriorityColor($priority)
    {
        return match($priority) {
            'high' => '#EF4444',
            'medium' => '#F59E0B',
            'low' => '#10B981',
            default => '#6B7280'
        };
    }

    public function exportAnalytics($format = 'pdf')
    {
        $exportService = new \App\Services\ExportService($this->project);
        
        try {
            $analyticsData = [
                'overviewMetrics' => $this->getOverviewMetrics(),
                'taskStatusDistribution' => $this->getTaskStatusDistribution(),
                'priorityDistribution' => $this->getPriorityDistribution(),
                'topPerformers' => $this->getTopPerformers(),
                'recentActivity' => $this->getRecentActivity(),
                'timeToCompletion' => $this->getTimeToCompletion(),
            ];
            
            if ($format === 'pdf') {
                return $exportService->exportAnalyticsToPdf($analyticsData);
            }
        } catch (\Exception $e) {
            session()->flash('error', 'Export hatası: ' . $e->getMessage());
        }
    }

    public function render()
    {
        $overviewMetrics = $this->getOverviewMetrics();
        $taskStatusDistribution = $this->getTaskStatusDistribution();
        $priorityDistribution = $this->getPriorityDistribution();
        $topPerformers = $this->getTopPerformers();
        $recentActivity = $this->getRecentActivity();
        $timeToCompletion = $this->getTimeToCompletion();
        
        $projectUsers = $this->project->tasks()
            ->whereNotNull('assigned_to')
            ->with('assignedUser')
            ->get()
            ->pluck('assignedUser')
            ->unique('id')
            ->filter()
            ->sortBy('name');

        $data = compact(
            'overviewMetrics',
            'taskStatusDistribution', 
            'priorityDistribution',
            'topPerformers',
            'recentActivity',
            'timeToCompletion',
            'projectUsers'
        );

        // Add specific metric data based on selection
        if ($this->selectedMetric === 'tasks') {
            $data['taskCompletionData'] = $this->getTaskCompletionData();
        } elseif ($this->selectedMetric === 'users') {
            $data['userPerformanceData'] = $this->getUserPerformanceData();
        } elseif ($this->selectedMetric === 'trends') {
            $data['productivityTrends'] = $this->getProductivityTrends();
        }

        return view('livewire.project-analytics', $data)
            ->layout('layouts.app');
    }
}