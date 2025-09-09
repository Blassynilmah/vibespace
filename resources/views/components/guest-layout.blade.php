<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <!-- Fonts -->
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=figtree:400,500,600&display=swap" rel="stylesheet" />

    <!-- Styles & Scripts -->
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="font-sans antialiased text-sm sm:text-base bg-gray-100">
    <div class="min-h-screen flex flex-col justify-center items-center px-4 sm:px-6 lg:px-8">
        <!-- Page Content -->
        <main class="w-full max-w-md">
            {{ $slot }}
        </main>
    </div>

    <!-- Alpine Plugins -->
    <script src="https://cdn.jsdelivr.net/npm/@alpinejs/intersect@3.x.x/dist/cdn.min.js" defer></script>
    <script src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js" defer></script>

    <script>
        document.addEventListener('alpine:init', () => {
            Alpine.plugin(window.AlpineIntersect)
            Alpine.store('videoSettings', Alpine.reactive({ muted: true }))
        })
    </script>
</body>
</html>