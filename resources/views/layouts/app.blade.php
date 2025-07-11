<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">

        <title>{{ config('app.name', 'Laravel') }}</title>

        <!-- Fonts -->
        <link rel="preconnect" href="https://fonts.bunny.net">
        <link href="https://fonts.bunny.net/css?family=figtree:400,500,600&display=swap" rel="stylesheet" />

        <!-- Scripts -->
        @vite(['resources/css/app.css', 'resources/js/app.js'])
        
        <!-- Keyboard Shortcuts -->
        <script>
            {!! app(\App\Services\KeyboardShortcutService::class)->generateJavaScript() !!}
        </script>
    </head>
    <body class="font-sans antialiased transition-colors duration-200 {{ app(\App\Services\ThemeService::class)->getCurrentTheme() === 'dark' ? 'dark bg-gray-900 text-white' : 'bg-gray-100 text-gray-900' }}">
        <div class="min-h-screen {{ app(\App\Services\ThemeService::class)->getCurrentTheme() === 'dark' ? 'bg-gray-900' : 'bg-gray-100' }}">
            <livewire:layout.navigation />

            <!-- Page Heading -->
            @if (isset($header))
                <header class="bg-white shadow">
                    <div class="max-w-7xl mx-auto py-6 px-4 sm:px-6 lg:px-8">
                        {{ $header }}
                    </div>
                </header>
            @endif

            <!-- Page Content -->
            <main>
                {{ $slot }}
            </main>
            
            <!-- Shortcuts Help Modal -->
            <livewire:shortcuts-help />
        </div>
    </body>
</html>
