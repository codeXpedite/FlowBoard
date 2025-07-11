<?php

namespace App\Livewire;

use App\Models\PushSubscription;
use App\Services\PushNotificationService;
use Livewire\Component;
use Illuminate\Support\Facades\Auth;

class NotificationSettings extends Component
{
    public $emailNotifications = true;
    public $browserNotifications = true;
    public $taskAssignments = true;
    public $taskComments = true;
    public $taskDeadlines = true;
    public $projectInvites = true;
    public $pushSubscriptionStatus = 'unknown';
    public $subscriptions = [];

    public function mount()
    {
        $user = Auth::user();
        $preferences = $user->notification_preferences ?? [];
        
        $this->emailNotifications = $preferences['email_notifications'] ?? true;
        $this->browserNotifications = $preferences['browser_notifications'] ?? true;
        $this->taskAssignments = $preferences['task_assignments'] ?? true;
        $this->taskComments = $preferences['task_comments'] ?? true;
        $this->taskDeadlines = $preferences['task_deadlines'] ?? true;
        $this->projectInvites = $preferences['project_invites'] ?? true;
        
        $this->loadSubscriptions();
    }

    public function loadSubscriptions()
    {
        $this->subscriptions = PushSubscription::where('user_id', Auth::id())
            ->orderBy('created_at', 'desc')
            ->get()
            ->map(function ($subscription) {
                return [
                    'id' => $subscription->id,
                    'browser' => $subscription->getBrowserInfo(),
                    'device' => $subscription->getDeviceInfo(),
                    'is_active' => $subscription->is_active,
                    'subscribed_at' => $subscription->subscribed_at,
                    'last_used_at' => $subscription->last_used_at,
                ];
            })->toArray();
    }

    public function saveSettings()
    {
        $user = Auth::user();
        
        $preferences = [
            'email_notifications' => $this->emailNotifications,
            'browser_notifications' => $this->browserNotifications,
            'task_assignments' => $this->taskAssignments,
            'task_comments' => $this->taskComments,
            'task_deadlines' => $this->taskDeadlines,
            'project_invites' => $this->projectInvites,
        ];
        
        $user->update(['notification_preferences' => $preferences]);
        
        session()->flash('message', 'Bildirim ayarları güncellendi.');
    }

    public function enablePushNotifications()
    {
        $this->dispatch('enable-push-notifications');
    }

    public function disablePushNotifications()
    {
        $subscriptions = PushSubscription::where('user_id', Auth::id())
            ->where('is_active', true)
            ->get();
            
        foreach ($subscriptions as $subscription) {
            $subscription->deactivate();
        }
        
        $this->browserNotifications = false;
        $this->saveSettings();
        $this->loadSubscriptions();
        
        session()->flash('message', 'Browser bildirimleri devre dışı bırakıldı.');
    }

    public function removeSubscription($subscriptionId)
    {
        $subscription = PushSubscription::where('user_id', Auth::id())
            ->where('id', $subscriptionId)
            ->first();
            
        if ($subscription) {
            $subscription->delete();
            $this->loadSubscriptions();
            session()->flash('message', 'Abonelik kaldırıldı.');
        }
    }

    public function testNotification()
    {
        $pushService = new PushNotificationService();
        
        $payload = [
            'title' => 'Test Bildirimi',
            'body' => 'Bu bir test bildirimidir. Bildirimler düzgün çalışıyor!',
            'icon' => '/favicon.ico',
            'tag' => 'test-notification',
            'data' => [
                'type' => 'test',
                'url' => '/dashboard',
            ]
        ];
        
        $user = Auth::user();
        $pushService->sendNotificationToUser($user, $payload);
        
        session()->flash('message', 'Test bildirimi gönderildi.');
    }

    public function updatePushSubscriptionStatus($status)
    {
        $this->pushSubscriptionStatus = $status;
        
        if ($status === 'granted') {
            $this->browserNotifications = true;
            $this->saveSettings();
        }
        
        $this->loadSubscriptions();
    }

    public function render()
    {
        $pushService = new PushNotificationService();
        $subscriptionStats = $pushService->getSubscriptionStats(Auth::user());
        
        return view('livewire.notification-settings', [
            'subscriptionStats' => $subscriptionStats,
            'vapidPublicKey' => PushNotificationService::getVapidPublicKey(),
        ])->layout('layouts.app');
    }
}