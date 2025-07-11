<?php

namespace App\Livewire;

use App\Models\Project;
use App\Models\Task;
use App\Models\TaskStatus;
use App\Models\User;
use App\Services\NotificationService;
use Livewire\Component;
use Livewire\Attributes\Validate;

class TaskForm extends Component
{
    public $project;
    public $task;
    public $isEditing = false;

    #[Validate('required|min:3|max:255')]
    public $title = '';
    
    #[Validate('nullable|max:2000')]
    public $description = '';
    
    #[Validate('required|in:low,medium,high,urgent')]
    public $priority = 'medium';
    
    #[Validate('nullable|exists:users,id')]
    public $assigned_to = null;
    
    #[Validate('required|exists:task_statuses,id')]
    public $task_status_id;
    
    #[Validate('nullable|date|after:today')]
    public $due_date = '';
    
    #[Validate('nullable|exists:tasks,id')]
    public $parent_task_id = null;

    public $selectedTags = [];
    public $showModal = false;

    public function mount($project = null, $task = null)
    {
        $this->project = $project;
        
        if ($task) {
            $this->task = $task;
            $this->isEditing = true;
            $this->title = $task->title;
            $this->description = $task->description;
            $this->priority = $task->priority;
            $this->assigned_to = $task->assigned_to;
            $this->task_status_id = $task->task_status_id;
            $this->due_date = $task->due_date ? $task->due_date->format('Y-m-d') : '';
            $this->parent_task_id = $task->parent_task_id;
            $this->selectedTags = $task->tags->pluck('id')->toArray();
        } else {
            // Set default status to first status of the project
            $this->task_status_id = $project->taskStatuses()->first()?->id;
        }
    }

    public function save()
    {
        $this->validate();

        $data = [
            'title' => $this->title,
            'description' => $this->description,
            'priority' => $this->priority,
            'assigned_to' => $this->assigned_to,
            'task_status_id' => $this->task_status_id,
            'due_date' => $this->due_date ? $this->due_date : null,
            'parent_task_id' => $this->parent_task_id,
        ];

        if ($this->isEditing) {
            $oldAssignedTo = $this->task->assigned_to;
            $this->task->update($data);
            
            // Check if assignment changed and send notification
            if ($oldAssignedTo !== $this->assigned_to && $this->assigned_to) {
                $assignedUser = User::find($this->assigned_to);
                if ($assignedUser) {
                    NotificationService::taskAssigned($this->task->fresh(), $assignedUser, auth()->user());
                }
            }
            
            // Sync tags
            $this->task->tags()->sync($this->selectedTags);
            
            $this->dispatch('task-updated', $this->task->id);
        } else {
            $data['project_id'] = $this->project->id;
            $data['created_by'] = auth()->id();
            $data['sort_order'] = Task::where('task_status_id', $this->task_status_id)->max('sort_order') + 1;
            
            $task = Task::create($data);
            
            // Send notification if task is assigned to someone
            if ($this->assigned_to) {
                $assignedUser = User::find($this->assigned_to);
                if ($assignedUser) {
                    NotificationService::taskAssigned($task, $assignedUser, auth()->user());
                }
            }
            
            // Sync tags for new task
            $task->tags()->sync($this->selectedTags);
            
            $this->dispatch('task-created', $task->id);
        }

        $this->reset(['title', 'description', 'priority', 'assigned_to', 'due_date', 'parent_task_id', 'selectedTags']);
        $this->showModal = false;
    }

    public function openModal()
    {
        $this->showModal = true;
    }

    public function closeModal()
    {
        $this->showModal = false;
        $this->reset(['title', 'description', 'priority', 'assigned_to', 'due_date', 'parent_task_id', 'selectedTags']);
    }

    public function toggleTag($tagId)
    {
        if (in_array($tagId, $this->selectedTags)) {
            $this->selectedTags = array_diff($this->selectedTags, [$tagId]);
        } else {
            $this->selectedTags[] = $tagId;
        }
    }

    public function render()
    {
        $taskStatuses = $this->project->taskStatuses;
        $projectUsers = $this->project->tasks()
            ->with('assignedUser')
            ->whereNotNull('assigned_to')
            ->get()
            ->pluck('assignedUser')
            ->unique('id')
            ->filter();
        
        // Add current user if not in the list
        if (!$projectUsers->contains('id', auth()->id())) {
            $projectUsers->push(auth()->user());
        }

        $parentTasks = $this->project->tasks()
            ->whereNull('parent_task_id')
            ->when($this->isEditing, fn($q) => $q->where('id', '!=', $this->task->id))
            ->get();

        $projectTags = $this->project->tags;

        return view('livewire.task-form', compact('taskStatuses', 'projectUsers', 'parentTasks', 'projectTags'));
    }
}
