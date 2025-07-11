<?php

namespace App\Livewire;

use App\Models\Notification;
use Livewire\Component;
use Livewire\Attributes\On;

class Notifications extends Component
{
    public $showDropdown = false;

    public function toggleDropdown()
    {
        $this->showDropdown = !$this->showDropdown;
    }

    public function markAsRead($notificationId)
    {
        $notification = Notification::where('id', $notificationId)
            ->where('user_id', auth()->id())
            ->first();

        if ($notification) {
            $notification->markAsRead();
        }
    }

    public function markAllAsRead()
    {
        auth()->user()->notifications()->unread()->update(['read_at' => now()]);
    }

    public function closeDropdown()
    {
        $this->showDropdown = false;
    }

    #[On('notification-created')]
    public function refreshNotifications()
    {
        // This will trigger a re-render to show new notifications
    }

    public function render()
    {
        $notifications = auth()->user()
            ->notifications()
            ->orderBy('created_at', 'desc')
            ->limit(10)
            ->get();

        $unreadCount = auth()->user()
            ->notifications()
            ->unread()
            ->count();

        return view('livewire.notifications', compact('notifications', 'unreadCount'));
    }
}
