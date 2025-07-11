<?php

namespace App\Livewire;

use App\Models\Task;
use Livewire\Component;

class SubtaskManager extends Component
{
    public Task $task;
    public bool $showForm = false;
    public string $newSubtaskTitle = '';
    public string $newSubtaskDescription = '';
    public string $newSubtaskPriority = 'medium';

    public function mount(Task $task)
    {
        $this->task = $task;
    }

    public function showAddForm()
    {
        if (!$this->task->canHaveSubtasks()) {
            session()->flash('error', 'Bu görev maksimum alt görev derinliğine ulaştı.');
            return;
        }
        
        $this->showForm = true;
        $this->reset(['newSubtaskTitle', 'newSubtaskDescription', 'newSubtaskPriority']);
    }

    public function hideForm()
    {
        $this->showForm = false;
    }

    public function createSubtask()
    {
        $this->validate([
            'newSubtaskTitle' => 'required|string|max:255',
            'newSubtaskDescription' => 'nullable|string',
            'newSubtaskPriority' => 'required|in:low,medium,high,urgent',
        ]);

        if (!$this->task->canHaveSubtasks()) {
            session()->flash('error', 'Bu görev maksimum alt görev derinliğine ulaştı.');
            return;
        }

        $subtask = $this->task->addSubtask([
            'title' => $this->newSubtaskTitle,
            'description' => $this->newSubtaskDescription,
            'priority' => $this->newSubtaskPriority,
            'task_status_id' => $this->task->project->taskStatuses()->first()->id,
            'created_by' => auth()->id(),
        ]);

        $this->task->refresh();
        $this->hideForm();
        
        session()->flash('success', 'Alt görev başarıyla oluşturuldu.');
        $this->dispatch('subtask-created', $subtask->id);
    }

    public function toggleSubtaskCompletion(Task $subtask)
    {
        if ($subtask->parent_task_id !== $this->task->id) {
            return;
        }

        if ($subtask->is_completed) {
            $subtask->markIncomplete();
        } else {
            $subtask->markCompleted();
        }

        $this->task->refresh();
        $this->dispatch('subtask-updated', $subtask->id);
    }

    public function deleteSubtask(Task $subtask)
    {
        if ($subtask->parent_task_id !== $this->task->id) {
            return;
        }

        $subtask->delete();
        $this->task->refresh();
        
        session()->flash('success', 'Alt görev silindi.');
        $this->dispatch('subtask-deleted', $subtask->id);
    }

    public function moveSubtaskUp(Task $subtask)
    {
        if ($subtask->parent_task_id !== $this->task->id || $subtask->subtask_order <= 0) {
            return;
        }

        $this->task->moveSubtask($subtask, $subtask->subtask_order - 1);
        $this->task->refresh();
    }

    public function moveSubtaskDown(Task $subtask)
    {
        $maxOrder = $this->task->subtasks()->count() - 1;
        
        if ($subtask->parent_task_id !== $this->task->id || $subtask->subtask_order >= $maxOrder) {
            return;
        }

        $this->task->moveSubtask($subtask, $subtask->subtask_order + 1);
        $this->task->refresh();
    }

    public function render()
    {
        return view('livewire.subtask-manager', [
            'subtasks' => $this->task->subtasks()->orderBy('subtask_order')->get(),
            'completionPercentage' => $this->task->getSubtaskCompletionPercentage(),
        ]);
    }
}