<div class="relative">
    <!-- Theme Toggle Button -->
    <button wire:click="toggleTheme" 
            class="flex items-center justify-center w-10 h-10 rounded-lg transition-colors duration-200 hover:bg-gray-100 dark:hover:bg-gray-700"
            title="{{ $isDarkTheme ? 'Switch to Light Mode' : 'Switch to Dark Mode' }}">
        @if($isDarkTheme)
            <!-- Sun Icon (Light Mode) -->
            <svg class="w-5 h-5 text-yellow-500" fill="currentColor" viewBox="0 0 20 20">
                <path fill-rule="evenodd" d="M10 2a1 1 0 011 1v1a1 1 0 11-2 0V3a1 1 0 011-1zm4 8a4 4 0 11-8 0 4 4 0 018 0zm-.464 4.95l.707.707a1 1 0 001.414-1.414l-.707-.707a1 1 0 00-1.414 1.414zm2.12-10.607a1 1 0 010 1.414l-.706.707a1 1 0 11-1.414-1.414l.707-.707a1 1 0 011.414 0zM17 11a1 1 0 100-2h-1a1 1 0 100 2h1zm-7 4a1 1 0 011 1v1a1 1 0 11-2 0v-1a1 1 0 011-1zM5.05 6.464A1 1 0 106.465 5.05l-.708-.707a1 1 0 00-1.414 1.414l.707.707zm1.414 8.486l-.707.707a1 1 0 01-1.414-1.414l.707-.707a1 1 0 011.414 1.414zM4 11a1 1 0 100-2H3a1 1 0 000 2h1z" clip-rule="evenodd"/>
            </svg>
        @else
            <!-- Moon Icon (Dark Mode) -->
            <svg class="w-5 h-5 text-gray-700 dark:text-gray-300" fill="currentColor" viewBox="0 0 20 20">
                <path d="M17.293 13.293A8 8 0 016.707 2.707a8.001 8.001 0 1010.586 10.586z"/>
            </svg>
        @endif
    </button>
</div>

<script>
document.addEventListener('livewire:init', () => {
    // Listen for theme changes
    Livewire.on('theme-changed', (event) => {
        const theme = event.theme;
        const body = document.body;
        
        if (theme === 'dark') {
            body.classList.add('dark');
            body.classList.remove('light');
        } else {
            body.classList.add('light');
            body.classList.remove('dark');
        }
        
        // Store in localStorage for immediate application
        localStorage.setItem('theme', theme);
    });
    
    // Apply theme on page load
    const currentTheme = '{{ $currentTheme }}';
    const body = document.body;
    
    if (currentTheme === 'dark') {
        body.classList.add('dark');
    } else {
        body.classList.add('light');
    }
});
</script>