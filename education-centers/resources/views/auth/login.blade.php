@extends('layouts.guest')

@section('title', __('Connexion') . ' — ' . config('app.name'))

@php
    $loginFeatures = [
        ['icon' => 'users', 'chip' => 'bg-brand-50 text-brand-600 dark:bg-brand-500/15 dark:text-brand-300', 'title' => __('Multi-centres'), 'text' => __('Gérez plusieurs centres depuis une seule plateforme.')],
        ['icon' => 'chart', 'chip' => 'bg-emerald-50 text-emerald-600 dark:bg-emerald-500/15 dark:text-emerald-400', 'title' => __('Suivi complet'), 'text' => __('Étudiants, formations, paiements et rapports en temps réel.')],
        ['icon' => 'shield', 'chip' => 'bg-amber-50 text-amber-600 dark:bg-amber-500/15 dark:text-amber-400', 'title' => __('Sécurité avancée'), 'text' => __('Vos données sont protégées et accessibles en toute sécurité.')],
    ];
@endphp

@section('content')
<div class="flex min-h-screen bg-white dark:bg-slate-950">
    {{-- ───────── Colonne gauche : présentation de la plateforme ───────── --}}
    <div class="relative hidden w-[55%] flex-col justify-between gap-10 overflow-hidden bg-slate-50 p-12 xl:w-3/5 xl:p-16 lg:flex dark:bg-slate-900/50">
        <div class="pointer-events-none absolute -top-40 -end-40 h-[30rem] w-[30rem] rounded-full border-[2.5rem] border-brand-100/70 dark:border-brand-500/10" aria-hidden="true"></div>
        <div class="pointer-events-none absolute -bottom-24 -start-24 h-80 w-80 rounded-full border-[1.75rem] border-brand-100/50 dark:border-brand-500/10" aria-hidden="true"></div>

        <a href="{{ route('home') }}" class="relative flex items-center gap-3">
            <span class="flex h-12 w-12 items-center justify-center rounded-2xl bg-gradient-to-br from-brand-500 to-brand-700 text-white shadow-lg shadow-brand-600/30">
                <x-icon name="academic" class="h-6 w-6" />
            </span>
            <span>
                <span class="block text-lg font-bold tracking-tight text-slate-900 dark:text-white">{{ __('Education Centers') }}</span>
                <span class="block text-xs font-medium text-slate-500 dark:text-slate-400">{{ __('Plateforme de gestion des centres de formation') }}</span>
            </span>
        </a>

        <div class="relative max-w-xl">
            <h1 class="text-4xl leading-[1.12] font-extrabold tracking-tight text-slate-900 xl:text-5xl dark:text-white">
                {{ __('Simplifiez la gestion') }}
                <span class="block text-brand-600">{{ __('de vos centres') }}</span>
            </h1>
            <p class="mt-5 text-lg leading-relaxed text-slate-500 dark:text-slate-400">{{ __('Une solution complète pour gérer vos étudiants, formations, paiements et équipes, en toute simplicité.') }}</p>

            <ul class="mt-9 space-y-6">
                @foreach($loginFeatures as $feature)
                    <li class="flex items-start gap-4">
                        <span class="flex h-12 w-12 shrink-0 items-center justify-center rounded-2xl {{ $feature['chip'] }}">
                            <x-icon name="{{ $feature['icon'] }}" class="h-6 w-6" />
                        </span>
                        <span>
                            <span class="block font-bold text-slate-900 dark:text-white">{{ $feature['title'] }}</span>
                            <span class="mt-0.5 block text-sm leading-relaxed text-slate-500 dark:text-slate-400">{{ $feature['text'] }}</span>
                        </span>
                    </li>
                @endforeach
            </ul>
        </div>

        <div class="relative">
            <div class="relative overflow-hidden rounded-[2rem] shadow-2xl shadow-brand-900/15 ring-1 ring-slate-900/5 dark:ring-white/10">
                <img src="{{ asset('images/landing/campus-login.jpg') }}" alt="{{ __('Campus moderne Education Centers') }}" class="h-60 w-full object-cover xl:h-64" width="1536" height="1024">
                <div class="absolute inset-0 bg-gradient-to-t from-brand-900/20 to-transparent" aria-hidden="true"></div>
            </div>

            <div class="absolute -bottom-8 start-6 rounded-2xl bg-white/95 p-5 shadow-2xl ring-1 ring-slate-900/5 backdrop-blur xl:start-10 dark:bg-slate-900/95 dark:ring-white/10">
                <dl class="grid grid-cols-3 gap-6 text-center">
                    <div class="flex flex-col items-center">
                        <dd class="order-1 flex items-center gap-1.5 text-xl font-extrabold text-slate-900 xl:text-2xl dark:text-white">
                            <x-icon name="book" class="h-4 w-4 text-brand-500" />
                            {{ format_number($stats['formations']) }}
                        </dd>
                        <dt class="order-2 mt-1 max-w-[7rem] text-[11px] leading-tight font-medium text-slate-500 dark:text-slate-400">{{ __('Formations de qualité') }}</dt>
                    </div>
                    <div class="flex flex-col items-center">
                        <dd class="order-1 flex items-center gap-1.5 text-xl font-extrabold text-slate-900 xl:text-2xl dark:text-white">
                            <x-icon name="users" class="h-4 w-4 text-brand-500" />
                            {{ format_number($stats['students']) }}
                        </dd>
                        <dt class="order-2 mt-1 max-w-[7rem] text-[11px] leading-tight font-medium text-slate-500 dark:text-slate-400">{{ __('Étudiants accompagnés') }}</dt>
                    </div>
                    <div class="flex flex-col items-center">
                        <dd class="order-1 flex items-center gap-1.5 text-xl font-extrabold text-slate-900 xl:text-2xl dark:text-white">
                            <x-icon name="building" class="h-4 w-4 text-brand-500" />
                            {{ format_number($stats['centers']) }}
                        </dd>
                        <dt class="order-2 mt-1 max-w-[7rem] text-[11px] leading-tight font-medium text-slate-500 dark:text-slate-400">{{ __('Centres en Europe') }}</dt>
                    </div>
                </dl>
            </div>
        </div>

        <figure class="relative mt-4 flex items-start gap-4 rounded-2xl bg-white/90 p-5 ring-1 ring-slate-900/5 dark:bg-slate-900/90 dark:ring-white/10">
            <span class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl bg-brand-50 text-brand-600 dark:bg-brand-500/15 dark:text-brand-300">
                <x-icon name="quote" class="h-5 w-5" />
            </span>
            <div>
                <blockquote class="text-sm leading-relaxed font-semibold text-slate-700 italic dark:text-slate-200">{{ __('« Une éducation de qualité pour un meilleur avenir. »') }}</blockquote>
                <figcaption class="mt-1 text-xs font-medium text-slate-400">{{ __('Education Centers') }}</figcaption>
            </div>
        </figure>
    </div>

    {{-- ───────── Colonne droite : connexion ───────── --}}
    <div class="flex flex-1 items-center justify-center px-4 py-12 sm:px-8">
        <div class="w-full max-w-md">
            <div class="rounded-3xl border border-slate-200/80 bg-white p-7 shadow-[0_24px_70px_-24px_rgba(79,70,229,0.3)] sm:p-10 dark:border-slate-800 dark:bg-slate-900">
                <div class="text-center">
                    <span class="mx-auto flex h-14 w-14 items-center justify-center rounded-2xl bg-gradient-to-br from-brand-500 to-brand-700 text-white shadow-lg shadow-brand-600/30">
                        <x-icon name="academic" class="h-7 w-7" />
                    </span>
                    <h1 class="mt-5 text-2xl font-bold tracking-tight text-slate-900 dark:text-white">{{ __('Education Centers') }}</h1>
                    <p class="mt-1.5 text-sm text-slate-500 dark:text-slate-400">{{ __('Connexion à votre espace') }}</p>
                </div>

                @if ($errors->any())
                    <div class="mt-6 rounded-xl border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700 dark:border-red-900 dark:bg-red-950/40 dark:text-red-300">
                        <ul class="list-disc ps-4">
                            @foreach ($errors->all() as $error)
                                <li>{{ $error }}</li>
                            @endforeach
                        </ul>
                    </div>
                @endif

                <form method="POST" action="{{ route('login') }}" novalidate class="mt-7 space-y-5" x-data="{ loading: false }" x-on:submit="loading = true">
                    @csrf

                    <div>
                        <label for="email" class="ec-label">{{ __('Adresse e-mail') }}</label>
                        <div class="relative">
                            <span class="pointer-events-none absolute inset-y-0 start-0 flex items-center ps-3.5 text-slate-400">
                                <x-icon name="mail" class="h-5 w-5" />
                            </span>
                            <input type="email" id="email" name="email" value="{{ old('email') }}" required autocomplete="email" autofocus
                                   placeholder="votre.email@exemple.com"
                                   class="ec-input !rounded-xl !py-3 ps-11 @error('email') is-invalid @enderror">
                        </div>
                        @error('email')<p class="ec-error">{{ $message }}</p>@enderror
                    </div>

                    <div x-data="{ show: false }">
                        <label for="password" class="ec-label">{{ __('Mot de passe') }}</label>
                        <div class="relative">
                            <span class="pointer-events-none absolute inset-y-0 start-0 flex items-center ps-3.5 text-slate-400">
                                <x-icon name="lock" class="h-5 w-5" />
                            </span>
                            <input :type="show ? 'text' : 'password'" id="password" name="password" required autocomplete="current-password"
                                   placeholder="{{ __('Votre mot de passe') }}"
                                   class="ec-input !rounded-xl !py-3 ps-11 pe-12 @error('password') is-invalid @enderror">
                            <button type="button"
                                    x-on:click="show = !show"
                                    class="absolute inset-y-0 end-0 flex items-center pe-3.5 text-slate-400 transition hover:text-slate-600"
                                    :aria-label="show ? '{{ __('Masquer le mot de passe') }}' : '{{ __('Afficher le mot de passe') }}'">
                                <x-icon name="eye" class="h-5 w-5" x-show="!show" />
                                <x-icon name="eye-off" class="h-5 w-5" x-show="show" x-cloak />
                            </button>
                        </div>
                        @error('password')<p class="ec-error">{{ $message }}</p>@enderror
                    </div>

                    <div class="flex items-center justify-between gap-3 text-sm">
                        <label class="flex cursor-pointer items-center gap-2 text-slate-600 dark:text-slate-300">
                            <input type="checkbox" id="remember" name="remember" class="h-4 w-4 rounded border-slate-300 text-brand-600 focus:ring-brand-500/30 dark:border-slate-600 dark:bg-slate-800" {{ old('remember') ? 'checked' : '' }}>
                            {{ __('Se souvenir de moi') }}
                        </label>
                        <a href="{{ route('public.contact') }}" class="font-semibold text-brand-600 transition hover:text-brand-700">{{ __('Mot de passe oublié ?') }}</a>
                    </div>

                    <button type="submit" class="flex w-full items-center justify-center gap-2 rounded-xl bg-brand-600 px-6 py-3.5 text-sm font-bold text-white shadow-lg shadow-brand-600/30 transition hover:-translate-y-0.5 hover:bg-brand-700 disabled:cursor-not-allowed disabled:opacity-60" :disabled="loading">
                        <span x-show="!loading" class="flex items-center gap-2">
                            {{ __('Se connecter') }}
                            <x-icon name="arrow-right" class="h-4 w-4 rtl:-scale-x-100" />
                        </span>
                        <span x-show="loading" x-cloak>{{ __('Connexion en cours…') }}</span>
                    </button>
                </form>

                <p class="mt-7 text-center text-sm text-slate-500 dark:text-slate-400">
                    {{ __('Vous n\'avez pas de compte ?') }}
                    <a href="{{ route('public.contact') }}" class="font-bold text-brand-600 transition hover:text-brand-700">{{ __('Nous contacter') }}</a>
                </p>
            </div>

            <p class="mt-6 text-center">
                <a href="{{ route('home') }}" class="inline-flex items-center gap-1.5 text-sm font-semibold text-slate-400 transition hover:text-brand-600">
                    <x-icon name="arrow-uturn-left" class="h-4 w-4 rtl:-scale-x-100" />
                    {{ __('Retour au site') }}
                </a>
            </p>
        </div>
    </div>
</div>
@endsection
