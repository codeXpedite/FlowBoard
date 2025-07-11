<div class="py-6">
    <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8">
        <!-- Header -->
        <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg mb-6">
            <div class="p-6">
                <div class="flex justify-between items-center mb-4">
                    <div>
                        <h2 class="text-2xl font-bold text-gray-900 flex items-center">
                            <svg class="w-6 h-6 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 17h5l-5 5v-5zM9 7H4l5-5v5zm0 0L4 2m5 5L4 7m0 0v5m0-5h5m11 7l-5 5m5-5l-5-5m5 5H9"></path>
                            </svg>
                            Bildirim Ayarları
                        </h2>
                        <p class="text-gray-600">Hangi bildirimleri almak istediğinizi seçin</p>
                    </div>
                    <div class="flex space-x-3">
                        <a href="/dashboard" class="px-4 py-2 bg-gray-500 text-white rounded hover:bg-gray-600 transition-colors">
                            Geri Dön
                        </a>
                    </div>
                </div>

                <!-- Success/Error Messages -->
                @if (session()->has('message'))
                    <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded mb-4">
                        {{ session('message') }}
                    </div>
                @endif
            </div>
        </div>

        <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
            <!-- Notification Preferences -->
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6">
                    <h3 class="text-lg font-semibold text-gray-900 mb-4">Bildirim Tercihleri</h3>
                    
                    <form wire:submit.prevent="saveSettings" class="space-y-4">
                        <!-- Email Notifications -->
                        <div class="flex items-center justify-between">
                            <div>
                                <label class="text-sm font-medium text-gray-700">E-posta Bildirimleri</label>
                                <p class="text-xs text-gray-500">Bildirimleri e-posta olarak al</p>
                            </div>
                            <input type="checkbox" wire:model="emailNotifications" 
                                   class="h-4 w-4 text-blue-600 focus:ring-blue-500 border-gray-300 rounded">
                        </div>

                        <!-- Browser Notifications -->
                        <div class="flex items-center justify-between">
                            <div>
                                <label class="text-sm font-medium text-gray-700">Tarayıcı Bildirimleri</label>
                                <p class="text-xs text-gray-500">Push bildirimleri ile anlık haberdar ol</p>
                            </div>
                            <input type="checkbox" wire:model="browserNotifications" 
                                   class="h-4 w-4 text-blue-600 focus:ring-blue-500 border-gray-300 rounded">
                        </div>

                        <hr class="my-4">

                        <!-- Specific Notification Types -->
                        <div class="space-y-3">
                            <h4 class="font-medium text-gray-900">Bildirim Türleri</h4>
                            
                            <div class="flex items-center justify-between">
                                <div>
                                    <label class="text-sm font-medium text-gray-700">Görev Atamaları</label>
                                    <p class="text-xs text-gray-500">Size görev atandığında bildir</p>
                                </div>
                                <input type="checkbox" wire:model="taskAssignments" 
                                       class="h-4 w-4 text-blue-600 focus:ring-blue-500 border-gray-300 rounded">
                            </div>

                            <div class="flex items-center justify-between">
                                <div>
                                    <label class="text-sm font-medium text-gray-700">Görev Yorumları</label>
                                    <p class="text-xs text-gray-500">Görevlerinize yorum geldiğinde bildir</p>
                                </div>
                                <input type="checkbox" wire:model="taskComments" 
                                       class="h-4 w-4 text-blue-600 focus:ring-blue-500 border-gray-300 rounded">
                            </div>

                            <div class="flex items-center justify-between">
                                <div>
                                    <label class="text-sm font-medium text-gray-700">Görev Tarihleri</label>
                                    <p class="text-xs text-gray-500">Görev tarihi yaklaştığında hatırlat</p>
                                </div>
                                <input type="checkbox" wire:model="taskDeadlines" 
                                       class="h-4 w-4 text-blue-600 focus:ring-blue-500 border-gray-300 rounded">
                            </div>

                            <div class="flex items-center justify-between">
                                <div>
                                    <label class="text-sm font-medium text-gray-700">Proje Davetleri</label>
                                    <p class="text-xs text-gray-500">Projelere davet edildiğinizde bildir</p>
                                </div>
                                <input type="checkbox" wire:model="projectInvites" 
                                       class="h-4 w-4 text-blue-600 focus:ring-blue-500 border-gray-300 rounded">
                            </div>
                        </div>

                        <div class="pt-4">
                            <button type="submit" 
                                    class="w-full px-4 py-2 bg-blue-600 text-white rounded hover:bg-blue-700 transition-colors">
                                Ayarları Kaydet
                            </button>
                        </div>
                    </form>
                </div>
            </div>

            <!-- Browser Push Notifications -->
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6">
                    <h3 class="text-lg font-semibold text-gray-900 mb-4">Push Bildirimleri</h3>
                    
                    <!-- Push Notification Status -->
                    <div class="mb-6">
                        <div class="flex items-center justify-between p-4 bg-gray-50 rounded-lg">
                            <div>
                                <h4 class="font-medium text-gray-900">Durum</h4>
                                <p class="text-sm text-gray-600" id="notification-status">Kontrol ediliyor...</p>
                            </div>
                            <div id="notification-status-icon" class="w-3 h-3 rounded-full bg-gray-400"></div>
                        </div>
                    </div>

                    <!-- Push Controls -->
                    <div class="space-y-3">
                        <button id="enable-notifications" 
                                wire:click="enablePushNotifications"
                                class="w-full px-4 py-2 bg-green-600 text-white rounded hover:bg-green-700 transition-colors hidden">
                            Push Bildirimleri Etkinleştir
                        </button>
                        
                        <button wire:click="disablePushNotifications"
                                class="w-full px-4 py-2 bg-red-600 text-white rounded hover:bg-red-700 transition-colors">
                            Push Bildirimleri Devre Dışı Bırak
                        </button>
                        
                        <button wire:click="testNotification"
                                class="w-full px-4 py-2 bg-blue-600 text-white rounded hover:bg-blue-700 transition-colors">
                            Test Bildirimi Gönder
                        </button>
                    </div>

                    <!-- Subscription Statistics -->
                    <div class="mt-6">
                        <h4 class="font-medium text-gray-900 mb-3">İstatistikler</h4>
                        <div class="grid grid-cols-2 gap-4 text-center">
                            <div class="bg-blue-50 p-3 rounded">
                                <div class="text-lg font-bold text-blue-600">{{ $subscriptionStats['active_subscriptions'] }}</div>
                                <div class="text-xs text-blue-700">Aktif Cihaz</div>
                            </div>
                            <div class="bg-green-50 p-3 rounded">
                                <div class="text-lg font-bold text-green-600">{{ $subscriptionStats['total_subscriptions'] }}</div>
                                <div class="text-xs text-green-700">Toplam Abonelik</div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Active Subscriptions -->
        @if(count($subscriptions) > 0)
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg mt-6">
                <div class="p-6">
                    <h3 class="text-lg font-semibold text-gray-900 mb-4">Aktif Cihazlar</h3>
                    
                    <div class="space-y-3">
                        @foreach($subscriptions as $subscription)
                            <div class="flex items-center justify-between p-4 border border-gray-200 rounded-lg">
                                <div class="flex items-center space-x-3">
                                    <div class="w-3 h-3 rounded-full {{ $subscription['is_active'] ? 'bg-green-400' : 'bg-gray-400' }}"></div>
                                    <div>
                                        <div class="font-medium text-gray-900">{{ $subscription['browser'] }}</div>
                                        <div class="text-sm text-gray-500">{{ $subscription['device'] }}</div>
                                    </div>
                                </div>
                                <div class="text-right">
                                    <div class="text-sm text-gray-600">
                                        @if($subscription['last_used_at'])
                                            Son kullanım: {{ \Carbon\Carbon::parse($subscription['last_used_at'])->diffForHumans() }}
                                        @else
                                            Henüz kullanılmadı
                                        @endif
                                    </div>
                                    <button wire:click="removeSubscription({{ $subscription['id'] }})"
                                            class="text-red-600 hover:text-red-800 text-sm">
                                        Kaldır
                                    </button>
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>
            </div>
        @endif
    </div>

    <!-- Push Notification JavaScript -->
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const vapidPublicKey = '{{ $vapidPublicKey }}';
            
            // Check notification permission status
            function updateNotificationStatus() {
                if (!('Notification' in window)) {
                    document.getElementById('notification-status').textContent = 'Tarayıcınız bildirimleri desteklemiyor';
                    document.getElementById('notification-status-icon').className = 'w-3 h-3 rounded-full bg-red-400';
                    return;
                }

                switch (Notification.permission) {
                    case 'granted':
                        document.getElementById('notification-status').textContent = 'Etkinleştirildi';
                        document.getElementById('notification-status-icon').className = 'w-3 h-3 rounded-full bg-green-400';
                        @this.call('updatePushSubscriptionStatus', 'granted');
                        break;
                    case 'denied':
                        document.getElementById('notification-status').textContent = 'Reddedildi';
                        document.getElementById('notification-status-icon').className = 'w-3 h-3 rounded-full bg-red-400';
                        break;
                    case 'default':
                        document.getElementById('notification-status').textContent = 'İzin bekleniyor';
                        document.getElementById('notification-status-icon').className = 'w-3 h-3 rounded-full bg-yellow-400';
                        document.getElementById('enable-notifications').classList.remove('hidden');
                        break;
                }
            }

            // Enable push notifications
            async function enablePushNotifications() {
                try {
                    const permission = await Notification.requestPermission();
                    
                    if (permission === 'granted') {
                        // Register service worker and subscribe
                        if ('serviceWorker' in navigator && 'PushManager' in window) {
                            const registration = await navigator.serviceWorker.register('/sw.js');
                            
                            const subscription = await registration.pushManager.subscribe({
                                userVisibleOnly: true,
                                applicationServerKey: urlBase64ToUint8Array(vapidPublicKey)
                            });

                            // Send subscription to server
                            await fetch('/api/push-subscribe', {
                                method: 'POST',
                                headers: {
                                    'Content-Type': 'application/json',
                                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                                },
                                body: JSON.stringify({
                                    subscription: subscription.toJSON(),
                                    metadata: {
                                        browser: navigator.userAgent,
                                        device: navigator.platform
                                    }
                                })
                            });

                            @this.call('updatePushSubscriptionStatus', 'granted');
                        }
                    }
                    
                    updateNotificationStatus();
                } catch (error) {
                    console.error('Push notification setup failed:', error);
                }
            }

            // Helper function to convert VAPID key
            function urlBase64ToUint8Array(base64String) {
                const padding = '='.repeat((4 - base64String.length % 4) % 4);
                const base64 = (base64String + padding)
                    .replace(/-/g, '+')
                    .replace(/_/g, '/');

                const rawData = window.atob(base64);
                const outputArray = new Uint8Array(rawData.length);

                for (let i = 0; i < rawData.length; ++i) {
                    outputArray[i] = rawData.charCodeAt(i);
                }
                return outputArray;
            }

            // Initial status check
            updateNotificationStatus();

            // Event listeners
            document.getElementById('enable-notifications').addEventListener('click', enablePushNotifications);
            
            // Listen for Livewire events
            Livewire.on('enable-push-notifications', () => {
                enablePushNotifications();
            });
        });
    </script>
</div>