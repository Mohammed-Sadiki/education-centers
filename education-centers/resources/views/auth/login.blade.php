@extends('layouts.guest')

@section('title', __('Connexion') . ' — ' . config('app.name'))

@section('content')
<div class="relative w-full max-w-5xl">
    {{-- Carte Login Glassmorphic Premium --}}
    <div class="animate-ec-fade-up relative overflow-hidden rounded-[2rem] border border-white/60 bg-white/80 p-3 shadow-[0_24px_70px_-15px_rgba(99,102,241,0.22)] backdrop-blur-2xl transition-all duration-300 sm:p-5 lg:p-6 dark:border-white/10 dark:bg-slate-900/80 dark:shadow-[0_24px_70px_-15px_rgba(0,0,0,0.7)]">
        
        <div class="grid grid-cols-1 gap-6 lg:grid-cols-12 lg:gap-8">
            
            {{-- ──────────────────────────────────────────────────────────────────
                 COLONNE GAUCHE — FORMULAIRE DE CONNEXION
                 ────────────────────────────────────────────────────────────────── --}}
            <div class="flex flex-col justify-between p-4 sm:p-6 lg:col-span-7 xl:p-8">
                <div>
                    {{-- En-tête : Logo & Contrôles (Langue + Thème) --}}
                    <div class="flex items-center justify-between gap-3">
                        <a href="{{ route('home') }}" class="group flex items-center gap-2.5 transition hover:opacity-90">
                            <span class="flex h-10 w-10 items-center justify-center rounded-xl bg-gradient-to-br from-brand-500 to-brand-700 text-white shadow-md shadow-brand-600/30 transition group-hover:scale-105">
                                <x-icon name="academic" class="h-5 w-5" />
                            </span>
                            <span class="text-lg font-bold tracking-tight text-slate-900 dark:text-white">
                                {{ __('Education Centers') }}
                            </span>
                        </a>

                        <div class="flex items-center gap-2">
                            @include('layouts.partials.language-switcher')
                            <button type="button"
                                    class="rounded-xl p-2 text-slate-500 transition hover:bg-slate-100 hover:text-slate-800 dark:text-slate-400 dark:hover:bg-slate-800/80 dark:hover:text-slate-200"
                                    x-on:click="$store.ui.toggleTheme()"
                                    aria-label="{{ __('Basculer le thème') }}">
                                <x-icon name="moon" class="h-5 w-5" x-show="!$store.ui.dark" />
                                <x-icon name="sun" class="h-5 w-5" x-show="$store.ui.dark" x-cloak />
                            </button>
                        </div>
                    </div>

                    {{-- Titre et sous-titre --}}
                    <div class="mt-8">
                        <h1 class="text-3xl font-extrabold tracking-tight text-slate-900 sm:text-4xl dark:text-white">
                            {{ __('Bienvenue') }}
                        </h1>
                        <p class="mt-2 text-sm text-slate-500 sm:text-base dark:text-slate-400">
                            {{ __('Connectez-vous à votre compte') }}
                        </p>
                    </div>

                    {{-- Alertes d'erreurs de validation --}}
                    @if ($errors->any())
                        <div class="mt-6 rounded-2xl border border-red-200/80 bg-red-50/80 p-4 text-sm text-red-700 backdrop-blur-md dark:border-red-900/60 dark:bg-red-950/40 dark:text-red-300">
                            <div class="flex items-start gap-3">
                                <x-icon name="warning" class="mt-0.5 h-5 w-5 shrink-0 text-red-500" />
                                <ul class="space-y-1">
                                    @foreach ($errors->all() as $error)
                                        <li>{{ $error }}</li>
                                    @endforeach
                                </ul>
                            </div>
                        </div>
                    @endif

                    {{-- Formulaire de connexion --}}
                    <form method="POST" action="{{ route('login') }}" novalidate class="mt-7 space-y-5" x-data="{ loading: false }" x-on:submit="loading = true">
                        @csrf

                        {{-- Champ Adresse e-mail --}}
                        <div>
                            <label for="email" class="block text-xs font-semibold uppercase tracking-wider text-slate-600 dark:text-slate-300">
                                {{ __('Adresse e-mail') }}
                            </label>
                            <div class="relative mt-2">
                                <span class="pointer-events-none absolute inset-y-0 start-0 flex items-center ps-3.5 text-slate-400 dark:text-slate-500">
                                    <x-icon name="mail" class="h-5 w-5" />
                                </span>
                                <input type="email"
                                       id="email"
                                       name="email"
                                       value="{{ old('email') }}"
                                       required
                                       autocomplete="email"
                                       autofocus
                                       placeholder="{{ __('Adresse e-mail') }}"
                                       class="w-full rounded-xl border border-slate-200/90 bg-white/70 py-3.5 ps-11 pe-4 text-sm text-slate-900 placeholder:text-slate-400 transition focus:border-brand-500 focus:bg-white focus:outline-none focus:ring-4 focus:ring-brand-500/15 dark:border-slate-700/80 dark:bg-slate-800/60 dark:text-white dark:placeholder:text-slate-500 dark:focus:border-brand-400 dark:focus:bg-slate-800 @error('email') is-invalid !border-red-500 !ring-red-500/20 @enderror">
                            </div>
                            @error('email')
                                <p class="mt-1.5 text-xs text-red-600 dark:text-red-400">{{ $message }}</p>
                            @enderror
                        </div>

                        {{-- Champ Mot de passe avec toggle Afficher/Masquer --}}
                        <div x-data="{ show: false }">
                            <label for="password" class="block text-xs font-semibold uppercase tracking-wider text-slate-600 dark:text-slate-300">
                                {{ __('Mot de passe') }}
                            </label>
                            <div class="relative mt-2">
                                <span class="pointer-events-none absolute inset-y-0 start-0 flex items-center ps-3.5 text-slate-400 dark:text-slate-500">
                                    <x-icon name="lock" class="h-5 w-5" />
                                </span>
                                <input :type="show ? 'text' : 'password'"
                                       id="password"
                                       name="password"
                                       required
                                       autocomplete="current-password"
                                       placeholder="{{ __('Mot de passe') }}"
                                       class="w-full rounded-xl border border-slate-200/90 bg-white/70 py-3.5 ps-11 pe-12 text-sm text-slate-900 placeholder:text-slate-400 transition focus:border-brand-500 focus:bg-white focus:outline-none focus:ring-4 focus:ring-brand-500/15 dark:border-slate-700/80 dark:bg-slate-800/60 dark:text-white dark:placeholder:text-slate-500 dark:focus:border-brand-400 dark:focus:bg-slate-800 @error('password') is-invalid !border-red-500 !ring-red-500/20 @enderror">
                                <button type="button"
                                        x-on:click="show = !show"
                                        class="absolute inset-y-0 end-0 flex items-center pe-3.5 text-slate-400 transition hover:text-slate-600 dark:text-slate-500 dark:hover:text-slate-300"
                                        :aria-label="show ? '{{ __('Masquer le mot de passe') }}' : '{{ __('Afficher le mot de passe') }}'">
                                    <x-icon name="eye" class="h-5 w-5" x-show="!show" />
                                    <x-icon name="eye-off" class="h-5 w-5" x-show="show" x-cloak />
                                </button>
                            </div>
                            @error('password')
                                <p class="mt-1.5 text-xs text-red-600 dark:text-red-400">{{ $message }}</p>
                            @enderror
                        </div>

                        {{-- Options : Se souvenir de moi & Mot de passe oublié --}}
                        <div class="flex items-center justify-between gap-3 pt-1 text-sm">
                            <label class="flex cursor-pointer items-center gap-2.5 text-slate-600 select-none dark:text-slate-300">
                                <input type="checkbox"
                                       id="remember"
                                       name="remember"
                                       class="h-4 w-4 rounded border-slate-300 text-brand-600 transition focus:ring-brand-500/30 dark:border-slate-600 dark:bg-slate-800"
                                       {{ old('remember') ? 'checked' : '' }}>
                                <span class="text-xs font-medium sm:text-sm">{{ __('Se souvenir de moi') }}</span>
                            </label>
                            <a href="{{ route('public.contact') }}" class="text-xs font-semibold text-brand-600 transition hover:text-brand-700 hover:underline sm:text-sm dark:text-brand-400 dark:hover:text-brand-300">
                                {{ __('Mot de passe oublié ?') }}
                            </a>
                        </div>

                        {{-- Bouton principal Se connecter --}}
                        <button type="submit"
                                class="group relative flex w-full items-center justify-center gap-2 rounded-xl bg-gradient-to-r from-brand-600 via-indigo-600 to-brand-700 px-6 py-3.5 text-sm font-bold text-white shadow-lg shadow-brand-600/30 transition hover:-translate-y-0.5 hover:shadow-brand-600/40 active:translate-y-0 disabled:cursor-not-allowed disabled:opacity-60"
                                :disabled="loading">
                            <span x-show="!loading" class="flex items-center gap-2">
                                {{ __('Se connecter') }}
                                <x-icon name="arrow-right" class="h-4 w-4 transition-transform group-hover:translate-x-1 rtl:group-hover:-translate-x-1 rtl:-scale-x-100" />
                            </span>
                            <span x-show="loading" x-cloak class="flex items-center gap-2">
                                <svg class="h-4 w-4 animate-spin" viewBox="0 0 24 24" fill="none">
                                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                                </svg>
                                {{ __('Connexion en cours…') }}
                            </span>
                        </button>
                    </form>

                    {{-- Lien d'inscription --}}
                    <div class="mt-8 text-center text-xs text-slate-500 sm:text-sm dark:text-slate-400">
                        {{ __('Vous n\'avez pas de compte ?') }}
                        <a href="{{ route('public.apply') }}" class="font-bold text-brand-600 transition hover:text-brand-700 hover:underline dark:text-brand-400 dark:hover:text-brand-300">
                            {{ __('S\'inscrire') }}
                        </a>
                    </div>
                </div>

                {{-- Lien retour accueil au bas du formulaire --}}
                <div class="mt-6 pt-4 text-center border-t border-slate-100 dark:border-slate-800/80">
                    <a href="{{ route('home') }}" class="inline-flex items-center gap-1.5 text-xs font-semibold text-slate-400 transition hover:text-brand-600 dark:hover:text-brand-400">
                        <x-icon name="arrow-uturn-left" class="h-3.5 w-3.5 rtl:-scale-x-100" />
                        {{ __('Retour au site public') }}
                    </a>
                </div>
            </div>

            {{-- ──────────────────────────────────────────────────────────────────
                 COLONNE DROITE — PRÉSENTATION & AMBIANCE ACADÉMIQUE PREMIUM
                 ────────────────────────────────────────────────────────────────── --}}
            <div class="relative hidden flex-col justify-between overflow-hidden rounded-[1.75rem] border border-brand-100/60 bg-gradient-to-br from-brand-50/70 via-indigo-50/50 to-purple-50/70 p-8 lg:col-span-5 lg:flex xl:p-10 dark:border-white/10 dark:from-slate-800/60 dark:via-slate-900/70 dark:to-brand-950/40 backdrop-blur-xl">
                
                {{-- Lueur diffuse d'arrière-plan --}}
                <div class="pointer-events-none absolute -top-24 -end-24 h-64 w-64 rounded-full bg-brand-400/20 blur-3xl dark:bg-brand-500/15" aria-hidden="true"></div>
                <div class="pointer-events-none absolute -bottom-24 -start-24 h-64 w-64 rounded-full bg-pink-400/20 blur-3xl dark:bg-pink-500/15" aria-hidden="true"></div>

                <div class="relative">
                    {{-- Illustration 3D : Toque académique lumineuse (Mortarboard néon 3D) --}}
                    <div class="relative mx-auto flex h-32 w-32 items-center justify-center">
                        <div class="absolute inset-0 rounded-full bg-gradient-to-tr from-brand-500/30 via-indigo-500/25 to-pink-500/20 blur-2xl dark:from-brand-500/40 dark:via-indigo-500/35 dark:to-pink-500/30" aria-hidden="true"></div>
                        <svg class="relative h-28 w-28 drop-shadow-[0_12px_24px_rgba(99,102,241,0.35)]" viewBox="0 0 120 120" fill="none" xmlns="http://www.w3.org/2000/svg">
                            <defs>
                                <linearGradient id="capTop" x1="15" y1="35" x2="105" y2="55" gradientUnits="userSpaceOnUse">
                                    <stop offset="0%" stop-color="#38bdf8" stop-opacity="0.9" />
                                    <stop offset="45%" stop-color="#818cf8" stop-opacity="0.95" />
                                    <stop offset="100%" stop-color="#c084fc" stop-opacity="0.9" />
                                </linearGradient>
                                <linearGradient id="capLeft" x1="15" y1="45" x2="60" y2="70" gradientUnits="userSpaceOnUse">
                                    <stop offset="0%" stop-color="#2563eb" stop-opacity="0.85" />
                                    <stop offset="100%" stop-color="#6366f1" stop-opacity="0.95" />
                                </linearGradient>
                                <linearGradient id="capRight" x1="60" y1="70" x2="105" y2="45" gradientUnits="userSpaceOnUse">
                                    <stop offset="0%" stop-color="#7c3aed" stop-opacity="0.9" />
                                    <stop offset="100%" stop-color="#db2777" stop-opacity="0.8" />
                                </linearGradient>
                                <linearGradient id="capBase" x1="35" y1="65" x2="85" y2="95" gradientUnits="userSpaceOnUse">
                                    <stop offset="0%" stop-color="#1e1b4b" stop-opacity="0.8" />
                                    <stop offset="50%" stop-color="#4338ca" stop-opacity="0.9" />
                                    <stop offset="100%" stop-color="#6d28d9" stop-opacity="0.85" />
                                </linearGradient>
                                <linearGradient id="tasselGrad" x1="60" y1="45" x2="25" y2="85" gradientUnits="userSpaceOnUse">
                                    <stop offset="0%" stop-color="#f472b6" />
                                    <stop offset="100%" stop-color="#38bdf8" />
                                </linearGradient>
                            </defs>
                            {{-- Base calotte sous le chapeau --}}
                            <path d="M38 56 C38 76, 82 76, 82 56 L82 66 C82 86, 38 86, 38 66 Z" fill="url(#capBase)" />
                            <path d="M42 66 C42 82, 78 82, 78 66" stroke="#818cf8" stroke-width="1.5" stroke-linecap="round" opacity="0.6" />
                            {{-- Tranche d'épaisseur du losange en 3D --}}
                            <path d="M15 45 L60 67 L60 71 L15 49 Z" fill="url(#capLeft)" />
                            <path d="M60 67 L105 45 L105 49 L60 71 Z" fill="url(#capRight)" />
                            {{-- Plateau losange supérieur --}}
                            <path d="M60 23 L105 45 L60 67 L15 45 Z" fill="url(#capTop)" stroke="rgba(255,255,255,0.7)" stroke-width="1.5" stroke-linejoin="round" />
                            {{-- Reflet spéculaire de verre --}}
                            <path d="M60 26 L95 43 L60 49 L25 43 Z" fill="rgba(255,255,255,0.22)" />
                            {{-- Bouton central et cordon/tassel --}}
                            <ellipse cx="60" cy="45" rx="3.5" ry="2" fill="#ffffff" />
                            <path d="M60 45 C45 48, 30 60, 26 78" stroke="url(#tasselGrad)" stroke-width="2.5" stroke-linecap="round" fill="none" />
                            <ellipse cx="25" cy="80" rx="3" ry="5" fill="#f472b6" />
                        </svg>
                    </div>

                    {{-- Titre & Description de la présentation --}}
                    <div class="mt-6 text-center">
                        <h2 class="text-xl font-bold tracking-tight text-slate-900 xl:text-2xl dark:text-white">
                            {{ __('Formez aujourd\'hui') }}<br>
                            <span class="text-brand-600 dark:text-brand-400">{{ __('les talents de demain') }}</span>
                        </h2>
                        <p class="mt-3 text-xs leading-relaxed text-slate-600 sm:text-sm dark:text-slate-300">
                            {{ __('Accédez à des formations de qualité dans nos centres, avec un accompagnement personnalisé.') }}
                        </p>
                    </div>

                    {{-- Les 3 avantages clés avec badges stylisés --}}
                    <div class="mt-8 space-y-3.5">
                        <div class="flex items-center gap-3.5 rounded-2xl bg-white/70 p-3 shadow-sm ring-1 ring-slate-900/5 backdrop-blur-md transition hover:-translate-y-0.5 hover:bg-white dark:bg-slate-800/60 dark:ring-white/10 dark:hover:bg-slate-800/80">
                            <span class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl bg-brand-500/10 text-brand-600 dark:bg-brand-400/20 dark:text-brand-300">
                                <x-icon name="academic" class="h-5 w-5" />
                            </span>
                            <span class="text-xs font-semibold text-slate-800 sm:text-sm dark:text-slate-100">
                                {{ __('Formations certifiantes') }}
                            </span>
                        </div>

                        <div class="flex items-center gap-3.5 rounded-2xl bg-white/70 p-3 shadow-sm ring-1 ring-slate-900/5 backdrop-blur-md transition hover:-translate-y-0.5 hover:bg-white dark:bg-slate-800/60 dark:ring-white/10 dark:hover:bg-slate-800/80">
                            <span class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl bg-indigo-500/10 text-indigo-600 dark:bg-indigo-400/20 dark:text-indigo-300">
                                <x-icon name="building" class="h-5 w-5" />
                            </span>
                            <span class="text-xs font-semibold text-slate-800 sm:text-sm dark:text-slate-100">
                                {{ __('Centres modernes') }}
                            </span>
                        </div>

                        <div class="flex items-center gap-3.5 rounded-2xl bg-white/70 p-3 shadow-sm ring-1 ring-slate-900/5 backdrop-blur-md transition hover:-translate-y-0.5 hover:bg-white dark:bg-slate-800/60 dark:ring-white/10 dark:hover:bg-slate-800/80">
                            <span class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl bg-pink-500/10 text-pink-600 dark:bg-pink-400/20 dark:text-pink-300">
                                <x-icon name="chart" class="h-5 w-5" />
                            </span>
                            <span class="text-xs font-semibold text-slate-800 sm:text-sm dark:text-slate-100">
                                {{ __('Accompagnement personnalisé') }}
                            </span>
                        </div>
                    </div>
                </div>

                {{-- Badges statistiques discrets au pied du panneau --}}
                <div class="relative mt-8 pt-5 border-t border-brand-200/50 dark:border-white/10">
                    <div class="grid grid-cols-3 gap-2 text-center">
                        <div>
                            <p class="text-base font-extrabold text-slate-900 sm:text-lg dark:text-white">{{ format_number($stats['formations']) }}</p>
                            <p class="text-[10px] font-medium text-slate-500 uppercase tracking-wider dark:text-slate-400">{{ __('Formations') }}</p>
                        </div>
                        <div>
                            <p class="text-base font-extrabold text-slate-900 sm:text-lg dark:text-white">{{ format_number($stats['students']) }}</p>
                            <p class="text-[10px] font-medium text-slate-500 uppercase tracking-wider dark:text-slate-400">{{ __('Étudiants') }}</p>
                        </div>
                        <div>
                            <p class="text-base font-extrabold text-slate-900 sm:text-lg dark:text-white">{{ format_number($stats['centers']) }}</p>
                            <p class="text-[10px] font-medium text-slate-500 uppercase tracking-wider dark:text-slate-400">{{ __('Centres') }}</p>
                        </div>
                    </div>
                </div>

            </div>

        </div>
    </div>
</div>
@endsection
