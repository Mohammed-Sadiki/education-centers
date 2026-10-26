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
<body class="min-h-screen bg-slate-100 dark:bg-slate-950">
    <div class="fixed right-3 top-3 z-50 flex items-center gap-2 rtl:left-3 rtl:right-auto">
        @include('layouts.partials.language-switcher')
        <button type="button" class="rounded-lg p-2 text-slate-500 hover:bg-slate-100 dark:text-slate-300 dark:hover:bg-slate-800 transition-colors"
                x-on:click="$store.ui.toggleTheme()" aria-label="{{ __('Basculer le thème') }}">
            <x-icon name="moon" class="h-5 w-5" x-show="!$store.ui.dark" />
            <x-icon name="sun" class="h-5 w-5" x-show="$store.ui.dark" x-cloak />
        </button>
    </div>
    @include('layouts.partials.toasts')
    @yield('content')
</body>
</html>
