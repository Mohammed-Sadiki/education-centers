@extends('layouts.portal')

@section('title', 'Sélection du domaine — Super Admin')
@section('description', 'Choisissez le domaine d\'activité à administrer.')

@section('content')
    {{-- En-tête de bienvenue du portail --}}
    <div class="mb-10 text-center sm:text-left">
        <div class="inline-flex items-center gap-2 rounded-full bg-brand-50 border border-brand-200/60 px-3.5 py-1 text-xs font-semibold text-brand-700 dark:bg-brand-950/50 dark:border-brand-800/60 dark:text-brand-300 mb-3 shadow-xs">
            <span class="flex h-2 w-2 rounded-full bg-brand-500 animate-pulse"></span>
            Espace Super Administrateur
        </div>
        <h1 class="text-3xl sm:text-4xl font-extrabold tracking-tight text-slate-900 dark:text-white">
            Sélectionnez votre domaine
        </h1>
        <p class="mt-2 text-base text-slate-600 dark:text-slate-400 max-w-2xl">
            Bienvenue dans le portail d'administration par domaine. Chaque filière regroupe ses centres, formations, équipes et étudiants spécifiques.
        </p>
    </div>

    {{-- Grille des 6 cartes de domaines --}}
    <div class="grid gap-6 sm:grid-cols-2 lg:grid-cols-3">

        {{-- ════════════════════════════════════════════════
             CARTE 1 — INFORMATIQUE (Bientôt disponible)
        ════════════════════════════════════════════════ --}}
        <div class="relative flex flex-col justify-between overflow-hidden rounded-2xl border border-slate-200/90 bg-white p-7 shadow-xs opacity-75 dark:border-slate-800 dark:bg-slate-900 select-none"
             aria-label="Domaine Informatique — bientôt disponible">
            <div>
                <div class="flex items-center justify-between mb-5">
                    <div class="flex h-14 w-14 items-center justify-center rounded-2xl bg-slate-100 text-slate-400 dark:bg-slate-800 dark:text-slate-500">
                        <svg class="h-7 w-7" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M9 17.25v1.007a3 3 0 0 1-.879 2.122L7.5 21h9l-.621-.621A3 3 0 0 1 15 18.257V17.25m6-12V15a2.25 2.25 0 0 1-2.25 2.25H5.25A2.25 2.25 0 0 1 3 15V5.25m18 0A2.25 2.25 0 0 0 18.75 3H5.25A2.25 2.25 0 0 0 3 5.25m18 0H3"/>
                        </svg>
                    </div>
                    <span class="inline-flex items-center gap-1.5 rounded-full bg-slate-100 px-3 py-1 text-xs font-semibold text-slate-600 dark:bg-slate-800 dark:text-slate-400">
                        <span class="h-1.5 w-1.5 rounded-full bg-slate-400"></span>
                        Bientôt disponible
                    </span>
                </div>
                <h2 class="text-xl font-bold text-slate-700 dark:text-slate-300">
                    Informatique
                </h2>
                <p class="mt-2 text-sm text-slate-500 dark:text-slate-400 leading-relaxed">
                    Développement web, cybersécurité, réseaux, administration systèmes et intelligence artificielle.
                </p>
            </div>
            <div class="mt-6 pt-4 border-t border-slate-100 dark:border-slate-800/80">
                <span class="text-xs font-medium text-slate-400 dark:text-slate-500 flex items-center gap-1.5 cursor-not-allowed">
                    <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M16.5 10.5V6.75a4.5 4.5 0 1 0-9 0v3.75m-.75 11.25h10.5a2.25 2.25 0 0 0 2.25-2.25v-6.75a2.25 2.25 0 0 0-2.25-2.25H6.75a2.25 2.25 0 0 0-2.25 2.25v6.75a2.25 2.25 0 0 0 2.25 2.25Z" />
                    </svg>
                    Module en cours de préparation
                </span>
            </div>
        </div>

        {{-- ════════════════════════════════════════════════
             CARTE 2 — SANTÉ (Disponible ✓)
        ════════════════════════════════════════════════ --}}
        <a href="{{ route('super-admin.sante.dashboard') }}"
           id="card-domaine-sante"
           class="group relative flex flex-col justify-between overflow-hidden rounded-2xl border-2 border-emerald-300 bg-white p-7 shadow-md
                  hover:border-emerald-500 hover:shadow-xl hover:-translate-y-1
                  dark:border-emerald-600/70 dark:bg-slate-900 dark:hover:border-emerald-400
                  transition-all duration-200 cursor-pointer ring-1 ring-emerald-500/20"
           aria-label="Accéder au domaine Santé">

            {{-- Dégradé interactif au survol --}}
            <div class="absolute inset-0 bg-gradient-to-br from-emerald-50/60 via-transparent to-brand-50/30 opacity-0 group-hover:opacity-100 transition-opacity duration-300 dark:from-emerald-950/20 dark:to-transparent pointer-events-none rounded-2xl"></div>

            <div>
                <div class="flex items-center justify-between mb-5">
                    <div class="flex h-14 w-14 items-center justify-center rounded-2xl bg-emerald-100 text-emerald-600 shadow-sm group-hover:bg-emerald-600 group-hover:text-white transition-colors duration-200 dark:bg-emerald-950/60 dark:text-emerald-400 dark:group-hover:bg-emerald-600 dark:group-hover:text-white">
                        <svg class="h-7 w-7" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M21 8.25c0-2.485-2.099-4.5-4.688-4.5-1.935 0-3.597 1.126-4.312 2.733-.715-1.607-2.377-2.733-4.313-2.733C5.1 3.75 3 5.765 3 8.25c0 7.22 9 12 9 12s9-4.78 9-12Z"/>
                        </svg>
                    </div>
                    <span class="inline-flex items-center gap-1.5 rounded-full bg-emerald-100 px-3 py-1 text-xs font-semibold text-emerald-800 dark:bg-emerald-900/50 dark:text-emerald-300 shadow-2xs">
                        <span class="h-2 w-2 rounded-full bg-emerald-500"></span>
                        Disponible
                    </span>
                </div>

                <div class="flex items-center gap-2">
                    <h2 class="text-xl font-bold text-slate-900 dark:text-white group-hover:text-emerald-700 dark:group-hover:text-emerald-400 transition-colors duration-200">
                        Santé
                    </h2>
                    <span class="rounded-md bg-emerald-50 px-2 py-0.5 text-[10px] font-bold uppercase tracking-wider text-emerald-700 dark:bg-emerald-950/80 dark:text-emerald-300 border border-emerald-200/60 dark:border-emerald-800/60">
                        Actif
                    </span>
                </div>
                <p class="mt-2 text-sm text-slate-600 dark:text-slate-300 leading-relaxed">
                    Formations paramédicales, soins infirmiers, aides-soignants, secrétariat médical et professions de santé.
                </p>
            </div>

            <div class="mt-6 pt-4 border-t border-slate-100 dark:border-slate-800/80">
                <div class="flex items-center justify-between text-sm font-semibold text-emerald-600 dark:text-emerald-400 group-hover:text-emerald-700 dark:group-hover:text-emerald-300">
                    <span>Accéder à l'espace Santé</span>
                    <span class="flex h-7 w-7 items-center justify-center rounded-full bg-emerald-50 text-emerald-600 group-hover:bg-emerald-600 group-hover:text-white group-hover:translate-x-1 transition-all duration-200 dark:bg-emerald-950/60 dark:text-emerald-400 dark:group-hover:bg-emerald-600 dark:group-hover:text-white">
                        <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="2.5" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M13.5 4.5 21 12m0 0-7.5 7.5M21 12H3"/>
                        </svg>
                    </span>
                </div>
            </div>
        </a>

        {{-- ════════════════════════════════════════════════
             CARTE 3 — HÔTELLERIE (Bientôt disponible)
        ════════════════════════════════════════════════ --}}
        <div class="relative flex flex-col justify-between overflow-hidden rounded-2xl border border-slate-200/90 bg-white p-7 shadow-xs opacity-75 dark:border-slate-800 dark:bg-slate-900 select-none"
             aria-label="Domaine Hôtellerie — bientôt disponible">
            <div>
                <div class="flex items-center justify-between mb-5">
                    <div class="flex h-14 w-14 items-center justify-center rounded-2xl bg-slate-100 text-slate-400 dark:bg-slate-800 dark:text-slate-500">
                        <svg class="h-7 w-7" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M2.25 21h19.5m-18-18v18m10.5-18v18m6-13.5V21M6.75 6.75h.75m-.75 3h.75m-.75 3h.75m3-6h.75m-.75 3h.75m-.75 3h.75M6.75 21v-3.375c0-.621.504-1.125 1.125-1.125h2.25c.621 0 1.125.504 1.125 1.125V21M3 3h12m-.75 4.5H21m-3.75 3.75h.008v.008h-.008v-.008Zm0 3h.008v.008h-.008v-.008Zm0 3h.008v.008h-.008v-.008Z"/>
                        </svg>
                    </div>
                    <span class="inline-flex items-center gap-1.5 rounded-full bg-slate-100 px-3 py-1 text-xs font-semibold text-slate-600 dark:bg-slate-800 dark:text-slate-400">
                        <span class="h-1.5 w-1.5 rounded-full bg-slate-400"></span>
                        Bientôt disponible
                    </span>
                </div>
                <h2 class="text-xl font-bold text-slate-700 dark:text-slate-300">
                    Hôtellerie
                </h2>
                <p class="mt-2 text-sm text-slate-500 dark:text-slate-400 leading-relaxed">
                    Arts culinaires, service en salle, gestion hôtelière, hébergement et tourisme.
                </p>
            </div>
            <div class="mt-6 pt-4 border-t border-slate-100 dark:border-slate-800/80">
                <span class="text-xs font-medium text-slate-400 dark:text-slate-500 flex items-center gap-1.5 cursor-not-allowed">
                    <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M16.5 10.5V6.75a4.5 4.5 0 1 0-9 0v3.75m-.75 11.25h10.5a2.25 2.25 0 0 0 2.25-2.25v-6.75a2.25 2.25 0 0 0-2.25-2.25H6.75a2.25 2.25 0 0 0-2.25 2.25v6.75a2.25 2.25 0 0 0 2.25 2.25Z" />
                    </svg>
                    Module en cours de préparation
                </span>
            </div>
        </div>

        {{-- ════════════════════════════════════════════════
             CARTE 4 — MAÇONNERIE (Bientôt disponible)
        ════════════════════════════════════════════════ --}}
        <div class="relative flex flex-col justify-between overflow-hidden rounded-2xl border border-slate-200/90 bg-white p-7 shadow-xs opacity-75 dark:border-slate-800 dark:bg-slate-900 select-none"
             aria-label="Domaine Maçonnerie — bientôt disponible">
            <div>
                <div class="flex items-center justify-between mb-5">
                    <div class="flex h-14 w-14 items-center justify-center rounded-2xl bg-slate-100 text-slate-400 dark:bg-slate-800 dark:text-slate-500">
                        <svg class="h-7 w-7" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M11.42 15.17 17.25 21A2.652 2.652 0 0 0 21 17.25l-5.877-5.877M11.42 15.17l2.496-3.03c.317-.384.74-.626 1.208-.766M11.42 15.17l-4.655 5.653a2.548 2.548 0 1 1-3.586-3.586l6.837-5.63m5.108-.233c.55-.164 1.163-.188 1.743-.14a4.5 4.5 0 0 0 4.486-6.336l-3.276 3.277a3.004 3.004 0 0 1-2.25-2.25l3.276-3.276a4.5 4.5 0 0 0-6.336 4.486c.091 1.076-.071 2.264-.904 2.95l-.102.085m-1.745 1.437L5.909 7.5H4.5L2.25 3.75l1.5-1.5L7.5 4.5v1.409l4.26 4.26m-1.745 1.437 1.745-1.437m6.615 8.206L15.75 15.75M4.867 19.125h.008v.008h-.008v-.008Z"/>
                        </svg>
                    </div>
                    <span class="inline-flex items-center gap-1.5 rounded-full bg-slate-100 px-3 py-1 text-xs font-semibold text-slate-600 dark:bg-slate-800 dark:text-slate-400">
                        <span class="h-1.5 w-1.5 rounded-full bg-slate-400"></span>
                        Bientôt disponible
                    </span>
                </div>
                <h2 class="text-xl font-bold text-slate-700 dark:text-slate-300">
                    Maçonnerie
                </h2>
                <p class="mt-2 text-sm text-slate-500 dark:text-slate-400 leading-relaxed">
                    Gros œuvre, maçonnerie générale, rénovation, béton armé et conduite de chantiers du BTP.
                </p>
            </div>
            <div class="mt-6 pt-4 border-t border-slate-100 dark:border-slate-800/80">
                <span class="text-xs font-medium text-slate-400 dark:text-slate-500 flex items-center gap-1.5 cursor-not-allowed">
                    <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M16.5 10.5V6.75a4.5 4.5 0 1 0-9 0v3.75m-.75 11.25h10.5a2.25 2.25 0 0 0 2.25-2.25v-6.75a2.25 2.25 0 0 0-2.25-2.25H6.75a2.25 2.25 0 0 0-2.25 2.25v6.75a2.25 2.25 0 0 0 2.25 2.25Z" />
                    </svg>
                    Module en cours de préparation
                </span>
            </div>
        </div>

        {{-- ════════════════════════════════════════════════
             CARTE 5 — DESIGN INTÉRIEUR (Bientôt disponible)
        ════════════════════════════════════════════════ --}}
        <div class="relative flex flex-col justify-between overflow-hidden rounded-2xl border border-slate-200/90 bg-white p-7 shadow-xs opacity-75 dark:border-slate-800 dark:bg-slate-900 select-none"
             aria-label="Domaine Design intérieur — bientôt disponible">
            <div>
                <div class="flex items-center justify-between mb-5">
                    <div class="flex h-14 w-14 items-center justify-center rounded-2xl bg-slate-100 text-slate-400 dark:bg-slate-800 dark:text-slate-500">
                        <svg class="h-7 w-7" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" d="m2.25 12 8.954-8.955c.44-.439 1.152-.439 1.591 0L21.75 12M4.5 9.75v10.125c0 .621.504 1.125 1.125 1.125H9.75v-4.875c0-.621.504-1.125 1.125-1.125h2.25c.621 0 1.125.504 1.125 1.125V21h4.125c.621 0 1.125-.504 1.125-1.125V9.75M8.25 21h8.25"/>
                        </svg>
                    </div>
                    <span class="inline-flex items-center gap-1.5 rounded-full bg-slate-100 px-3 py-1 text-xs font-semibold text-slate-600 dark:bg-slate-800 dark:text-slate-400">
                        <span class="h-1.5 w-1.5 rounded-full bg-slate-400"></span>
                        Bientôt disponible
                    </span>
                </div>
                <h2 class="text-xl font-bold text-slate-700 dark:text-slate-300">
                    Design intérieur
                </h2>
                <p class="mt-2 text-sm text-slate-500 dark:text-slate-400 leading-relaxed">
                    Aménagement d'espace, décoration, modélisation 3D architecturale et scénographie.
                </p>
            </div>
            <div class="mt-6 pt-4 border-t border-slate-100 dark:border-slate-800/80">
                <span class="text-xs font-medium text-slate-400 dark:text-slate-500 flex items-center gap-1.5 cursor-not-allowed">
                    <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M16.5 10.5V6.75a4.5 4.5 0 1 0-9 0v3.75m-.75 11.25h10.5a2.25 2.25 0 0 0 2.25-2.25v-6.75a2.25 2.25 0 0 0-2.25-2.25H6.75a2.25 2.25 0 0 0-2.25 2.25v6.75a2.25 2.25 0 0 0 2.25 2.25Z" />
                    </svg>
                    Module en cours de préparation
                </span>
            </div>
        </div>

        {{-- ════════════════════════════════════════════════
             CARTE 6 — DESIGN GRAPHIQUE (Bientôt disponible)
        ════════════════════════════════════════════════ --}}
        <div class="relative flex flex-col justify-between overflow-hidden rounded-2xl border border-slate-200/90 bg-white p-7 shadow-xs opacity-75 dark:border-slate-800 dark:bg-slate-900 select-none"
             aria-label="Domaine Design graphique — bientôt disponible">
            <div>
                <div class="flex items-center justify-between mb-5">
                    <div class="flex h-14 w-14 items-center justify-center rounded-2xl bg-slate-100 text-slate-400 dark:bg-slate-800 dark:text-slate-500">
                        <svg class="h-7 w-7" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M9.53 16.122a3 3 0 0 0-5.78 1.128 2.25 2.25 0 0 1-2.4 2.245 4.5 4.5 0 0 0 8.4-2.245c0-.399-.078-.78-.22-1.128Zm0 0a15.998 15.998 0 0 0 3.388-1.62m-5.043-.025a15.994 15.994 0 0 1 1.622-3.395m3.42 3.42a15.995 15.995 0 0 0 4.764-4.648l3.876-5.814a1.151 1.151 0 0 0-1.597-1.597L14.146 6.32a15.996 15.996 0 0 0-4.649 4.763m3.42 3.42a6.776 6.776 0 0 0-3.42-3.42"/>
                        </svg>
                    </div>
                    <span class="inline-flex items-center gap-1.5 rounded-full bg-slate-100 px-3 py-1 text-xs font-semibold text-slate-600 dark:bg-slate-800 dark:text-slate-400">
                        <span class="h-1.5 w-1.5 rounded-full bg-slate-400"></span>
                        Bientôt disponible
                    </span>
                </div>
                <h2 class="text-xl font-bold text-slate-700 dark:text-slate-300">
                    Design graphique
                </h2>
                <p class="mt-2 text-sm text-slate-500 dark:text-slate-400 leading-relaxed">
                    Identité visuelle, typographie, illustration vectorielle, UI/UX design et packaging.
                </p>
            </div>
            <div class="mt-6 pt-4 border-t border-slate-100 dark:border-slate-800/80">
                <span class="text-xs font-medium text-slate-400 dark:text-slate-500 flex items-center gap-1.5 cursor-not-allowed">
                    <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M16.5 10.5V6.75a4.5 4.5 0 1 0-9 0v3.75m-.75 11.25h10.5a2.25 2.25 0 0 0 2.25-2.25v-6.75a2.25 2.25 0 0 0-2.25-2.25H6.75a2.25 2.25 0 0 0-2.25 2.25v6.75a2.25 2.25 0 0 0 2.25 2.25Z" />
                    </svg>
                    Module en cours de préparation
                </span>
            </div>
        </div>

    </div>{{-- /grid --}}
@endsection
