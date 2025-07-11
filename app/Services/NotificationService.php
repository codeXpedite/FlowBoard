<?php

namespace App\Services;

use App\Models\Notification;
use App\Models\User;
use App\Models\Task;
use App\Mail\TaskAssignedMail;
use App\Mail\TaskStatusChangedMail;
use App\Mail\TaskCommentedMail;
use Illuminate\Support\Facades\Mail;

class NotificationService
{
    public static function taskAssigned(Task $task, User $assignedUser, User $assignedBy): void
    {
        if ($assignedUser->id === $assignedBy->id) {
            return; // Don't notify when self-assigning
        }

        // Check user notification preferences
        $preferences = $assignedUser->notification_preferences ?? [];
        if (!($preferences['task_assignments'] ?? true)) {
            return; // User has disabled task assignment notifications
        }

        Notification::create([
            'user_id' => $assignedUser->id,
            'type' => 'task_assigned',
            'data' => [
                'task_id' => $task->id,
                'task_title' => $task->title,
                'project_name' => $task->project->name,
                'assigned_by' => $assignedBy->name,
                'assigned_by_id' => $assignedBy->id,
                'message' => "{$assignedBy->name} size '{$task->title}' görevini atadı",
                'url' => "/projects/{$task->project->id}"
            ]
        ]);

        // Send email notification if enabled
        if ($preferences['email_notifications'] ?? true) {
            Mail::to($assignedUser->email)->send(new TaskAssignedMail($task, $assignedUser, $assignedBy));
        }

        // Send push notification if enabled
        if ($preferences['browser_notifications'] ?? true) {
            $pushService = new \App\Services\PushNotificationService();
            $pushService->sendTaskAssignedNotification($task, $assignedUser, $assignedBy);
        }
    }

    public static function taskStatusChanged(Task $task, string $oldStatus, string $newStatus, User $changedBy): void
    {
        // Notify assigned user if someone else changed the status
        if ($task->assignedUser && $task->assignedUser->id !== $changedBy->id) {
            // Check user notification preferences
            $preferences = $task->assignedUser->notification_preferences ?? [];
            if (!($preferences['task_updates'] ?? false)) {
                return; // User has disabled task update notifications
            }

            Notification::create([
                'user_id' => $task->assignedUser->id,
                'type' => 'task_status_changed',
                'data' => [
                    'task_id' => $task->id,
                    'task_title' => $task->title,
                    'project_name' => $task->project->name,
                    'old_status' => $oldStatus,
                    'new_status' => $newStatus,
                    'changed_by' => $changedBy->name,
                    'changed_by_id' => $changedBy->id,
                    'message' => "{$changedBy->name} '{$task->title}' görevinin durumunu {$oldStatus} -> {$newStatus} olarak değiştirdi",
                    'url' => "/projects/{$task->project->id}"
                ]
            ]);

            // Send email notification if enabled
            if ($preferences['email_notifications'] ?? true) {
                Mail::to($task->assignedUser->email)->send(new TaskStatusChangedMail($task, $oldStatus, $newStatus, $changedBy, $task->assignedUser));
            }
        }

        // Notify project owner
        if ($task->project->owner->id !== $changedBy->id && (!$task->assignedUser || $task->project->owner->id !== $task->assignedUser->id)) {
            $preferences = $task->project->owner->notification_preferences ?? [];
            if ($preferences['project_updates'] ?? true) {
                Notification::create([
                    'user_id' => $task->project->owner->id,
                    'type' => 'task_status_changed',
                    'data' => [
                        'task_id' => $task->id,
                        'task_title' => $task->title,
                        'project_name' => $task->project->name,
                        'old_status' => $oldStatus,
                        'new_status' => $newStatus,
                        'changed_by' => $changedBy->name,
                        'changed_by_id' => $changedBy->id,
                        'message' => "{$changedBy->name} '{$task->title}' görevinin durumunu {$oldStatus} -> {$newStatus} olarak değiştirdi",
                        'url' => "/projects/{$task->project->id}"
                    ]
                ]);

                // Send email notification if enabled
                if ($preferences['email_notifications'] ?? true) {
                    Mail::to($task->project->owner->email)->send(new TaskStatusChangedMail($task, $oldStatus, $newStatus, $changedBy, $task->project->owner));
                }
            }
        }
    }

    public static function taskCommented(Task $task, User $commenter, string $comment): void
    {
        $usersToNotify = collect();

        // Notify assigned user
        if ($task->assignedUser && $task->assignedUser->id !== $commenter->id) {
            $usersToNotify->push($task->assignedUser);
        }

        // Notify project owner
        if ($task->project->owner->id !== $commenter->id && (!$task->assignedUser || $task->project->owner->id !== $task->assignedUser->id)) {
            $usersToNotify->push($task->project->owner);
        }

        // Notify previous commenters (excluding the current commenter)
        $previousCommenters = $task->comments()
            ->with('user')
            ->where('user_id', '!=', $commenter->id)
            ->get()
            ->pluck('user')
            ->unique('id');

        $usersToNotify = $usersToNotify->merge($previousCommenters)->unique('id');

        foreach ($usersToNotify as $user) {
            $preferences = $user->notification_preferences ?? [];
            if ($preferences['task_updates'] ?? false) {
                Notification::create([
                    'user_id' => $user->id,
                    'type' => 'task_commented',
                    'data' => [
                        'task_id' => $task->id,
                        'task_title' => $task->title,
                        'project_name' => $task->project->name,
                        'commenter' => $commenter->name,
                        'commenter_id' => $commenter->id,
                        'comment_preview' => substr(strip_tags($comment), 0, 100),
                        'message' => "{$commenter->name} '{$task->title}' görevine yorum yaptı",
                        'url' => "/projects/{$task->project->id}"
                    ]
                ]);

                // Send email notification if enabled
                if ($preferences['email_notifications'] ?? true) {
                    Mail::to($user->email)->send(new TaskCommentedMail($task, $commenter, $comment, $user));
                }
            }
        }
    }
}