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

class TaskCommentedMail extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    public $task;
    public $commenter;
    public $comment;
    public $recipient;

    /**
     * Create a new message instance.
     */
    public function __construct(Task $task, User $commenter, string $comment, User $recipient)
    {
        $this->task = $task;
        $this->commenter = $commenter;
        $this->comment = $comment;
        $this->recipient = $recipient;
    }

    /**
     * Get the message envelope.
     */
    public function envelope(): Envelope
    {
        return new Envelope(
            subject: "'{$this->task->title}' görevine yeni yorum",
        );
    }

    /**
     * Get the message content definition.
     */
    public function content(): Content
    {
        return new Content(
            view: 'emails.task-commented',
            with: [
                'task' => $this->task,
                'commenter' => $this->commenter,
                'comment' => $this->comment,
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
