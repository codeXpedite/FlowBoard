<?php

namespace App\Services;

use App\Models\ProjectTemplate;
use App\Models\Project;
use App\Models\TaskStatus;
use App\Models\Task;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class ProjectTemplateService
{
    public function createProjectFromTemplate(ProjectTemplate $template, array $projectData, User $user): Project
    {
        return DB::transaction(function () use ($template, $projectData, $user) {
            // Create the project
            $project = Project::create([
                'name' => $projectData['name'],
                'description' => $projectData['description'] ?? $template->description,
                'color' => $projectData['color'] ?? $template->color,
                'owner_id' => $user->id,
                'template_id' => $template->id,
                'notification_preferences' => $template->default_settings ?? [],
            ]);

            // Create task statuses from template
            $statusMapping = [];
            foreach ($template->getDefaultTaskStatusesAsObjects() as $statusData) {
                $status = TaskStatus::create([
                    'project_id' => $project->id,
                    'name' => $statusData['name'],
                    'color' => $statusData['color'],
                    'order' => $statusData['order'],
                    'description' => $statusData['description'],
                ]);
                $statusMapping[$statusData['name']] = $status;
            }

            // Create default tasks from template
            if (!empty($template->default_tasks)) {
                foreach ($template->getDefaultTasksAsObjects() as $taskData) {
                    $status = $statusMapping[$taskData['status_name']] ?? $statusMapping[array_key_first($statusMapping)];
                    
                    $dueDate = null;
                    if (isset($taskData['due_date_offset']) && is_numeric($taskData['due_date_offset'])) {
                        $dueDate = now()->addDays($taskData['due_date_offset']);
                    }

                    Task::create([
                        'project_id' => $project->id,
                        'title' => $taskData['title'],
                        'description' => $taskData['description'],
                        'priority' => $taskData['priority'],
                        'task_status_id' => $status->id,
                        'created_by' => $user->id,
                        'order' => $taskData['order'],
                        'due_date' => $dueDate,
                    ]);
                }
            }

            // Increment template usage count
            $template->incrementUsage();

            return $project;
        });
    }

    public function getTemplatesByCategory(string $category = null): \Illuminate\Database\Eloquent\Collection
    {
        $query = ProjectTemplate::public();
        
        if ($category) {
            $query->byCategory($category);
        }
        
        return $query->with('creator')->get();
    }

    public function getPopularTemplates(int $limit = 10): \Illuminate\Database\Eloquent\Collection
    {
        return ProjectTemplate::public()
            ->popular()
            ->with('creator')
            ->limit($limit)
            ->get();
    }

    public function getRecentTemplates(int $limit = 10): \Illuminate\Database\Eloquent\Collection
    {
        return ProjectTemplate::public()
            ->recent()
            ->with('creator')
            ->limit($limit)
            ->get();
    }

    public function searchTemplates(string $search): \Illuminate\Database\Eloquent\Collection
    {
        return ProjectTemplate::public()
            ->where(function ($query) use ($search) {
                $query->where('name', 'like', "%{$search}%")
                      ->orWhere('description', 'like', "%{$search}%")
                      ->orWhereJsonContains('tags', $search);
            })
            ->with('creator')
            ->get();
    }

    public function createTemplate(array $templateData, User $creator): ProjectTemplate
    {
        return ProjectTemplate::create([
            'name' => $templateData['name'],
            'description' => $templateData['description'],
            'category' => $templateData['category'],
            'color' => $templateData['color'] ?? '#3B82F6',
            'default_task_statuses' => $templateData['task_statuses'],
            'default_tasks' => $templateData['tasks'] ?? [],
            'default_settings' => $templateData['settings'] ?? [],
            'is_public' => $templateData['is_public'] ?? false,
            'created_by' => $creator->id,
            'tags' => $templateData['tags'] ?? [],
        ]);
    }

    public function createTemplateFromProject(Project $project, array $templateData, User $creator): ProjectTemplate
    {
        // Get task statuses from project
        $taskStatuses = $project->taskStatuses()
            ->orderBy('order')
            ->get()
            ->map(function ($status) {
                return [
                    'name' => $status->name,
                    'color' => $status->color,
                    'order' => $status->order,
                    'description' => $status->description,
                ];
            })
            ->toArray();

        // Get sample tasks from project (optional)
        $tasks = [];
        if ($templateData['include_tasks'] ?? false) {
            $tasks = $project->tasks()
                ->with('taskStatus')
                ->orderBy('order')
                ->limit(10) // Limit to avoid too many tasks
                ->get()
                ->map(function ($task) {
                    return [
                        'title' => $task->title,
                        'description' => $task->description,
                        'priority' => $task->priority,
                        'status_name' => $task->taskStatus->name,
                        'order' => $task->order,
                    ];
                })
                ->toArray();
        }

        return $this->createTemplate([
            'name' => $templateData['name'],
            'description' => $templateData['description'],
            'category' => $templateData['category'],
            'color' => $project->color,
            'task_statuses' => $taskStatuses,
            'tasks' => $tasks,
            'settings' => $project->notification_preferences ?? [],
            'is_public' => $templateData['is_public'] ?? false,
            'tags' => $templateData['tags'] ?? [],
        ], $creator);
    }

    public function initializeDefaultTemplates(): void
    {
        $defaultTemplates = ProjectTemplate::getDefaultTemplates();
        
        foreach ($defaultTemplates as $templateData) {
            // Check if template already exists
            $exists = ProjectTemplate::where('name', $templateData['name'])
                ->where('category', $templateData['category'])
                ->exists();
                
            if (!$exists) {
                ProjectTemplate::create($templateData);
            }
        }
    }

    public function getTemplateUsageStats(ProjectTemplate $template): array
    {
        $projects = Project::where('template_id', $template->id)->get();
        
        return [
            'total_usage' => $template->usage_count,
            'projects_created' => $projects->count(),
            'recent_usage' => $projects->where('created_at', '>=', now()->subDays(30))->count(),
            'avg_project_tasks' => $projects->avg(function ($project) {
                return $project->tasks()->count();
            }),
            'completion_rate' => $this->calculateTemplateCompletionRate($projects),
        ];
    }

    private function calculateTemplateCompletionRate($projects): float
    {
        if ($projects->isEmpty()) {
            return 0;
        }

        $totalTasks = 0;
        $completedTasks = 0;

        foreach ($projects as $project) {
            $projectTasks = $project->tasks()->count();
            $projectCompleted = $project->tasks()->whereNotNull('completed_at')->count();
            
            $totalTasks += $projectTasks;
            $completedTasks += $projectCompleted;
        }

        return $totalTasks > 0 ? round(($completedTasks / $totalTasks) * 100, 1) : 0;
    }

    public function duplicateTemplate(ProjectTemplate $template, User $creator, array $customizations = []): ProjectTemplate
    {
        $data = [
            'name' => $customizations['name'] ?? $template->name . ' (Copy)',
            'description' => $customizations['description'] ?? $template->description,
            'category' => $customizations['category'] ?? $template->category,
            'color' => $customizations['color'] ?? $template->color,
            'task_statuses' => $template->default_task_statuses,
            'tasks' => $template->default_tasks,
            'settings' => $template->default_settings,
            'is_public' => $customizations['is_public'] ?? false,
            'tags' => array_merge($template->tags ?? [], $customizations['tags'] ?? []),
        ];

        return $this->createTemplate($data, $creator);
    }
}