<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" dir="{{ current_dir() }}" x-data x-init="$store.ui.init()">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', config('app.name'))</title>
    @include('layouts.partials.fonts')
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <script>
        if (localStorage.getItem('ec-theme') === 'dark' || (!localStorage.getItem('ec-theme') && window.matchMedia('(prefers-color-scheme: dark)').matches)) {
            document.documentElement.classList.add('dark');
        }
    </script>
</head>
<body class="relative min-h-screen bg-slate-50/50 text-slate-800 dark:bg-slate-950 dark:text-slate-100 overflow-x-hidden antialiased">
    {{-- Arrière-plan Aurora 3D : Couche 1 (Décor fixe) + Couche 2 (Lumière souris interactive) --}}
    <canvas id="ec-aurora-static" class="ec-aurora-layer ec-aurora-static" aria-hidden="true"></canvas>
    <canvas id="ec-aurora-interactive" class="ec-aurora-layer ec-aurora-interactive" aria-hidden="true"></canvas>

    @include('layouts.partials.toasts')

    <main class="relative z-10 flex min-h-screen items-center justify-center p-4 sm:p-6 lg:p-8">
        @yield('content')
    </main>
</body>
</html>
