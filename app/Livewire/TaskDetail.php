<?php

namespace App\Livewire;

use App\Models\Task;
use App\Models\TaskComment;
use App\Models\User;
use App\Services\NotificationService;
use Livewire\Component;
use Livewire\Attributes\Validate;
use Livewire\Attributes\On;

class TaskDetail extends Component
{
    public $task;
    public $showModal = false;
    
    #[Validate('required|min:1|max:1000')]
    public $newComment = '';

    public function mount($taskId = null)
    {
        if ($taskId) {
            $this->task = Task::with(['comments.user', 'assignedUser', 'createdBy', 'project', 'taskStatus'])
                ->findOrFail($taskId);
        }
    }

    public function openModal($taskId)
    {
        $this->task = Task::with(['comments.user', 'assignedUser', 'createdBy', 'project', 'taskStatus'])
            ->findOrFail($taskId);
        $this->showModal = true;
    }

    public function closeModal()
    {
        $this->showModal = false;
        $this->reset(['newComment']);
    }

    public function addComment()
    {
        $this->validate();

        // Extract mentions from comment
        preg_match_all('/@(\w+)/', $this->newComment, $matches);
        $mentions = [];
        
        if (!empty($matches[1])) {
            $usernames = array_unique($matches[1]);
            $users = User::whereIn('name', $usernames)->get();
            
            foreach ($users as $user) {
                $mentions[] = [
                    'user_id' => $user->id,
                    'username' => $user->name
                ];
            }
        }

        $comment = TaskComment::create([
            'content' => $this->newComment,
            'task_id' => $this->task->id,
            'user_id' => auth()->id(),
            'mentions' => !empty($mentions) ? $mentions : null,
        ]);

        // Send notification for new comment
        NotificationService::taskCommented($this->task, auth()->user(), $this->newComment);

        $this->task->load(['comments.user']);
        $this->reset(['newComment']);
        
        $this->dispatch('comment-added');
    }

    public function deleteComment($commentId)
    {
        $comment = TaskComment::where('id', $commentId)
            ->where('user_id', auth()->id())
            ->first();

        if ($comment) {
            $comment->delete();
            $this->task->load(['comments.user']);
        }
    }

    #[On('task-updated')]
    public function refreshTask()
    {
        if ($this->task) {
            $this->task->load(['comments.user', 'assignedUser', 'createdBy', 'project', 'taskStatus']);
        }
    }

    #[On('open-detail-modal')]
    public function handleOpenDetailModal($taskId)
    {
        $this->openModal($taskId);
    }

    public function render()
    {
        return view('livewire.task-detail');
    }
}
