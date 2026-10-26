<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" dir="{{ current_dir() }}" x-data x-init="$store.ui.init()">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', __('Portail Domaines - Education Centers'))</title>
    <meta name="description" content="@yield('description', __('Portail de sélection des domaines pour le Super Administrateur.'))">
    @include('layouts.partials.fonts')
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <script>
        if (localStorage.getItem('ec-theme') === 'dark' || (!localStorage.getItem('ec-theme') && window.matchMedia('(prefers-color-scheme: dark)').matches)) {
            document.documentElement.classList.add('dark');
        }
    </script>
    @stack('styles')
</head>
<body class="ec-admin min-h-screen bg-slate-50 text-slate-900 antialiased dark:bg-slate-950 dark:text-slate-100 flex flex-col">
    @php
        $user = auth()->user();
        $roleLabel = match ($user?->role) {
            'super_admin' => __('Super Admin'),
            'center_admin' => __('Admin Centre'),
            'teacher' => __('Professeur'),
            'student' => __('Élève'),
            default => $user?->role ?? __('Invité'),
        };
        $centerLabel = $user?->isSuperAdmin() ? __('Tous les centres') : ($user?->center?->name ?? __('N/A'));
    @endphp

    {{-- ────────────────────────────────────────────────────────────
         TOPBAR MINIMALE DU PORTAIL (AUCUNE SIDEBAR)
    ──────────────────────────────────────────────────────────── --}}
    <header class="sticky top-0 z-30 flex h-16 items-center justify-between border-b border-slate-200/80 bg-white/90 px-4 sm:px-6 lg:px-8 backdrop-blur dark:border-slate-800 dark:bg-slate-900/90 shadow-sm">
        {{-- Logo & Titre --}}
        <div class="flex items-center gap-3">
            <div class="flex h-10 w-10 items-center justify-center rounded-xl bg-brand-600 text-white shadow-sm">
                <x-icon name="academic" class="h-6 w-6" />
            </div>
            <div>
                <span class="text-base font-bold tracking-tight text-slate-900 dark:text-white">
                    {{ __('Education Centers') }}
                </span>
                <span class="hidden sm:inline-block ms-2 rounded-full bg-slate-100 px-2 py-0.5 text-[11px] font-medium text-slate-600 dark:bg-slate-800 dark:text-slate-300">
                    {{ $user?->isCenterAdmin() ? __('Portail Admin Centre') : __('Portail Super Admin') }}
                </span>
            </div>
        </div>

        {{-- Actions droite : Dark Mode, Profil, Déconnexion --}}
        <div class="flex items-center gap-2 sm:gap-4">
            @include('layouts.partials.language-switcher')

            {{-- Toggle Mode Sombre / Clair --}}
            <button
                type="button"
                class="rounded-xl p-2 text-slate-500 hover:bg-slate-100 dark:hover:bg-slate-800 transition-colors"
                x-on:click="$store.ui.toggleTheme()"
                aria-label="{{ __('Basculer le thème') }}"
                title="{{ __('Basculer le thème') }}"
            >
                <x-icon name="moon" class="h-5 w-5" x-show="!$store.ui.dark" />
                <x-icon name="sun" class="h-5 w-5" x-show="$store.ui.dark" x-cloak />
            </button>

            <div class="h-6 w-px bg-slate-200 dark:bg-slate-800"></div>

            {{-- Profil de l'utilisateur --}}
            <div class="flex items-center gap-2.5">
                <x-ui.avatar :name="$user->name" size="sm" />
                <div class="hidden text-start sm:block">
                    <span class="block max-w-[160px] truncate text-sm font-semibold text-slate-800 dark:text-slate-100">
                        {{ $user->name }}
                    </span>
                    <span class="block max-w-[160px] truncate text-xs text-slate-500 dark:text-slate-400">
                        {{ $roleLabel }} · {{ $centerLabel }}
                    </span>
                </div>
            </div>

            {{-- Bouton Déconnexion direct --}}
            <form method="POST" action="{{ route('logout') }}" class="inline">
                @csrf
                <button
                    type="submit"
                    id="portal-logout-button"
                    class="inline-flex items-center gap-1.5 rounded-xl border border-slate-200 bg-white px-3 py-1.5 text-xs font-semibold text-slate-700 shadow-sm
                           hover:bg-rose-50 hover:border-rose-200 hover:text-rose-600
                           dark:border-slate-700 dark:bg-slate-800 dark:text-slate-300 dark:hover:bg-rose-950/40 dark:hover:border-rose-800 dark:hover:text-rose-400
                           transition-all duration-150"
                    title="{{ __('Se déconnecter') }}"
                >
                    <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M15.75 9V5.25A2.25 2.25 0 0 0 13.5 3h-6a2.25 2.25 0 0 0-2.25 2.25v13.5A2.25 2.25 0 0 0 7.5 21h6a2.25 2.25 0 0 0 2.25-2.25V15m3 0 3-3m0 0-3-3m3 3H9" />
                    </svg>
                    <span class="hidden sm:inline">{{ __('Déconnexion') }}</span>
                </button>
            </form>
        </div>
    </header>

    {{-- ────────────────────────────────────────────────────────────
         CONTENU PRINCIPAL DU PORTAIL
    ──────────────────────────────────────────────────────────── --}}
    <main class="ec-page-in flex-1 py-8 sm:py-12 px-4 sm:px-6 lg:px-8 max-w-7xl mx-auto w-full">
        @yield('content')
    </main>

    {{-- Pied de page minimal --}}
    <footer class="border-t border-slate-200/80 py-4 text-center text-xs text-slate-400 dark:border-slate-800 dark:text-slate-500">
        &copy; {{ date('Y') }} {{ __('Education Centers — Espace Super Administrateur') }}
    </footer>

    @include('layouts.partials.toasts')
    @stack('scripts')
</body>
</html>
