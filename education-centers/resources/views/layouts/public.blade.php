<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" dir="{{ current_dir() }}" x-data="{ mobile: false }" x-init="$store.ui.init()">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', config('app.name'))</title>
    <meta name="description" content="@yield('description', __('Centres de formation — Education Centers'))">
    @include('layouts.partials.fonts')
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <script>
        if (localStorage.getItem('ec-theme') === 'dark' || (!localStorage.getItem('ec-theme') && window.matchMedia('(prefers-color-scheme: dark)').matches)) {
            document.documentElement.classList.add('dark');
        }
    </script>
</head>
<body class="min-h-screen bg-white text-slate-800 dark:bg-slate-950 dark:text-slate-100">
    <header class="sticky top-0 z-40 border-b border-slate-200 bg-white/90 backdrop-blur dark:border-slate-800 dark:bg-slate-950/90">
        <div class="mx-auto flex max-w-6xl items-center justify-between px-4 py-3">
            <a href="{{ route('home') }}" class="flex items-center gap-2 font-semibold">
                <span class="flex h-9 w-9 items-center justify-center rounded-lg bg-brand-600 text-white">
                    <x-icon name="academic" class="h-5 w-5" />
                </span>
                {{ __('Education Centers') }}
            </a>
            <nav class="hidden items-center gap-6 text-sm font-medium text-slate-600 md:flex dark:text-slate-300">
                <a href="{{ route('home') }}" class="{{ request()->routeIs('home') ? 'text-brand-700' : 'hover:text-brand-700' }}">{{ __('Accueil') }}</a>
                <a href="{{ route('public.centers') }}" class="{{ request()->routeIs('public.centers*') ? 'text-brand-700' : 'hover:text-brand-700' }}">{{ __('Centres') }}</a>
                <a href="{{ route('public.formations') }}" class="{{ request()->routeIs('public.formations*') ? 'text-brand-700' : 'hover:text-brand-700' }}">{{ __('Formations') }}</a>
                <a href="{{ route('public.announcements') }}" class="{{ request()->routeIs('public.announcements*') ? 'text-brand-700' : 'hover:text-brand-700' }}">{{ __('Actualités') }}</a>
                <a href="{{ route('public.contact') }}" class="{{ request()->routeIs('public.contact') ? 'text-brand-700' : 'hover:text-brand-700' }}">{{ __('Contact') }}</a>
            </nav>
            <div class="hidden items-center gap-2 md:flex">
                @include('layouts.partials.language-switcher')
                @auth
                    <a href="{{ route('dashboard') }}" class="ec-btn-primary">{{ __('Tableau de bord') }}</a>
                @else
                    <a href="{{ route('login') }}" class="ec-btn-secondary">{{ __('Connexion') }}</a>
                    <a href="{{ route('public.apply') }}" class="ec-btn-primary">{{ __('S’inscrire') }}</a>
                @endauth
            </div>
            <button type="button" class="rounded-lg p-2 md:hidden" x-on:click="mobile = !mobile" aria-label="{{ __('Menu') }}">
                <x-icon name="menu" class="h-6 w-6" />
            </button>
        </div>
        <div x-show="mobile" x-cloak x-collapse class="border-t border-slate-200 px-4 py-3 md:hidden dark:border-slate-800">
            <div class="flex flex-col gap-3 text-sm font-medium">
                @include('layouts.partials.language-switcher')
                <a href="{{ route('home') }}">{{ __('Accueil') }}</a>
                <a href="{{ route('public.centers') }}">{{ __('Centres') }}</a>
                <a href="{{ route('public.formations') }}">{{ __('Formations') }}</a>
                <a href="{{ route('public.announcements') }}">{{ __('Actualités') }}</a>
                <a href="{{ route('public.contact') }}">{{ __('Contact') }}</a>
                @auth
                    <a href="{{ route('dashboard') }}" class="ec-btn-primary">{{ __('Tableau de bord') }}</a>
                @else
                    <a href="{{ route('login') }}" class="ec-btn-secondary">{{ __('Connexion') }}</a>
                    <a href="{{ route('public.apply') }}" class="ec-btn-primary">{{ __('S’inscrire') }}</a>
                @endauth
            </div>
        </div>
    </header>

    @include('layouts.partials.toasts')
    <main>@yield('content')</main>

    <footer class="border-t border-slate-200 bg-slate-50 py-8 dark:border-slate-800 dark:bg-slate-900">
        <div class="mx-auto flex max-w-6xl flex-col gap-3 px-4 text-sm text-slate-500 sm:flex-row sm:justify-between">
            <p>&copy; {{ date('Y') }} {{ __('Education Centers') }}</p>
            <div class="flex gap-4">
                <a href="{{ route('public.contact') }}" class="hover:text-brand-700">{{ __('Contact') }}</a>
                <a href="{{ route('public.verify-certificate') }}" class="hover:text-brand-700">{{ __('Vérifier un document') }}</a>
            </div>
        </div>
    </footer>
</body>
</html>
