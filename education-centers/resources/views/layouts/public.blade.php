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
<body class="relative min-h-screen bg-[#fafafc] text-slate-800 dark:bg-[#080c16] dark:text-slate-100 overflow-x-hidden">
    {{-- Arrière-plan Aurora 3D : Couche 1 (Décor fixe) + Couche 2 (Lumière souris interactive) --}}
    <canvas id="ec-aurora-static" class="ec-aurora-layer ec-aurora-static" aria-hidden="true"></canvas>
    <canvas id="ec-aurora-interactive" class="ec-aurora-layer ec-aurora-interactive" aria-hidden="true"></canvas>

    @php
        $navItems = [
            ['route' => 'home', 'pattern' => 'home', 'label' => __('Accueil')],
            ['route' => 'public.centers', 'pattern' => 'public.centers*', 'label' => __('Centres')],
            ['route' => 'public.formations', 'pattern' => 'public.formations*', 'label' => __('Formations')],
            ['route' => 'public.announcements', 'pattern' => 'public.announcements*', 'label' => __('Actualités')],
            ['route' => 'public.contact', 'pattern' => 'public.contact', 'label' => __('Contact')],
        ];
    @endphp

    <header class="sticky top-0 z-40 border-b border-slate-200/80 bg-white/90 shadow-[0_1px_10px_rgba(15,23,42,0.05)] backdrop-blur dark:border-slate-800 dark:bg-slate-950/90">
        <div class="mx-auto flex h-[4.25rem] max-w-7xl items-center justify-between gap-4 px-4 sm:px-6 lg:px-8">
            <a href="{{ route('home') }}" class="flex shrink-0 items-center gap-2.5">
                <span class="flex h-10 w-10 items-center justify-center rounded-xl bg-gradient-to-br from-brand-500 to-brand-700 text-white shadow-md shadow-brand-600/30">
                    <x-icon name="academic" class="h-5 w-5" />
                </span>
                <span class="text-lg font-bold tracking-tight text-slate-900 dark:text-white">{{ __('Education Centers') }}</span>
            </a>

            <nav class="hidden items-center gap-7 text-sm font-semibold text-slate-600 lg:flex dark:text-slate-300" aria-label="{{ __('Navigation') }}">
                @foreach($navItems as $item)
                    @php($active = request()->routeIs($item['pattern']))
                    <a href="{{ route($item['route']) }}"
                       @if($active) aria-current="page" @endif
                       class="relative py-1 transition-colors hover:text-brand-600 dark:hover:text-brand-400 {{ $active ? 'text-brand-600 dark:text-brand-400' : '' }}">
                        {{ $item['label'] }}
                        <span class="absolute -bottom-1 start-0 h-0.5 rounded-full bg-brand-600 transition-all {{ $active ? 'w-full' : 'w-0' }}"></span>
                    </a>
                @endforeach
            </nav>

            <div class="hidden items-center gap-3 lg:flex">
                @include('layouts.partials.language-switcher')
                <button type="button" class="rounded-lg p-2 text-slate-500 hover:bg-slate-100 dark:text-slate-300 dark:hover:bg-slate-800 transition-colors"
                        x-on:click="$store.ui.toggleTheme()" aria-label="{{ __('Basculer le thème') }}">
                    <x-icon name="moon" class="h-5 w-5" x-show="!$store.ui.dark" />
                    <x-icon name="sun" class="h-5 w-5" x-show="$store.ui.dark" x-cloak />
                </button>
                @auth
                    <a href="{{ route('dashboard') }}" class="ec-btn-primary">
                        <x-icon name="home" class="h-4 w-4 rtl:-scale-x-100" />
                        {{ __('Tableau de bord') }}
                    </a>
                @else
                    <a href="{{ route('login') }}" class="ec-btn-secondary">{{ __('Connexion') }}</a>
                    <a href="{{ route('public.apply') }}" class="ec-btn-primary shadow-md shadow-brand-600/25">
                        <x-icon name="user" class="h-4 w-4" />
                        {{ __('S’inscrire') }}
                    </a>
                @endauth
            </div>

            <button type="button"
                    class="rounded-xl p-2 text-slate-600 transition hover:bg-slate-100 lg:hidden dark:text-slate-300 dark:hover:bg-slate-800"
                    x-on:click="mobile = !mobile"
                    :aria-expanded="mobile.toString()"
                    aria-label="{{ __('Menu') }}">
                <x-icon name="menu" class="h-6 w-6" x-show="!mobile" />
                <x-icon name="x" class="h-6 w-6" x-show="mobile" x-cloak />
            </button>
        </div>

        <div x-show="mobile" x-cloak x-collapse class="border-t border-slate-200 bg-white px-4 py-4 lg:hidden dark:border-slate-800 dark:bg-slate-950">
            <nav class="flex flex-col gap-1 text-sm font-semibold text-slate-700 dark:text-slate-200" aria-label="{{ __('Navigation') }}">
                @foreach($navItems as $item)
                    <a href="{{ route($item['route']) }}"
                       class="rounded-xl px-3 py-2.5 transition hover:bg-brand-50 hover:text-brand-700 dark:hover:bg-slate-800 {{ request()->routeIs($item['pattern']) ? 'bg-brand-50 text-brand-700 dark:bg-slate-800 dark:text-brand-300' : '' }}">
                        {{ $item['label'] }}
                    </a>
                @endforeach
            </nav>
            <div class="mt-4 flex flex-col gap-3 border-t border-slate-100 pt-4 dark:border-slate-800">
                <div class="flex items-center gap-2">
                    @include('layouts.partials.language-switcher')
                    <button type="button" class="rounded-lg p-2 text-slate-500 hover:bg-slate-100 dark:text-slate-300 dark:hover:bg-slate-800 transition-colors"
                            x-on:click="$store.ui.toggleTheme()" aria-label="{{ __('Basculer le thème') }}">
                        <x-icon name="moon" class="h-5 w-5" x-show="!$store.ui.dark" />
                        <x-icon name="sun" class="h-5 w-5" x-show="$store.ui.dark" x-cloak />
                    </button>
                </div>
                @auth
                    <a href="{{ route('dashboard') }}" class="ec-btn-primary w-full">{{ __('Tableau de bord') }}</a>
                @else
                    <div class="grid grid-cols-2 gap-3">
                        <a href="{{ route('login') }}" class="ec-btn-secondary w-full">{{ __('Connexion') }}</a>
                        <a href="{{ route('public.apply') }}" class="ec-btn-primary w-full">{{ __('S’inscrire') }}</a>
                    </div>
                @endauth
            </div>
        </div>
    </header>

    @include('layouts.partials.toasts')
    <main class="relative z-10">@yield('content')</main>

    <footer class="relative z-10 bg-slate-950 text-slate-300">
        <div class="mx-auto grid max-w-7xl gap-10 px-4 py-14 sm:grid-cols-2 sm:px-6 lg:grid-cols-4 lg:px-8">
            <div>
                <a href="{{ route('home') }}" class="flex items-center gap-2.5">
                    <span class="flex h-9 w-9 items-center justify-center rounded-xl bg-gradient-to-br from-brand-500 to-brand-700 text-white">
                        <x-icon name="academic" class="h-5 w-5" />
                    </span>
                    <span class="text-lg font-bold tracking-tight text-white">{{ __('Education Centers') }}</span>
                </a>
                <p class="mt-4 max-w-xs text-sm leading-relaxed text-slate-400">{{ __('Plateforme de gestion des centres de formation') }}</p>
            </div>

            <nav aria-label="{{ __('Navigation') }}">
                <h3 class="text-sm font-semibold uppercase tracking-wider text-white">{{ __('Navigation') }}</h3>
                <ul class="mt-4 space-y-2.5 text-sm">
                    <li><a href="{{ route('home') }}" class="transition hover:text-white">{{ __('Accueil') }}</a></li>
                    <li><a href="{{ route('public.centers') }}" class="transition hover:text-white">{{ __('Centres') }}</a></li>
                    <li><a href="{{ route('public.formations') }}" class="transition hover:text-white">{{ __('Formations') }}</a></li>
                    <li><a href="{{ route('public.announcements') }}" class="transition hover:text-white">{{ __('Actualités') }}</a></li>
                </ul>
            </nav>

            <nav aria-label="{{ __('Liens utiles') }}">
                <h3 class="text-sm font-semibold uppercase tracking-wider text-white">{{ __('Liens utiles') }}</h3>
                <ul class="mt-4 space-y-2.5 text-sm">
                    <li><a href="{{ route('public.apply') }}" class="transition hover:text-white">{{ __('Candidature') }}</a></li>
                    <li><a href="{{ route('public.contact') }}" class="transition hover:text-white">{{ __('Contact') }}</a></li>
                    <li><a href="{{ route('public.verify-certificate') }}" class="transition hover:text-white">{{ __('Vérifier un document') }}</a></li>
                    <li><a href="{{ route('login') }}" class="transition hover:text-white">{{ __('Connexion') }}</a></li>
                </ul>
            </nav>

            <div>
                <h3 class="text-sm font-semibold uppercase tracking-wider text-white">{{ __('Contact') }}</h3>
                <p class="mt-4 text-sm leading-relaxed text-slate-400">{{ __('Une question ? Notre équipe vous répond.') }}</p>
                <a href="{{ route('public.contact') }}" class="ec-btn-primary mt-4 !bg-white !text-slate-900 hover:!bg-slate-100">
                    {{ __('Nous contacter') }}
                    <x-icon name="arrow-right" class="h-4 w-4 rtl:-scale-x-100" />
                </a>
            </div>
        </div>

        <div class="border-t border-slate-800/80">
            <div class="mx-auto flex max-w-7xl flex-col items-center justify-between gap-3 px-4 py-6 text-sm text-slate-500 sm:flex-row sm:px-6 lg:px-8">
                <p>&copy; {{ date('Y') }} {{ __('Education Centers') }}. {{ __('Tous droits réservés.') }}</p>
                <div class="flex items-center gap-5">
                    <a href="{{ route('public.contact') }}" class="transition hover:text-white">{{ __('Contact') }}</a>
                    <a href="{{ route('public.verify-certificate') }}" class="transition hover:text-white">{{ __('Vérifier un document') }}</a>
                </div>
            </div>
        </div>
    </footer>

</body>
</html>
