<!-- Shortcuts Help Modal -->
@if($showModal)
    <div class="fixed inset-0 z-50 overflow-y-auto" wire:click="closeModal">
        <div class="flex items-center justify-center min-h-screen px-4 pt-4 pb-20 text-center sm:block sm:p-0">
            <!-- Background overlay -->
            <div class="fixed inset-0 transition-opacity bg-gray-500 bg-opacity-75 dark:bg-gray-900 dark:bg-opacity-75"></div>

            <!-- Modal panel -->
            <div class="inline-block w-full max-w-4xl my-8 overflow-hidden text-left align-middle transition-all transform bg-white dark:bg-gray-800 shadow-xl rounded-lg" 
                 wire:click.stop>
                <!-- Header -->
                <div class="px-6 py-4 border-b border-gray-200 dark:border-gray-700">
                    <div class="flex items-center justify-between">
                        <h3 class="text-lg font-medium text-gray-900 dark:text-white">
                            Klavye Kısayolları
                        </h3>
                        <button wire:click="closeModal" 
                                class="text-gray-400 hover:text-gray-600 dark:hover:text-gray-300">
                            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                            </svg>
                        </button>
                    </div>
                </div>

                <!-- Content -->
                <div class="px-6 py-4 max-h-96 overflow-y-auto">
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        @foreach($shortcuts as $categoryKey => $category)
                            <div class="space-y-3">
                                <h4 class="text-sm font-semibold text-gray-900 dark:text-white uppercase tracking-wide">
                                    {{ $category['title'] }}
                                </h4>
                                
                                <div class="space-y-2">
                                    @foreach($category['shortcuts'] as $shortcut)
                                        <div class="flex items-center justify-between py-2 px-3 rounded bg-gray-50 dark:bg-gray-700">
                                            <span class="text-sm text-gray-700 dark:text-gray-300">
                                                {{ $shortcut['description'] }}
                                            </span>
                                            
                                            <div class="flex items-center space-x-1">
                                                @php
                                                    $keys = explode(' + ', $isMac ? $shortcut['mac'] : $shortcut['key']);
                                                @endphp
                                                
                                                @foreach($keys as $index => $key)
                                                    @if($index > 0)
                                                        <span class="text-xs text-gray-500 dark:text-gray-400">+</span>
                                                    @endif
                                                    
                                                    <kbd class="px-2 py-1 text-xs font-mono bg-white dark:bg-gray-600 border border-gray-300 dark:border-gray-500 rounded text-gray-700 dark:text-gray-300 shadow-sm">
                                                        {{ trim($key) }}
                                                    </kbd>
                                                @endforeach
                                            </div>
                                        </div>
                                    @endforeach
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>

                <!-- Footer -->
                <div class="px-6 py-4 border-t border-gray-200 dark:border-gray-700 bg-gray-50 dark:bg-gray-700">
                    <div class="flex items-center justify-between text-sm text-gray-600 dark:text-gray-400">
                        <div class="flex items-center space-x-2">
                            <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z" clip-rule="evenodd"/>
                            </svg>
                            <span>Kısayollar form alanlarında çalışmaz</span>
                        </div>
                        
                        <button wire:click="closeModal" 
                                class="px-4 py-2 text-sm font-medium text-gray-700 dark:text-gray-300 bg-white dark:bg-gray-600 border border-gray-300 dark:border-gray-500 rounded hover:bg-gray-50 dark:hover:bg-gray-500">
                            Kapat
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endif

<script>
// Listen for keyboard shortcut events
document.addEventListener('livewire:init', () => {
    window.addEventListener('keyboard-shortcut', (event) => {
        const action = event.detail.action;
        
        switch(action) {
            case 'show-shortcuts':
                @this.call('showModal');
                break;
                
            case 'close-modal':
                @this.call('closeModal');
                break;
                
            case 'global-search':
                // Focus search input if available
                const searchInput = document.querySelector('input[wire\\:model*="search"]');
                if (searchInput) {
                    searchInput.focus();
                }
                break;
                
            case 'new-task':
                // Trigger new task action if available
                const newTaskButton = document.querySelector('[wire\\:click*="openTaskForm"]');
                if (newTaskButton) {
                    newTaskButton.click();
                }
                break;
                
            case 'toggle-theme':
                // Trigger theme toggle
                const themeButton = document.querySelector('[wire\\:click*="toggleTheme"]');
                if (themeButton) {
                    themeButton.click();
                }
                break;
                
            case 'toggle-filters':
                // Toggle filters
                const filterButton = document.querySelector('[wire\\:click*="toggleFilters"]');
                if (filterButton) {
                    filterButton.click();
                }
                break;
                
            case 'refresh-board':
                // Refresh kanban board
                const refreshButton = document.querySelector('[wire\\:click*="refreshBoard"]');
                if (refreshButton) {
                    refreshButton.click();
                }
                break;
                
            case 'focus-search':
                // Focus search input
                const focusSearchInput = document.querySelector('input[wire\\:model*="search"]');
                if (focusSearchInput) {
                    focusSearchInput.focus();
                    focusSearchInput.select();
                }
                break;
                
            case 'go-dashboard':
                window.location.href = '/dashboard';
                break;
                
            case 'go-projects':
                window.location.href = '/dashboard';
                break;
                
            case 'go-notifications':
                window.location.href = '/notifications/settings';
                break;
                
            case 'go-settings':
                window.location.href = '/profile';
                break;
        }
    });
});
</script>