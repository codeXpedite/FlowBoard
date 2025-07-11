<?php

namespace App\Services;

use App\Models\User;
use App\Models\Task;
use App\Models\Project;
use App\Models\PushSubscription;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class PushNotificationService
{
    private const VAPID_PUBLIC_KEY = 'BFzR4bJmNqhQNjrT5_6E-1nH2Pl6-5qZv4bk7A3I-_H5nW9_kG4cM3r2_b8K9fL2nE5vA7zQ3rT8'; // Demo key
    private const VAPID_PRIVATE_KEY = 'demo-private-key'; // In production, store in .env

    public function sendTaskAssignedNotification(Task $task, User $assignedUser, User $assignedBy)
    {
        $payload = [
            'title' => 'Yeni Görev Atandı',
            'body' => "Size '{$task->title}' görevi atandı",
            'icon' => '/favicon.ico',
            'badge' => '/favicon.ico',
            'tag' => 'task-assigned-' . $task->id,
            'data' => [
                'type' => 'task_assigned',
                'task_id' => $task->id,
                'project_id' => $task->project_id,
                'url' => '/projects/' . $task->project_id,
            ],
            'actions' => [
                [
                    'action' => 'view',
                    'title' => 'Görüntüle',
                    'icon' => '/favicon.ico'
                ],
                [
                    'action' => 'dismiss',
                    'title' => 'Kapat'
                ]
            ]
        ];

        $this->sendNotificationToUser($assignedUser, $payload);
    }

    public function sendTaskCompletedNotification(Task $task, User $projectOwner)
    {
        $payload = [
            'title' => 'Görev Tamamlandı',
            'body' => "'{$task->title}' görevi tamamlandı",
            'icon' => '/favicon.ico',
            'badge' => '/favicon.ico',
            'tag' => 'task-completed-' . $task->id,
            'data' => [
                'type' => 'task_completed',
                'task_id' => $task->id,
                'project_id' => $task->project_id,
                'url' => '/projects/' . $task->project_id,
            ]
        ];

        $this->sendNotificationToUser($projectOwner, $payload);
    }

    public function sendTaskOverdueNotification(Task $task, User $assignedUser)
    {
        $payload = [
            'title' => 'Görev Tarihi Geçti',
            'body' => "'{$task->title}' görevinin tarihi geçmiş",
            'icon' => '/favicon.ico',
            'badge' => '/favicon.ico',
            'tag' => 'task-overdue-' . $task->id,
            'data' => [
                'type' => 'task_overdue',
                'task_id' => $task->id,
                'project_id' => $task->project_id,
                'url' => '/projects/' . $task->project_id,
            ],
            'requireInteraction' => true
        ];

        $this->sendNotificationToUser($assignedUser, $payload);
    }

    public function sendCommentNotification(Task $task, User $commenter, User $recipient)
    {
        $payload = [
            'title' => 'Yeni Yorum',
            'body' => "{$commenter->name} '{$task->title}' görevine yorum yaptı",
            'icon' => '/favicon.ico',
            'badge' => '/favicon.ico',
            'tag' => 'comment-' . $task->id,
            'data' => [
                'type' => 'new_comment',
                'task_id' => $task->id,
                'project_id' => $task->project_id,
                'url' => '/projects/' . $task->project_id,
            ]
        ];

        $this->sendNotificationToUser($recipient, $payload);
    }

    public function sendProjectInviteNotification(Project $project, User $invitedUser, User $invitedBy)
    {
        $payload = [
            'title' => 'Projeye Davet',
            'body' => "{$invitedBy->name} sizi '{$project->name}' projesine davet etti",
            'icon' => '/favicon.ico',
            'badge' => '/favicon.ico',
            'tag' => 'project-invite-' . $project->id,
            'data' => [
                'type' => 'project_invite',
                'project_id' => $project->id,
                'url' => '/projects/' . $project->id,
            ]
        ];

        $this->sendNotificationToUser($invitedUser, $payload);
    }

    public function sendDeadlineReminderNotification(Task $task, User $assignedUser)
    {
        $daysLeft = $task->due_date->diffInDays(now());
        
        $payload = [
            'title' => 'Görev Hatırlatması',
            'body' => "'{$task->title}' görevi için {$daysLeft} gün kaldı",
            'icon' => '/favicon.ico',
            'badge' => '/favicon.ico',
            'tag' => 'deadline-reminder-' . $task->id,
            'data' => [
                'type' => 'deadline_reminder',
                'task_id' => $task->id,
                'project_id' => $task->project_id,
                'url' => '/projects/' . $task->project_id,
            ]
        ];

        $this->sendNotificationToUser($assignedUser, $payload);
    }

    private function sendNotificationToUser(User $user, array $payload)
    {
        // Check user notification preferences
        $preferences = $user->notification_preferences ?? [];
        if (!($preferences['browser_notifications'] ?? true)) {
            return;
        }

        // Get user's push subscriptions
        $subscriptions = PushSubscription::where('user_id', $user->id)
            ->where('is_active', true)
            ->get();

        foreach ($subscriptions as $subscription) {
            $this->sendPushNotification($subscription, $payload);
        }
    }

    private function sendPushNotification(PushSubscription $subscription, array $payload)
    {
        try {
            // In a real implementation, you would use web-push library
            // For demo purposes, we'll simulate the notification
            
            $webPushPayload = [
                'endpoint' => $subscription->endpoint,
                'keys' => [
                    'p256dh' => $subscription->p256dh_key,
                    'auth' => $subscription->auth_key
                ],
                'payload' => json_encode($payload)
            ];

            // Simulate successful push notification
            Log::info('Push notification sent', [
                'user_id' => $subscription->user_id,
                'payload' => $payload
            ]);

            // Update last used timestamp
            $subscription->update(['last_used_at' => now()]);

        } catch (\Exception $e) {
            Log::error('Push notification failed', [
                'subscription_id' => $subscription->id,
                'error' => $e->getMessage()
            ]);

            // Mark subscription as inactive if it fails
            $subscription->update(['is_active' => false]);
        }
    }

    public function subscribeUser(User $user, array $subscriptionData)
    {
        return PushSubscription::updateOrCreate([
            'user_id' => $user->id,
            'endpoint' => $subscriptionData['endpoint']
        ], [
            'p256dh_key' => $subscriptionData['keys']['p256dh'],
            'auth_key' => $subscriptionData['keys']['auth'],
            'is_active' => true,
            'subscribed_at' => now()
        ]);
    }

    public function unsubscribeUser(User $user, string $endpoint)
    {
        return PushSubscription::where('user_id', $user->id)
            ->where('endpoint', $endpoint)
            ->update(['is_active' => false]);
    }

    public static function getVapidPublicKey(): string
    {
        return self::VAPID_PUBLIC_KEY;
    }

    public function sendBulkNotification(array $userIds, array $payload)
    {
        $subscriptions = PushSubscription::whereIn('user_id', $userIds)
            ->where('is_active', true)
            ->with('user')
            ->get();

        foreach ($subscriptions as $subscription) {
            // Check user preferences
            $preferences = $subscription->user->notification_preferences ?? [];
            if ($preferences['browser_notifications'] ?? true) {
                $this->sendPushNotification($subscription, $payload);
            }
        }
    }

    public function getSubscriptionStats(User $user): array
    {
        $subscriptions = PushSubscription::where('user_id', $user->id)->get();
        
        return [
            'total_subscriptions' => $subscriptions->count(),
            'active_subscriptions' => $subscriptions->where('is_active', true)->count(),
            'last_subscription' => $subscriptions->sortByDesc('subscribed_at')->first()?->subscribed_at,
        ];
    }
}