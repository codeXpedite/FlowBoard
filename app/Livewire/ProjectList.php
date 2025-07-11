<?php

namespace App\Livewire;

use App\Models\Project;
use App\Models\TaskStatus;
use Livewire\Component;
use Livewire\Attributes\Validate;

class ProjectList extends Component
{
    #[Validate('required|min:3|max:255')]
    public $name = '';
    
    #[Validate('nullable|max:1000')]
    public $description = '';
    
    #[Validate('required|regex:/^#[0-9A-Fa-f]{6}$/')]
    public $color = '#3B82F6';
    
    public $showCreateForm = false;

    public function createProject()
    {
        // Check if user has permission to create projects
        if (!auth()->user()->can('create projects')) {
            session()->flash('error', 'Proje oluşturma yetkiniz bulunmamaktadır.');
            return;
        }

        $this->validate();

        $project = Project::create([
            'name' => $this->name,
            'description' => $this->description,
            'color' => $this->color,
            'owner_id' => auth()->id(),
            'status' => 'active',
        ]);

        // Create default task statuses for the new project
        foreach (TaskStatus::getDefaultStatuses() as $status) {
            $project->taskStatuses()->create([
                'name' => $status['name'],
                'slug' => $status['slug'],
                'color' => $status['color'],
                'sort_order' => $status['sort_order'],
            ]);
        }

        $this->reset(['name', 'description', 'color', 'showCreateForm']);
        $this->dispatch('project-created');
    }

    public function deleteProject($projectId)
    {
        $project = Project::where('id', $projectId)->where('owner_id', auth()->id())->first();
        
        if ($project) {
            $project->delete();
            $this->dispatch('project-deleted');
        }
    }

    public function toggleCreateForm()
    {
        $this->showCreateForm = !$this->showCreateForm;
        $this->reset(['name', 'description', 'color']);
    }

    public function render()
    {
        $projects = Project::where('owner_id', auth()->id())
            ->withCount(['tasks', 'activeTasks', 'completedTasks'])
            ->with(['tasks' => function($query) {
                $query->select('id', 'project_id', 'priority', 'due_date', 'completed_at', 'created_at')
                      ->orderBy('created_at', 'desc')
                      ->limit(3);
            }])
            ->orderBy('created_at', 'desc')
            ->get();

        // Calculate overall statistics
        $totalProjects = $projects->count();
        $activeProjects = $projects->where('status', 'active')->count();
        $totalTasks = $projects->sum('tasks_count');
        $completedTasks = $projects->sum('completed_tasks_count');
        $overdueTasks = $projects->sum(function($project) {
            return $project->tasks->filter(function($task) {
                return $task->due_date && $task->due_date->isPast() && !$task->completed_at;
            })->count();
        });

        $stats = [
            'total_projects' => $totalProjects,
            'active_projects' => $activeProjects,
            'total_tasks' => $totalTasks,
            'completed_tasks' => $completedTasks,
            'overdue_tasks' => $overdueTasks,
            'completion_rate' => $totalTasks > 0 ? round(($completedTasks / $totalTasks) * 100) : 0
        ];

        return view('livewire.project-list', compact('projects', 'stats'));
    }
}
