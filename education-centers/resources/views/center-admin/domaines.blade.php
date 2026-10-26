@extends('layouts.portal')

@section('title', __('Sélection du domaine') . ' — ' . config('app.name'))
@section('description', __('Choisissez le domaine d\'activité à administrer pour votre centre.'))

@php
    /*
    | Palette de classes Tailwind littérales par couleur de domaine (partagée
    | avec le portail Super Admin). Chaînes complètes en dur pour le scanner.
    */
    $palette = [
        'emerald' => ['card' => 'border-emerald-200/70 bg-emerald-50/40 hover:border-emerald-300 dark:border-emerald-400/20 dark:bg-emerald-400/[0.06] dark:hover:border-emerald-400/40', 'chip' => 'bg-gradient-to-br from-emerald-400 to-emerald-600 shadow-emerald-500/30', 'badge' => 'bg-emerald-100 text-emerald-700 dark:bg-emerald-500/15 dark:text-emerald-300', 'dot' => 'bg-emerald-500', 'title' => 'group-hover:text-emerald-700 dark:group-hover:text-emerald-300', 'cta' => 'bg-emerald-100/70 text-emerald-700 dark:bg-emerald-500/10 dark:text-emerald-300', 'circle' => 'bg-emerald-600 text-white'],
        'blue' => ['card' => 'border-blue-200/70 bg-blue-50/40 hover:border-blue-300 dark:border-blue-400/20 dark:bg-blue-400/[0.06] dark:hover:border-blue-400/40', 'chip' => 'bg-gradient-to-br from-blue-400 to-blue-600 shadow-blue-500/30', 'badge' => 'bg-blue-100 text-blue-700 dark:bg-blue-500/15 dark:text-blue-300', 'dot' => 'bg-blue-500', 'title' => 'group-hover:text-blue-700 dark:group-hover:text-blue-300', 'cta' => 'bg-blue-100/70 text-blue-700 dark:bg-blue-500/10 dark:text-blue-300', 'circle' => 'bg-blue-600 text-white'],
        'amber' => ['card' => 'border-amber-200/70 bg-amber-50/40 hover:border-amber-300 dark:border-amber-400/20 dark:bg-amber-400/[0.06] dark:hover:border-amber-400/40', 'chip' => 'bg-gradient-to-br from-amber-400 to-amber-600 shadow-amber-500/30', 'badge' => 'bg-amber-100 text-amber-800 dark:bg-amber-500/15 dark:text-amber-300', 'dot' => 'bg-amber-500', 'title' => 'group-hover:text-amber-700 dark:group-hover:text-amber-300', 'cta' => 'bg-amber-100/70 text-amber-800 dark:bg-amber-500/10 dark:text-amber-300', 'circle' => 'bg-amber-500 text-white'],
        'orange' => ['card' => 'border-orange-200/70 bg-orange-50/40 hover:border-orange-300 dark:border-orange-400/20 dark:bg-orange-400/[0.06] dark:hover:border-orange-400/40', 'chip' => 'bg-gradient-to-br from-orange-400 to-orange-600 shadow-orange-500/30', 'badge' => 'bg-orange-100 text-orange-700 dark:bg-orange-500/15 dark:text-orange-300', 'dot' => 'bg-orange-500', 'title' => 'group-hover:text-orange-700 dark:group-hover:text-orange-300', 'cta' => 'bg-orange-100/70 text-orange-700 dark:bg-orange-500/10 dark:text-orange-300', 'circle' => 'bg-orange-600 text-white'],
        'violet' => ['card' => 'border-violet-200/70 bg-violet-50/40 hover:border-violet-300 dark:border-violet-400/20 dark:bg-violet-400/[0.06] dark:hover:border-violet-400/40', 'chip' => 'bg-gradient-to-br from-violet-400 to-violet-600 shadow-violet-500/30', 'badge' => 'bg-violet-100 text-violet-700 dark:bg-violet-500/15 dark:text-violet-300', 'dot' => 'bg-violet-500', 'title' => 'group-hover:text-violet-700 dark:group-hover:text-violet-300', 'cta' => 'bg-violet-100/70 text-violet-700 dark:bg-violet-500/10 dark:text-violet-300', 'circle' => 'bg-violet-600 text-white'],
        'pink' => ['card' => 'border-pink-200/70 bg-pink-50/40 hover:border-pink-300 dark:border-pink-400/20 dark:bg-pink-400/[0.06] dark:hover:border-pink-400/40', 'chip' => 'bg-gradient-to-br from-pink-400 to-pink-600 shadow-pink-500/30', 'badge' => 'bg-pink-100 text-pink-700 dark:bg-pink-500/15 dark:text-pink-300', 'dot' => 'bg-pink-500', 'title' => 'group-hover:text-pink-700 dark:group-hover:text-pink-300', 'cta' => 'bg-pink-100/70 text-pink-700 dark:bg-pink-500/10 dark:text-pink-300', 'circle' => 'bg-pink-600 text-white'],
        'brand' => ['card' => 'border-brand-200/70 bg-brand-50/40 hover:border-brand-300 dark:border-brand-400/20 dark:bg-brand-400/[0.06] dark:hover:border-brand-400/40', 'chip' => 'bg-gradient-to-br from-brand-400 to-brand-600 shadow-brand-500/30', 'badge' => 'bg-brand-100 text-brand-700 dark:bg-brand-500/15 dark:text-brand-300', 'dot' => 'bg-brand-500', 'title' => 'group-hover:text-brand-700 dark:group-hover:text-brand-300', 'cta' => 'bg-brand-100/70 text-brand-700 dark:bg-brand-500/10 dark:text-brand-300', 'circle' => 'bg-brand-600 text-white'],
    ];
    $p = fn (string $c): array => $palette[$c] ?? $palette['brand'];
    $user = auth()->user();
    $centerName = $user->center?->name ?? '';
@endphp

@section('content')

    {{-- ══════════════════════════════════════════════════════════════════
         HERO — titre + contexte centre
    ═══════════════════════════════════════════════════════════════════ --}}
    <section class="relative mb-12 overflow-hidden rounded-3xl border border-slate-200/70 bg-gradient-to-br from-indigo-50 via-white to-brand-50 px-6 py-10 sm:px-10 sm:py-12 dark:border-slate-800 dark:from-slate-900 dark:via-slate-900 dark:to-brand-950/40">
        <div class="pointer-events-none absolute -top-24 -end-16 h-72 w-72 rounded-full bg-brand-300/30 blur-3xl dark:bg-brand-500/10"></div>
        <div class="relative">
            <span class="inline-flex items-center gap-2 rounded-full border border-brand-200/70 bg-white/70 px-3.5 py-1 text-xs font-semibold text-brand-700 shadow-2xs backdrop-blur dark:border-brand-500/30 dark:bg-brand-500/10 dark:text-brand-300">
                <x-icon name="building" class="h-3.5 w-3.5" />
                {{ __('Espace Admin Centre') }} · {{ $centerName }}
            </span>

            <h1 class="mt-4 text-4xl font-extrabold leading-[1.1] tracking-tight text-slate-900 sm:text-5xl dark:text-white">
                {{ __('Sélectionnez votre domaine') }}
            </h1>
            <p class="mt-4 max-w-2xl text-base leading-relaxed text-slate-600 dark:text-slate-300">
                {{ __('Choisissez une filière de formation pour administrer les données de votre centre. Les six domaines sont disponibles ; les données restent propres à votre centre.') }}
            </p>
        </div>
    </section>

    {{-- ══════════════════════════════════════════════════════════════════
         DOMAINES DE FORMATION DU CENTRE
    ═══════════════════════════════════════════════════════════════════ --}}
    <div class="mb-6 flex items-center gap-3">
        <span class="flex h-11 w-11 shrink-0 items-center justify-center rounded-2xl bg-gradient-to-br from-brand-500 to-brand-700 text-white shadow-lg shadow-brand-600/30">
            <x-icon name="book" class="h-6 w-6" />
        </span>
        <div>
            <h2 class="text-2xl font-extrabold tracking-tight text-slate-900 sm:text-[1.75rem] dark:text-white">
                {{ __('Domaines de formation') }}
            </h2>
            <p class="text-sm text-slate-500 dark:text-slate-400">
                {{ __('Les six domaines sont disponibles pour votre centre.') }}
            </p>
        </div>
    </div>

    <div class="grid gap-6 sm:grid-cols-2 lg:grid-cols-3">
        @forelse ($domains as $domain)
            @php $c = $p($domain->color); @endphp
            <a href="{{ route('center-admin.domain.dashboard', $domain->slug) }}"
               id="card-domaine-{{ $domain->slug }}"
               class="group relative flex flex-col overflow-hidden rounded-3xl border p-6 shadow-sm transition-all duration-200 hover:-translate-y-1 hover:shadow-xl {{ $c['card'] }}"
               aria-label="{{ __('Accéder au domaine :domain', ['domain' => $domain->name]) }}">

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
                    <span>{{ __('Accéder à l\'espace :domain', ['domain' => $domain->name]) }}</span>
                    <span class="flex h-7 w-7 items-center justify-center rounded-full shadow-sm transition-transform group-hover:translate-x-1 {{ $c['circle'] }}">
                        <x-icon name="arrow-right" class="h-4 w-4 rtl:rotate-180" />
                    </span>
                </div>
            </a>
        @empty
            <div class="col-span-full rounded-2xl border border-dashed border-slate-300 bg-white/60 p-10 text-center dark:border-slate-700 dark:bg-slate-900/40">
                <x-icon name="book" class="mx-auto h-10 w-10 text-slate-300 dark:text-slate-600" />
                <p class="mt-3 text-sm font-medium text-slate-500 dark:text-slate-400">
                    {{ __('Aucun domaine disponible pour votre centre pour le moment.') }}
                </p>
                <a href="{{ route('dashboard') }}" class="mt-4 inline-flex items-center gap-2 rounded-xl bg-brand-600 px-4 py-2 text-sm font-semibold text-white hover:bg-brand-700">
                    <x-icon name="home" class="h-4 w-4" />
                    {{ __('Retour au tableau de bord') }}
                </a>
            </div>
        @endforelse
    </div>
@endsection
