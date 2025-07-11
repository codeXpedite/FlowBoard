<?php

namespace App\Mail;

use App\Models\Task;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class TaskAssignedMail extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    public $task;
    public $assignedUser;
    public $assignedBy;

    /**
     * Create a new message instance.
     */
    public function __construct(Task $task, User $assignedUser, User $assignedBy)
    {
        $this->task = $task;
        $this->assignedUser = $assignedUser;
        $this->assignedBy = $assignedBy;
    }

    /**
     * Get the message envelope.
     */
    public function envelope(): Envelope
    {
        return new Envelope(
            subject: "Size '{$this->task->title}' görevi atandı",
        );
    }

    /**
     * Get the message content definition.
     */
    public function content(): Content
    {
        return new Content(
            view: 'emails.task-assigned',
            with: [
                'task' => $this->task,
                'assignedUser' => $this->assignedUser,
                'assignedBy' => $this->assignedBy,
                'projectUrl' => url("/projects/{$this->task->project->id}"),
            ],
        );
    }

    /**
     * Get the attachments for the message.
     *
     * @return array<int, \Illuminate\Mail\Mailables\Attachment>
     */
    public function attachments(): array
    {
        return [];
    }
}
