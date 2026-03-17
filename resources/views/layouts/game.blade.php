<!DOCTYPE html>
<html lang="en" x-data="darkMode()" :class="{ 'dark': isDark }">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Portfolio</title>

    {{-- Prevent flash: apply saved theme before Alpine boots --}}
    <script>
        (function () {
            const stored = localStorage.getItem('theme');
            const prefersDark = window.matchMedia('(prefers-color-scheme: dark)').matches;
            if (stored === 'dark' || (!stored && prefersDark)) {
                document.documentElement.classList.add('dark');
            }
        })();
    </script>

    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @livewireStyles
</head>
<body class="min-h-screen bg-white dark:bg-zinc-900 text-gray-900 dark:text-zinc-100 transition-colors duration-200">
    <main>
        @yield('content')
    </main>
    @livewireScripts
</body>
</html>
