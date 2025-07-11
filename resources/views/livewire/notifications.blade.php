<div class="relative" x-data="{ showDropdown: @entangle('showDropdown') }">
    <!-- Notification Bell -->
    <button @click="showDropdown = !showDropdown" class="relative p-2 text-gray-600 hover:text-gray-800 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 rounded-md">
        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 17h5l-5 5-5-5h5V3h5v14zM9 3a6 6 0 016 6v7l2 2H1l2-2V9a6 6 0 016-6z"></path>
        </svg>
        
        @if($unreadCount > 0)
            <span class="absolute top-0 right-0 inline-flex items-center justify-center px-2 py-1 text-xs font-bold leading-none text-red-100 transform translate-x-1/2 -translate-y-1/2 bg-red-600 rounded-full">
                {{ $unreadCount > 99 ? '99+' : $unreadCount }}
            </span>
        @endif
    </button>

    <!-- Notifications Dropdown -->
    <div x-show="showDropdown" 
         x-transition:enter="transition ease-out duration-100"
         x-transition:enter-start="transform opacity-0 scale-95"
         x-transition:enter-end="transform opacity-100 scale-100"
         x-transition:leave="transition ease-in duration-75"
         x-transition:leave-start="transform opacity-100 scale-100"
         x-transition:leave-end="transform opacity-0 scale-95"
         @click.away="showDropdown = false"
         class="absolute right-0 mt-2 w-80 bg-white rounded-md shadow-lg ring-1 ring-black ring-opacity-5 focus:outline-none z-50"
         style="display: none;">
        
        <div class="p-4">
            <div class="flex items-center justify-between mb-4">
                <h3 class="text-lg font-medium text-gray-900">Bildirimler</h3>
                @if($unreadCount > 0)
                    <button wire:click="markAllAsRead" class="text-sm text-blue-600 hover:text-blue-800">
                        Tümünü Okundu Olarak İşaretle
                    </button>
                @endif
            </div>

            <div class="max-h-96 overflow-y-auto">
                @forelse($notifications as $notification)
                    <div class="flex items-start p-3 {{ $notification->isRead() ? 'bg-white' : 'bg-blue-50' }} rounded-lg mb-2 last:mb-0 hover:bg-gray-50 transition-colors">
                        <div class="flex-shrink-0">
                            <div class="w-8 h-8 bg-blue-500 rounded-full flex items-center justify-center">
                                @if($notification->type === 'task_assigned')
                                    <svg class="w-4 h-4 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"></path>
                                    </svg>
                                @elseif($notification->type === 'task_status_changed')
                                    <svg class="w-4 h-4 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4M7.835 4.697a3.42 3.42 0 001.946-.806 3.42 3.42 0 014.438 0 3.42 3.42 0 001.946.806 3.42 3.42 0 013.138 3.138 3.42 3.42 0 00.806 1.946 3.42 3.42 0 010 4.438 3.42 3.42 0 00-.806 1.946 3.42 3.42 0 01-3.138 3.138 3.42 3.42 0 00-1.946.806 3.42 3.42 0 01-4.438 0 3.42 3.42 0 00-1.946-.806 3.42 3.42 0 01-3.138-3.138 3.42 3.42 0 00-.806-1.946 3.42 3.42 0 010-4.438 3.42 3.42 0 00.806-1.946 3.42 3.42 0 013.138-3.138z"></path>
                                    </svg>
                                @else
                                    <svg class="w-4 h-4 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 8h10M7 12h4m1 8l-4-4H5a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v8a2 2 0 01-2 2h-3l-4 4z"></path>
                                    </svg>
                                @endif
                            </div>
                        </div>
                        
                        <div class="ml-3 flex-1 min-w-0">
                            <p class="text-sm text-gray-900 font-medium">
                                {{ $notification->data['message'] ?? '' }}
                            </p>
                            @if(isset($notification->data['project_name']))
                                <p class="text-xs text-gray-500 mt-1">
                                    Proje: {{ $notification->data['project_name'] }}
                                </p>
                            @endif
                            <p class="text-xs text-gray-400 mt-1">
                                {{ $notification->created_at->diffForHumans() }}
                            </p>
                        </div>

                        <div class="flex-shrink-0 ml-2">
                            @if(!$notification->isRead())
                                <button wire:click="markAsRead({{ $notification->id }})" 
                                        class="text-blue-600 hover:text-blue-800 text-xs">
                                    Okundu
                                </button>
                            @endif
                        </div>
                    </div>
                @empty
                    <div class="text-center py-8 text-gray-500">
                        <svg class="w-12 h-12 mx-auto mb-4 text-gray-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 17h5l-5 5-5-5h5V3h5v14zM9 3a6 6 0 016 6v7l2 2H1l2-2V9a6 6 0 016-6z"></path>
                        </svg>
                        <p>Henüz bildiriminiz yok.</p>
                    </div>
                @endforelse
            </div>
        </div>
    </div>
</div>
