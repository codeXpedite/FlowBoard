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

class TaskStatusChangedMail extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    public $task;
    public $oldStatus;
    public $newStatus;
    public $changedBy;
    public $recipient;

    /**
     * Create a new message instance.
     */
    public function __construct(Task $task, string $oldStatus, string $newStatus, User $changedBy, User $recipient)
    {
        $this->task = $task;
        $this->oldStatus = $oldStatus;
        $this->newStatus = $newStatus;
        $this->changedBy = $changedBy;
        $this->recipient = $recipient;
    }

    /**
     * Get the message envelope.
     */
    public function envelope(): Envelope
    {
        return new Envelope(
            subject: "'{$this->task->title}' görevinin durumu değişti",
        );
    }

    /**
     * Get the message content definition.
     */
    public function content(): Content
    {
        return new Content(
            view: 'emails.task-status-changed',
            with: [
                'task' => $this->task,
                'oldStatus' => $this->oldStatus,
                'newStatus' => $this->newStatus,
                'changedBy' => $this->changedBy,
                'recipient' => $this->recipient,
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
