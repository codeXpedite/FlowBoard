<?php

namespace App\Livewire;

use App\Models\Project;
use App\Models\Task;
use App\Models\TaskStatus;
use App\Services\NotificationService;
use App\Services\CacheService;
use Livewire\Component;
use Livewire\Attributes\On;

class KanbanBoard extends Component
{
    public $project;
    public $showTaskForm = false;
    public $editingTask = null;
    
    // Filtering and search properties
    public $search = '';
    public $filterPriority = '';
    public $filterAssignedTo = '';
    public $filterStatus = '';
    public $filterTag = '';
    public $filterDateFrom = '';
    public $filterDateTo = '';
    public $filterCompletionStatus = '';
    public $filterGitHubStatus = '';
    public $sortBy = 'created_at';
    public $sortDirection = 'desc';
    public $showFilters = false;
    public $showAdvancedFilters = false;

    public function mount($project)
    {
        $cacheService = app(CacheService::class);
        $this->project = $cacheService->getProject($project, auth()->id());
        
        if (!$this->project) {
            abort(404);
        }
    }

    #[On('task-created')]
    public function taskCreated()
    {
        $this->refreshBoard();
    }

    #[On('task-updated')]
    public function taskUpdated()
    {
        $this->refreshBoard();
        $this->editingTask = null;
        $this->showTaskForm = false;
    }

    #[On('open-task-detail')]
    public function openTaskDetail($taskId)
    {
        $this->dispatch('open-detail-modal', taskId: $taskId);
    }

    public function refreshBoard()
    {
        $cacheService = app(CacheService::class);
        $cacheService->invalidateProject($this->project->id, auth()->id());
        $this->project = $cacheService->getProject($this->project->id, auth()->id());
    }

    public function deleteTask($taskId)
    {
        // Check permissions
        if (!auth()->user()->can('delete tasks')) {
            session()->flash('error', 'Görev silme yetkiniz bulunmamaktadır.');
            return;
        }

        $task = Task::where('id', $taskId)
            ->where('project_id', $this->project->id)
            ->first();

        if ($task) {
            $cacheService = app(CacheService::class);
            $cacheService->invalidateTask($task->id, $task->project_id, auth()->id());
            $task->delete();
            $this->refreshBoard();
        }
    }

    public function editTask($taskId)
    {
        // Check permissions
        if (!auth()->user()->can('edit tasks')) {
            session()->flash('error', 'Görev düzenleme yetkiniz bulunmamaktadır.');
            return;
        }

        $this->editingTask = Task::find($taskId);
        $this->showTaskForm = true;
    }

    public function moveTask($taskId, $newStatusId, $newPosition)
    {
        $task = Task::find($taskId);
        if (!$task || $task->project_id !== $this->project->id) {
            return;
        }

        // Update the task status
        $oldStatusId = $task->task_status_id;
        $oldStatus = TaskStatus::find($oldStatusId);
        $newStatus = TaskStatus::find($newStatusId);
        
        $task->task_status_id = $newStatusId;

        // Reorder tasks in the new status
        $tasksInNewStatus = Task::where('task_status_id', $newStatusId)
            ->where('id', '!=', $taskId)
            ->orderBy('sort_order')
            ->get();

        $newSortOrder = 1;
        foreach ($tasksInNewStatus as $index => $statusTask) {
            if ($index == $newPosition) {
                $task->sort_order = $newSortOrder;
                $newSortOrder++;
            }
            $statusTask->sort_order = $newSortOrder;
            $statusTask->save();
            $newSortOrder++;
        }

        // If task is placed at the end
        if ($newPosition >= $tasksInNewStatus->count()) {
            $task->sort_order = $newSortOrder;
        }

        // Handle completion status
        if ($newStatus->slug === 'done') {
            $task->completed_at = now();
        } else {
            $task->completed_at = null;
        }

        $task->save();

        // Send notification for status change
        if ($oldStatusId !== $newStatusId) {
            NotificationService::taskStatusChanged($task, $oldStatus->name, $newStatus->name, auth()->user());
        }

        // Reorder remaining tasks in old status
        if ($oldStatusId !== $newStatusId) {
            $remainingTasks = Task::where('task_status_id', $oldStatusId)
                ->orderBy('sort_order')
                ->get();

            foreach ($remainingTasks as $index => $remainingTask) {
                $remainingTask->sort_order = $index + 1;
                $remainingTask->save();
            }
        }

        $this->refreshBoard();
    }

    public function openTaskForm()
    {
        // Check permissions
        if (!auth()->user()->can('create tasks')) {
            session()->flash('error', 'Görev oluşturma yetkiniz bulunmamaktadır.');
            return;
        }

        $this->showTaskForm = true;
        $this->editingTask = null;
    }

    public function closeTaskForm()
    {
        $this->showTaskForm = false;
        $this->editingTask = null;
    }

    public function toggleFilters()
    {
        $this->showFilters = !$this->showFilters;
    }

    public function clearFilters()
    {
        $this->search = '';
        $this->filterPriority = '';
        $this->filterAssignedTo = '';
        $this->filterStatus = '';
        $this->filterTag = '';
        $this->filterDateFrom = '';
        $this->filterDateTo = '';
        $this->filterCompletionStatus = '';
        $this->filterGitHubStatus = '';
        $this->sortBy = 'created_at';
        $this->sortDirection = 'desc';
    }

    public function toggleAdvancedFilters()
    {
        $this->showAdvancedFilters = !$this->showAdvancedFilters;
    }

    public function toggleSort($field)
    {
        if ($this->sortBy === $field) {
            $this->sortDirection = $this->sortDirection === 'asc' ? 'desc' : 'asc';
        } else {
            $this->sortBy = $field;
            $this->sortDirection = 'asc';
        }
    }

    public function searchTasks($searchTerm)
    {
        return Task::where('project_id', $this->project->id)
            ->where(function($q) use ($searchTerm) {
                $q->where('title', 'like', '%' . $searchTerm . '%')
                  ->orWhere('description', 'like', '%' . $searchTerm . '%')
                  ->orWhereHas('assignedUser', function($userQuery) use ($searchTerm) {
                      $userQuery->where('name', 'like', '%' . $searchTerm . '%');
                  })
                  ->orWhereHas('tags', function($tagQuery) use ($searchTerm) {
                      $tagQuery->where('name', 'like', '%' . $searchTerm . '%');
                  });
            })
            ->with(['assignedUser', 'tags', 'taskStatus'])
            ->get()
            ->groupBy('task_status_id');
    }

    public function updatedSearch()
    {
        // This will trigger a re-render when search changes
    }

    public function updatedFilterPriority()
    {
        // This will trigger a re-render when priority filter changes
    }

    public function updatedFilterAssignedTo()
    {
        // This will trigger a re-render when assigned user filter changes
    }

    public function updatedFilterStatus()
    {
        // This will trigger a re-render when status filter changes
    }

    public function updatedFilterTag()
    {
        // This will trigger a re-render when tag filter changes
    }

    public function render()
    {
        $taskStatuses = $this->project->taskStatuses()
            ->with(['tasks' => function($query) {
                $query->with(['assignedUser', 'createdBy', 'tags', 'subtasks'])
                      ->whereNull('parent_task_id');
                
                // Apply search filter
                if ($this->search) {
                    $query->where(function($q) {
                        $q->where('title', 'like', '%' . $this->search . '%')
                          ->orWhere('description', 'like', '%' . $this->search . '%')
                          ->orWhereHas('assignedUser', function($userQuery) {
                              $userQuery->where('name', 'like', '%' . $this->search . '%');
                          })
                          ->orWhereHas('tags', function($tagQuery) {
                              $tagQuery->where('name', 'like', '%' . $this->search . '%');
                          });
                    });
                }
                
                // Apply priority filter
                if ($this->filterPriority) {
                    $query->where('priority', $this->filterPriority);
                }
                
                // Apply assigned user filter
                if ($this->filterAssignedTo) {
                    if ($this->filterAssignedTo === 'unassigned') {
                        $query->whereNull('assigned_to');
                    } elseif ($this->filterAssignedTo === 'me') {
                        $query->where('assigned_to', auth()->id());
                    } else {
                        $query->where('assigned_to', $this->filterAssignedTo);
                    }
                }
                
                // Apply tag filter
                if ($this->filterTag) {
                    $query->whereHas('tags', function($q) {
                        $q->where('tags.id', $this->filterTag);
                    });
                }

                // Apply date range filters
                if ($this->filterDateFrom) {
                    $query->whereDate('created_at', '>=', $this->filterDateFrom);
                }
                if ($this->filterDateTo) {
                    $query->whereDate('created_at', '<=', $this->filterDateTo);
                }

                // Apply completion status filter
                if ($this->filterCompletionStatus) {
                    if ($this->filterCompletionStatus === 'completed') {
                        $query->whereNotNull('completed_at');
                    } elseif ($this->filterCompletionStatus === 'pending') {
                        $query->whereNull('completed_at');
                    } elseif ($this->filterCompletionStatus === 'overdue') {
                        $query->whereNull('completed_at')
                              ->whereDate('due_date', '<', now());
                    }
                }

                // Apply GitHub status filter
                if ($this->filterGitHubStatus) {
                    if ($this->filterGitHubStatus === 'github') {
                        $query->whereNotNull('github_repository_id');
                    } elseif ($this->filterGitHubStatus === 'manual') {
                        $query->whereNull('github_repository_id');
                    }
                }
                
                // Apply sorting
                switch ($this->sortBy) {
                    case 'title':
                        $query->orderBy('title', $this->sortDirection);
                        break;
                    case 'priority':
                        $query->orderByRaw("FIELD(priority, 'urgent', 'high', 'medium', 'low') " . $this->sortDirection);
                        break;
                    case 'due_date':
                        $query->orderBy('due_date', $this->sortDirection);
                        break;
                    case 'assigned_to':
                        $query->leftJoin('users', 'tasks.assigned_to', '=', 'users.id')
                              ->orderBy('users.name', $this->sortDirection)
                              ->select('tasks.*');
                        break;
                    default:
                        $query->orderBy('sort_order');
                }
            }])
            ->when($this->filterStatus, function($query) {
                $query->where('id', $this->filterStatus);
            })
            ->orderBy('sort_order')
            ->get();

        // Get project users for filter dropdown (cached)
        $cacheService = app(CacheService::class);
        $projectUsers = $cacheService->getProjectUsers($this->project->id);

        // Get project tags for filter dropdown (cached)
        $projectTags = $cacheService->getProjectTags($this->project->id);

        return view('livewire.kanban-board', compact('taskStatuses', 'projectUsers', 'projectTags'))
            ->layout('layouts.app');
    }
}
