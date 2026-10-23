@extends('layouts.portal')

@section('title', __('Sélection du domaine') . ' — ' . config('app.name'))
@section('description', __('Choisissez le domaine d\'activité à administrer.'))

@php
    /*
    | Palette de classes Tailwind littérales par couleur de domaine.
    | Les chaînes complètes sont écrites en dur pour que le scanner Tailwind
    | les génère (les classes dynamiques du type bg-{$c}-500 ne le seraient pas).
    */
    $palette = [
        'emerald' => [
            'card'   => 'border-emerald-200/70 bg-emerald-50/40 hover:border-emerald-300 dark:border-emerald-400/20 dark:bg-emerald-400/[0.06] dark:hover:border-emerald-400/40',
            'chip'   => 'bg-gradient-to-br from-emerald-400 to-emerald-600 shadow-emerald-500/30',
            'badge'  => 'bg-emerald-100 text-emerald-700 dark:bg-emerald-500/15 dark:text-emerald-300',
            'dot'    => 'bg-emerald-500',
            'title'  => 'group-hover:text-emerald-700 dark:group-hover:text-emerald-300',
            'cta'    => 'bg-emerald-100/70 text-emerald-700 dark:bg-emerald-500/10 dark:text-emerald-300',
            'circle' => 'bg-emerald-600 text-white',
        ],
        'blue' => [
            'card'   => 'border-blue-200/70 bg-blue-50/40 hover:border-blue-300 dark:border-blue-400/20 dark:bg-blue-400/[0.06] dark:hover:border-blue-400/40',
            'chip'   => 'bg-gradient-to-br from-blue-400 to-blue-600 shadow-blue-500/30',
            'badge'  => 'bg-blue-100 text-blue-700 dark:bg-blue-500/15 dark:text-blue-300',
            'dot'    => 'bg-blue-500',
            'title'  => 'group-hover:text-blue-700 dark:group-hover:text-blue-300',
            'cta'    => 'bg-blue-100/70 text-blue-700 dark:bg-blue-500/10 dark:text-blue-300',
            'circle' => 'bg-blue-600 text-white',
        ],
        'amber' => [
            'card'   => 'border-amber-200/70 bg-amber-50/40 hover:border-amber-300 dark:border-amber-400/20 dark:bg-amber-400/[0.06] dark:hover:border-amber-400/40',
            'chip'   => 'bg-gradient-to-br from-amber-400 to-amber-600 shadow-amber-500/30',
            'badge'  => 'bg-amber-100 text-amber-800 dark:bg-amber-500/15 dark:text-amber-300',
            'dot'    => 'bg-amber-500',
            'title'  => 'group-hover:text-amber-700 dark:group-hover:text-amber-300',
            'cta'    => 'bg-amber-100/70 text-amber-800 dark:bg-amber-500/10 dark:text-amber-300',
            'circle' => 'bg-amber-500 text-white',
        ],
        'orange' => [
            'card'   => 'border-orange-200/70 bg-orange-50/40 hover:border-orange-300 dark:border-orange-400/20 dark:bg-orange-400/[0.06] dark:hover:border-orange-400/40',
            'chip'   => 'bg-gradient-to-br from-orange-400 to-orange-600 shadow-orange-500/30',
            'badge'  => 'bg-orange-100 text-orange-700 dark:bg-orange-500/15 dark:text-orange-300',
            'dot'    => 'bg-orange-500',
            'title'  => 'group-hover:text-orange-700 dark:group-hover:text-orange-300',
            'cta'    => 'bg-orange-100/70 text-orange-700 dark:bg-orange-500/10 dark:text-orange-300',
            'circle' => 'bg-orange-600 text-white',
        ],
        'violet' => [
            'card'   => 'border-violet-200/70 bg-violet-50/40 hover:border-violet-300 dark:border-violet-400/20 dark:bg-violet-400/[0.06] dark:hover:border-violet-400/40',
            'chip'   => 'bg-gradient-to-br from-violet-400 to-violet-600 shadow-violet-500/30',
            'badge'  => 'bg-violet-100 text-violet-700 dark:bg-violet-500/15 dark:text-violet-300',
            'dot'    => 'bg-violet-500',
            'title'  => 'group-hover:text-violet-700 dark:group-hover:text-violet-300',
            'cta'    => 'bg-violet-100/70 text-violet-700 dark:bg-violet-500/10 dark:text-violet-300',
            'circle' => 'bg-violet-600 text-white',
        ],
        'pink' => [
            'card'   => 'border-pink-200/70 bg-pink-50/40 hover:border-pink-300 dark:border-pink-400/20 dark:bg-pink-400/[0.06] dark:hover:border-pink-400/40',
            'chip'   => 'bg-gradient-to-br from-pink-400 to-pink-600 shadow-pink-500/30',
            'badge'  => 'bg-pink-100 text-pink-700 dark:bg-pink-500/15 dark:text-pink-300',
            'dot'    => 'bg-pink-500',
            'title'  => 'group-hover:text-pink-700 dark:group-hover:text-pink-300',
            'cta'    => 'bg-pink-100/70 text-pink-700 dark:bg-pink-500/10 dark:text-pink-300',
            'circle' => 'bg-pink-600 text-white',
        ],
        'indigo' => [
            'card'   => 'border-indigo-200/70 bg-indigo-50/40 hover:border-indigo-300 dark:border-indigo-400/20 dark:bg-indigo-400/[0.06] dark:hover:border-indigo-400/40',
            'chip'   => 'bg-gradient-to-br from-indigo-400 to-indigo-600 shadow-indigo-500/30',
            'badge'  => 'bg-indigo-100 text-indigo-700 dark:bg-indigo-500/15 dark:text-indigo-300',
            'dot'    => 'bg-indigo-500',
            'title'  => 'group-hover:text-indigo-700 dark:group-hover:text-indigo-300',
            'cta'    => 'bg-indigo-100/70 text-indigo-700 dark:bg-indigo-500/10 dark:text-indigo-300',
            'circle' => 'bg-indigo-600 text-white',
        ],
        'brand' => [
            'card'   => 'border-brand-200/70 bg-brand-50/40 hover:border-brand-300 dark:border-brand-400/20 dark:bg-brand-400/[0.06] dark:hover:border-brand-400/40',
            'chip'   => 'bg-gradient-to-br from-brand-400 to-brand-600 shadow-brand-500/30',
            'badge'  => 'bg-brand-100 text-brand-700 dark:bg-brand-500/15 dark:text-brand-300',
            'dot'    => 'bg-brand-500',
            'title'  => 'group-hover:text-brand-700 dark:group-hover:text-brand-300',
            'cta'    => 'bg-brand-100/70 text-brand-700 dark:bg-brand-500/10 dark:text-brand-300',
            'circle' => 'bg-brand-600 text-white',
        ],
    ];
    $p = fn (string $c): array => $palette[$c] ?? $palette['brand'];
@endphp

@section('content')

    {{-- ══════════════════════════════════════════════════════════════════
         HERO — titre + illustration 3D
    ═══════════════════════════════════════════════════════════════════ --}}
    <section class="relative mb-12 overflow-hidden rounded-3xl border border-slate-200/70 bg-gradient-to-br from-indigo-50 via-white to-brand-50 px-6 py-10 sm:px-10 sm:py-12 dark:border-slate-800 dark:from-slate-900 dark:via-slate-900 dark:to-brand-950/40">
        {{-- halos décoratifs --}}
        <div class="pointer-events-none absolute -top-24 -end-16 h-72 w-72 rounded-full bg-brand-300/30 blur-3xl dark:bg-brand-500/10"></div>
        <div class="pointer-events-none absolute -bottom-24 -start-10 h-72 w-72 rounded-full bg-indigo-300/20 blur-3xl dark:bg-indigo-500/10"></div>

        <div class="relative grid items-center gap-10 lg:grid-cols-2">
            {{-- Texte --}}
            <div class="text-center lg:text-start">
                <span class="inline-flex items-center gap-2 rounded-full border border-brand-200/70 bg-white/70 px-3.5 py-1 text-xs font-semibold text-brand-700 shadow-2xs backdrop-blur dark:border-brand-500/30 dark:bg-brand-500/10 dark:text-brand-300">
                    <svg class="h-3.5 w-3.5" fill="currentColor" viewBox="0 0 16 16" aria-hidden="true">
                        <rect x="1" y="1" width="6" height="6" rx="1.5"/><rect x="9" y="1" width="6" height="6" rx="1.5"/>
                        <rect x="1" y="9" width="6" height="6" rx="1.5"/><rect x="9" y="9" width="6" height="6" rx="1.5"/>
                    </svg>
                    {{ __('Espace Super Administrateur') }}
                </span>

                <h1 class="mt-4 text-4xl font-extrabold leading-[1.1] tracking-tight text-slate-900 sm:text-5xl dark:text-white">
                    {{ __('Sélectionnez votre domaine') }}
                </h1>
                <p class="mx-auto mt-4 max-w-xl text-base leading-relaxed text-slate-600 lg:mx-0 dark:text-slate-300">
                    {{ __('Bienvenue dans le portail d\'administration. Choisissez une filière de formation ou accédez aux modules de gestion administrative indépendants.') }}
                </p>
            </div>

            {{-- Illustration 3D (décorative) --}}
            <div class="relative mx-auto hidden h-72 w-full max-w-md lg:block" aria-hidden="true">
                {{-- anneaux concentriques --}}
                <div class="absolute left-1/2 top-1/2 h-64 w-64 -translate-x-1/2 -translate-y-1/2 rounded-full border border-brand-300/40 dark:border-brand-500/20"></div>
                <div class="absolute left-1/2 top-1/2 h-44 w-44 -translate-x-1/2 -translate-y-1/2 rounded-full border border-brand-300/50 dark:border-brand-500/25"></div>

                {{-- socle / cap 3D --}}
                <div class="absolute left-1/2 top-1/2 h-36 w-36 -translate-x-1/2 -translate-y-1/2">
                    <div class="absolute inset-0 rotate-12 rounded-[1.9rem] bg-gradient-to-br from-brand-400 via-brand-500 to-brand-700 shadow-2xl shadow-brand-600/40"></div>
                    <div class="absolute inset-0 rotate-12 rounded-[1.9rem] bg-white/10 [clip-path:polygon(0_0,100%_0,100%_50%,0_50%)]"></div>
                    <div class="absolute inset-0 rotate-12 rounded-[1.9rem] ring-1 ring-inset ring-white/25"></div>
                    <div class="absolute inset-0 flex items-center justify-center text-white">
                        <x-icon name="academic" class="h-16 w-16 -rotate-12" stroke-width="1.4" />
                    </div>
                </div>

                {{-- tuiles flottantes --}}
                <span class="absolute left-4 top-6 flex h-11 w-11 items-center justify-center rounded-2xl bg-gradient-to-br from-emerald-400 to-emerald-600 text-white shadow-lg shadow-emerald-500/30">
                    <x-icon name="heart" class="h-5 w-5" />
                </span>
                <span class="absolute right-10 top-0 flex h-11 w-11 items-center justify-center rounded-2xl bg-gradient-to-br from-blue-400 to-blue-600 text-white shadow-lg shadow-blue-500/30">
                    <x-icon name="monitor" class="h-5 w-5" />
                </span>
                <span class="absolute -right-1 top-24 flex h-11 w-11 items-center justify-center rounded-2xl bg-gradient-to-br from-amber-400 to-amber-600 text-white shadow-lg shadow-amber-500/30">
                    <x-icon name="building" class="h-5 w-5" />
                </span>
                <span class="absolute bottom-6 right-8 flex h-11 w-11 items-center justify-center rounded-2xl bg-gradient-to-br from-pink-400 to-pink-600 text-white shadow-lg shadow-pink-500/30">
                    <x-icon name="brush" class="h-5 w-5" />
                </span>
                <span class="absolute bottom-2 left-10 flex h-11 w-11 items-center justify-center rounded-2xl bg-gradient-to-br from-violet-400 to-violet-600 text-white shadow-lg shadow-violet-500/30">
                    <x-icon name="chart" class="h-5 w-5" />
                </span>
            </div>
        </div>
    </section>

    {{-- ══════════════════════════════════════════════════════════════════
         SECTION 1 — DOMAINES DE FORMATION (chargés depuis la base)
    ═══════════════════════════════════════════════════════════════════ --}}
    <div class="mb-6 flex items-center gap-3">
        <span class="flex h-11 w-11 shrink-0 items-center justify-center rounded-2xl bg-gradient-to-br from-brand-500 to-brand-700 text-white shadow-lg shadow-brand-600/30">
            <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" aria-hidden="true">
                <path stroke-linecap="round" stroke-linejoin="round" d="M9.594 3.94c.09-.542.56-.94 1.11-.94h2.593c.55 0 1.02.398 1.11.94l.213 1.281c.063.374.313.686.645.87.074.04.147.083.22.127.325.196.72.257 1.075.124l1.217-.456a1.125 1.125 0 0 1 1.37.49l1.296 2.247a1.125 1.125 0 0 1-.26 1.431l-1.003.827c-.293.24-.438.613-.43.992a7.723 7.723 0 0 1 0 .255c-.008.378.137.75.43.991l1.004.827c.424.35.534.955.26 1.43l-1.298 2.247a1.125 1.125 0 0 1-1.369.491l-1.217-.456c-.355-.133-.75-.072-1.076.124a6.47 6.47 0 0 1-.22.128c-.331.183-.581.495-.644.869l-.213 1.281c-.09.543-.56.94-1.11.94h-2.594c-.55 0-1.019-.398-1.11-.94l-.213-1.281c-.062-.374-.312-.686-.644-.87a6.52 6.52 0 0 1-.22-.127c-.325-.196-.72-.257-1.076-.124l-1.217.456a1.125 1.125 0 0 1-1.369-.49l-1.297-2.247a1.125 1.125 0 0 1 .26-1.431l1.004-.827c.292-.24.437-.613.43-.992a7.723 7.723 0 0 1 0-.255c.007-.378-.138-.75-.43-.991l-1.004-.827a1.125 1.125 0 0 1-.26-1.43l1.297-2.247a1.125 1.125 0 0 1 1.37-.491l1.216.456c.356.133.751.072 1.076-.124.072-.044.146-.086.22-.128.332-.183.582-.495.644-.869l.214-1.281Z" />
                <path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 1 1-6 0 3 3 0 0 1 6 0Z" />
            </svg>
        </span>
        <div>
            <h2 class="text-2xl font-extrabold tracking-tight text-slate-900 sm:text-[1.75rem] dark:text-white">
                {{ __('Domaines de formation') }}
            </h2>
            <p class="text-sm text-slate-500 dark:text-slate-400">
                {{ __('Filières de formation pédagogique et gestion des centres par domaine.') }}
            </p>
        </div>
    </div>

    <div class="mb-14 grid gap-6 sm:grid-cols-2 lg:grid-cols-3">
        @foreach ($domains as $domain)
            @php $c = $p($domain->color); @endphp
            <a href="{{ $domain->routeUrl() }}"
               id="card-domaine-{{ $domain->slug }}"
               class="group relative flex flex-col overflow-hidden rounded-3xl border p-6 shadow-sm transition-all duration-200 hover:-translate-y-1 hover:shadow-xl {{ $c['card'] }}"
               aria-label="{{ __('Accéder au domaine ' . $domain->name) }}">

                <div class="mb-5 flex items-center justify-between">
                    <span class="flex h-14 w-14 items-center justify-center rounded-2xl text-white shadow-lg {{ $c['chip'] }}">
                        <x-icon :name="$domain->icon" class="h-7 w-7" />
                    </span>
                    <span class="inline-flex items-center gap-1.5 rounded-full px-3 py-1 text-xs font-semibold {{ $c['badge'] }}">
                        <span class="h-2 w-2 rounded-full {{ $c['dot'] }}"></span>
                        {{ __('Actif') }}
                    </span>
                </div>

                <h3 class="text-xl font-bold text-slate-900 transition-colors dark:text-white {{ $c['title'] }}">
                    {{ __($domain->name) }}
                </h3>
                <p class="mt-2 flex-1 text-sm leading-relaxed text-slate-600 dark:text-slate-300">
                    {{ __($domain->description) }}
                </p>

                <div class="mt-6 flex items-center justify-between rounded-xl px-4 py-2.5 text-sm font-semibold transition-colors {{ $c['cta'] }}">
                    <span>{{ __('Accéder à l\'espace ' . $domain->name) }}</span>
                    <span class="flex h-7 w-7 items-center justify-center rounded-full shadow-sm transition-transform group-hover:translate-x-1 {{ $c['circle'] }}">
                        <x-icon name="arrow-right" class="h-4 w-4 rtl:rotate-180" />
                    </span>
                </div>
            </a>
        @endforeach
    </div>

    {{-- ══════════════════════════════════════════════════════════════════
         SECTION 2 — GESTION ADMINISTRATIVE (modules indépendants)
    ═══════════════════════════════════════════════════════════════════ --}}
    <div class="border-t border-slate-200/80 pt-10 dark:border-slate-800">
        <div class="mb-6 flex items-center gap-3">
            <span class="flex h-11 w-11 shrink-0 items-center justify-center rounded-2xl bg-gradient-to-br from-brand-500 to-brand-700 text-white shadow-lg shadow-brand-600/30">
                <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" aria-hidden="true">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M9.594 3.94c.09-.542.56-.94 1.11-.94h2.593c.55 0 1.02.398 1.11.94l.213 1.281c.063.374.313.686.645.87.074.04.147.083.22.127.325.196.72.257 1.075.124l1.217-.456a1.125 1.125 0 0 1 1.37.49l1.296 2.247a1.125 1.125 0 0 1-.26 1.431l-1.003.827c-.293.24-.438.613-.43.992a7.723 7.723 0 0 1 0 .255c-.008.378.137.75.43.991l1.004.827c.424.35.534.955.26 1.43l-1.298 2.247a1.125 1.125 0 0 1-1.369.491l-1.217-.456c-.355-.133-.75-.072-1.076.124a6.47 6.47 0 0 1-.22.128c-.331.183-.581.495-.644.869l-.213 1.281c-.09.543-.56.94-1.11.94h-2.594c-.55 0-1.019-.398-1.11-.94l-.213-1.281c-.062-.374-.312-.686-.644-.87a6.52 6.52 0 0 1-.22-.127c-.325-.196-.72-.257-1.076-.124l-1.217.456a1.125 1.125 0 0 1-1.369-.49l-1.297-2.247a1.125 1.125 0 0 1 .26-1.431l1.004-.827c.292-.24.437-.613.43-.992a7.723 7.723 0 0 1 0-.255c.007-.378-.138-.75-.43-.991l-1.004-.827a1.125 1.125 0 0 1-.26-1.43l1.297-2.247a1.125 1.125 0 0 1 1.37-.491l1.216.456c.356.133.751.072 1.076-.124.072-.044.146-.086.22-.128.332-.183.582-.495.644-.869l.214-1.281Z" />
                    <path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 1 1-6 0 3 3 0 0 1 6 0Z" />
                </svg>
            </span>
            <div>
                <h2 class="text-2xl font-extrabold tracking-tight text-slate-900 sm:text-[1.75rem] dark:text-white">
                    {{ __('Gestion administrative') }}
                </h2>
                <p class="text-sm text-slate-500 dark:text-slate-400">
                    {{ __('Modules administratifs indépendants du cursus pédagogique (non liés aux formations scolaires, classes ou examens).') }}
                </p>
            </div>
        </div>

        <div class="grid gap-6 lg:grid-cols-2">
            {{-- Stagiaires --}}
            @php $si = $p('indigo'); @endphp
            <a href="{{ route('stagiaires.dashboard') }}"
               id="card-stagiaires"
               class="group relative flex items-start gap-4 overflow-hidden rounded-3xl border p-6 shadow-sm transition-all duration-200 hover:-translate-y-1 hover:shadow-xl {{ $si['card'] }}"
               aria-label="{{ __('Accéder au module Stagiaires') }}">
                <span class="flex h-14 w-14 shrink-0 items-center justify-center rounded-2xl text-white shadow-lg {{ $si['chip'] }}">
                    <x-icon name="briefcase" class="h-7 w-7" />
                </span>
                <div class="min-w-0 flex-1">
                    <div class="flex flex-wrap items-center gap-2">
                        <h3 class="text-xl font-bold text-slate-900 dark:text-white {{ $si['title'] }}">{{ __('Stagiaires') }}</h3>
                        <span class="rounded-md bg-white/70 px-2 py-0.5 text-[10px] font-bold uppercase tracking-wider text-indigo-700 ring-1 ring-indigo-200/70 dark:bg-indigo-500/15 dark:text-indigo-300 dark:ring-indigo-400/30">
                            {{ __('Dossiers de stage') }}
                        </span>
                    </div>
                    <p class="mt-2 text-sm leading-relaxed text-slate-600 dark:text-slate-300">
                        {{ __('Gestion complète des dossiers de stage, conventions, identité CIN, diplômes, documents et prix convenu du stage.') }}
                    </p>
                </div>
                <span class="flex h-9 w-9 shrink-0 items-center justify-center self-center rounded-full {{ $si['circle'] }} shadow-sm transition-transform group-hover:translate-x-1">
                    <x-icon name="chevron-right" class="h-5 w-5 rtl:rotate-180" />
                </span>
            </a>

            {{-- Gestion financière --}}
            @php $fi = $p('emerald'); @endphp
            <a href="{{ route('super-admin.finance.dashboard') }}"
               id="card-gestion-financiere"
               class="group relative flex items-start gap-4 overflow-hidden rounded-3xl border p-6 shadow-sm transition-all duration-200 hover:-translate-y-1 hover:shadow-xl {{ $fi['card'] }}"
               aria-label="{{ __('Accéder au module Gestion financière') }}">
                <span class="flex h-14 w-14 shrink-0 items-center justify-center rounded-2xl text-white shadow-lg {{ $fi['chip'] }}">
                    <x-icon name="banknotes" class="h-7 w-7" />
                </span>
                <div class="min-w-0 flex-1">
                    <div class="flex flex-wrap items-center gap-2">
                        <h3 class="text-xl font-bold text-slate-900 dark:text-white {{ $fi['title'] }}">{{ __('Gestion financière') }}</h3>
                        <span class="rounded-md bg-white/70 px-2 py-0.5 text-[10px] font-bold uppercase tracking-wider text-emerald-700 ring-1 ring-emerald-200/70 dark:bg-emerald-500/15 dark:text-emerald-300 dark:ring-emerald-400/30">
                            {{ __('Vue globale') }}
                        </span>
                    </div>
                    <p class="mt-2 text-sm leading-relaxed text-slate-600 dark:text-slate-300">
                        {{ __('Vision financière globale de la plateforme : encaissements étudiants, suivi des impayés, salaires des professeurs et filtres par domaine.') }}
                    </p>
                </div>
                <span class="flex h-9 w-9 shrink-0 items-center justify-center self-center rounded-full {{ $fi['circle'] }} shadow-sm transition-transform group-hover:translate-x-1">
                    <x-icon name="chevron-right" class="h-5 w-5 rtl:rotate-180" />
                </span>
            </a>
        </div>
    </div>
@endsection
