@extends('layouts.app')

@section('title', 'Tableau de bord — Stagiaires')
@section('description', 'Espace d\'administration dédié aux stagiaires, dossiers administratifs, pièces justificatives et tarification.')

@section('breadcrumb')
    <li class="flex items-center gap-2">
        <x-icon name="chevron-left" class="h-3 w-3 text-slate-400 rotate-180" />
        <a href="{{ route('super-admin.domaines') }}" class="hover:text-brand-700 dark:hover:text-brand-400 transition-colors">Portail domaines</a>
    </li>
    <li class="flex items-center gap-2">
        <x-icon name="chevron-left" class="h-3 w-3 text-slate-400 rotate-180" />
        <span class="font-semibold text-slate-800 dark:text-slate-200">Espace Stagiaires</span>
    </li>
@endsection

@section('content')
<div class="space-y-8">
    {{-- ────────────────────────────────────────────────────────────
         EN-TÊTE PRINCIPAL DE L'ESPACE D'ADMINISTRATION STAGIAIRES
    ──────────────────────────────────────────────────────────── --}}
    <div class="relative overflow-hidden rounded-2xl bg-gradient-to-r from-purple-800 via-indigo-800 to-slate-900 p-6 sm:p-8 text-white shadow-xl">
        {{-- Décoration de fond --}}
        <div class="absolute -right-10 -bottom-10 h-64 w-64 rounded-full bg-purple-500/10 blur-3xl pointer-events-none"></div>

        <div class="relative z-10 flex flex-col gap-6 lg:flex-row lg:items-center lg:justify-between">
            <div class="space-y-2">
                <div class="flex flex-wrap items-center gap-2">
                    <span class="inline-flex items-center gap-1.5 rounded-full bg-white/15 px-3 py-1 text-xs font-semibold backdrop-blur-md">
                        <span class="text-sm">🧑‍💼</span>
                        <span>Espace administratif autonome</span>
                    </span>
                    <span class="inline-flex items-center rounded-full bg-emerald-500/20 text-emerald-200 border border-emerald-400/30 px-2.5 py-0.5 text-xs font-medium">
                        Contexte actif
                    </span>
                </div>
                <h1 class="text-2xl sm:text-3xl font-black tracking-tight">
                    Tableau de bord — Stagiaires
                </h1>
                <p class="text-sm text-purple-100 max-w-2xl leading-relaxed">
                    Espace centralisé pour la gestion complète des stagiaires, conventions de stage, identité CIN, diplômes scannés et tarification convenue.
                </p>
            </div>

            <div class="flex flex-wrap items-center gap-3">
                <a href="{{ route('super-admin.domaines') }}"
                   class="inline-flex items-center gap-2 rounded-xl border border-white/20 bg-white/10 px-4 py-2.5 text-xs font-semibold text-white backdrop-blur-md hover:bg-white/20 transition-all shadow-xs"
                   title="Retourner à la sélection des domaines">
                    <x-icon name="arrow-uturn-left" class="h-4 w-4" />
                    <span>Changer de domaine</span>
                </a>
                <a href="{{ route('stagiaires.index') }}"
                   class="inline-flex items-center gap-2 rounded-xl border border-white/20 bg-white/10 px-4 py-2.5 text-xs font-semibold text-white backdrop-blur-md hover:bg-white/20 transition-all shadow-xs">
                    <x-icon name="users" class="h-4 w-4" />
                    <span>Tous les stagiaires</span>
                </a>
                <a href="{{ route('stagiaires.create') }}"
                   id="btn-ajouter-stagiaire-header"
                   class="inline-flex items-center gap-2 rounded-xl bg-white px-5 py-2.5 text-xs font-bold text-purple-900 shadow-md hover:bg-purple-50 transition-all">
                    <x-icon name="plus" class="h-4 w-4 text-purple-700" />
                    <span>Ajouter un stagiaire</span>
                </a>
            </div>
        </div>
    </div>

    {{-- ────────────────────────────────────────────────────────────
         BARRE DE RECHERCHE RAPIDE & ACTIONS DE GESTION
    ──────────────────────────────────────────────────────────── --}}
    <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-xs dark:border-slate-800 dark:bg-slate-900">
        <form method="GET" action="{{ route('stagiaires.index') }}" class="flex flex-col sm:flex-row gap-3">
            <div class="relative flex-1">
                <div class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-3.5 text-slate-400">
                    <x-icon name="search" class="h-4 w-4" />
                </div>
                <input type="text" name="search"
                       placeholder="Recherche rapide d'un stagiaire par nom, prénom, CIN, email ou téléphone..."
                       class="block w-full rounded-xl border border-slate-300 bg-slate-50/50 pl-10 pr-4 py-2.5 text-xs text-slate-900 placeholder:text-slate-400 focus:border-purple-500 focus:bg-white focus:outline-none focus:ring-2 focus:ring-purple-500/20 dark:border-slate-700 dark:bg-slate-800 dark:text-white dark:placeholder:text-slate-500">
            </div>
            <button type="submit"
                    class="inline-flex items-center justify-center gap-2 rounded-xl bg-purple-600 px-5 py-2.5 text-xs font-bold text-white shadow-xs hover:bg-purple-700 transition">
                <x-icon name="search" class="h-3.5 w-3.5" />
                <span>Rechercher</span>
            </button>
            <a href="{{ route('stagiaires.index') }}"
               class="inline-flex items-center justify-center gap-2 rounded-xl border border-slate-300 bg-white px-4 py-2.5 text-xs font-semibold text-slate-700 hover:bg-slate-50 dark:border-slate-700 dark:bg-slate-800 dark:text-slate-200 dark:hover:bg-slate-700 transition">
                <span>Voir la liste complète</span>
            </a>
        </form>
    </div>

    {{-- ────────────────────────────────────────────────────────────
         CARTES STATISTIQUES (KPIs CLICQUABLES)
    ──────────────────────────────────────────────────────────── --}}
    <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-6">
        {{-- Total --}}
        <a href="{{ route('stagiaires.index') }}"
           class="group rounded-2xl border border-slate-200 bg-white p-5 shadow-xs hover:border-purple-300 hover:shadow-md transition-all dark:border-slate-800 dark:bg-slate-900">
            <div class="flex items-center justify-between">
                <span class="text-xs font-semibold uppercase tracking-wider text-slate-500">Total stagiaires</span>
                <span class="flex h-7 w-7 items-center justify-center rounded-lg bg-slate-100 text-slate-600 group-hover:bg-purple-100 group-hover:text-purple-700 transition dark:bg-slate-800 dark:text-slate-300">
                    <x-icon name="users" class="h-4 w-4" />
                </span>
            </div>
            <p class="mt-3 text-3xl font-black text-slate-900 dark:text-white">{{ $total }}</p>
            <p class="mt-1 text-[11px] text-slate-400 flex items-center justify-between">
                <span>Dossiers créés</span>
                <span class="text-purple-600 dark:text-purple-400 group-hover:translate-x-0.5 transition-transform">→</span>
            </p>
        </a>

        {{-- Nouveaux --}}
        <a href="{{ route('stagiaires.index', ['status' => 'nouveau']) }}"
           class="group rounded-2xl border border-blue-200 bg-blue-50/20 p-5 shadow-xs hover:border-blue-400 hover:shadow-md transition-all dark:border-blue-900/40 dark:bg-blue-950/10">
            <div class="flex items-center justify-between">
                <span class="text-xs font-semibold uppercase tracking-wider text-blue-700 dark:text-blue-300">Nouveaux</span>
                <span class="flex h-7 w-7 items-center justify-center rounded-lg bg-blue-100 text-blue-700 dark:bg-blue-900/40 dark:text-blue-300">
                    <x-icon name="sparkles" class="h-4 w-4" />
                </span>
            </div>
            <p class="mt-3 text-3xl font-black text-blue-700 dark:text-blue-300">{{ $nouveaux }}</p>
            <p class="mt-1 text-[11px] text-blue-600/80 dark:text-blue-400/80 flex items-center justify-between">
                <span>Récemment inscrits</span>
                <span class="group-hover:translate-x-0.5 transition-transform">→</span>
            </p>
        </a>

        {{-- Stages en cours --}}
        <a href="{{ route('stagiaires.index', ['status' => 'en_cours']) }}"
           class="group rounded-2xl border border-emerald-200 bg-emerald-50/20 p-5 shadow-xs hover:border-emerald-400 hover:shadow-md transition-all dark:border-emerald-900/40 dark:bg-emerald-950/10">
            <div class="flex items-center justify-between">
                <span class="text-xs font-semibold uppercase tracking-wider text-emerald-700 dark:text-emerald-300">En stage</span>
                <span class="flex h-7 w-7 items-center justify-center rounded-lg bg-emerald-100 text-emerald-700 dark:bg-emerald-900/40 dark:text-emerald-300">
                    <x-icon name="briefcase" class="h-4 w-4" />
                </span>
            </div>
            <p class="mt-3 text-3xl font-black text-emerald-700 dark:text-emerald-300">{{ $enCours }}</p>
            <p class="mt-1 text-[11px] text-emerald-600/80 dark:text-emerald-400/80 flex items-center justify-between">
                <span>Actuellement au centre</span>
                <span class="group-hover:translate-x-0.5 transition-transform">→</span>
            </p>
        </a>

        {{-- Stages terminés --}}
        <a href="{{ route('stagiaires.index', ['status' => 'termine']) }}"
           class="group rounded-2xl border border-slate-200 bg-white p-5 shadow-xs hover:border-slate-400 hover:shadow-md transition-all dark:border-slate-800 dark:bg-slate-900">
            <div class="flex items-center justify-between">
                <span class="text-xs font-semibold uppercase tracking-wider text-slate-600 dark:text-slate-400">Terminés</span>
                <span class="flex h-7 w-7 items-center justify-center rounded-lg bg-slate-100 text-slate-600 dark:bg-slate-800 dark:text-slate-300">
                    <x-icon name="check" class="h-4 w-4" />
                </span>
            </div>
            <p class="mt-3 text-3xl font-black text-slate-800 dark:text-slate-200">{{ $termines }}</p>
            <p class="mt-1 text-[11px] text-slate-400 flex items-center justify-between">
                <span>Période échue</span>
                <span class="group-hover:translate-x-0.5 transition-transform">→</span>
            </p>
        </a>

        {{-- Dossiers complets --}}
        <a href="{{ route('stagiaires.index', ['filter' => 'complets']) }}"
           class="group rounded-2xl border border-emerald-300 bg-emerald-50/40 p-5 shadow-xs hover:border-emerald-500 hover:shadow-md transition-all dark:border-emerald-800/60 dark:bg-emerald-950/20">
            <div class="flex items-center justify-between">
                <span class="text-xs font-semibold uppercase tracking-wider text-emerald-800 dark:text-emerald-300">Dossiers complets</span>
                <span class="flex h-7 w-7 items-center justify-center rounded-lg bg-emerald-200 text-emerald-900 dark:bg-emerald-900 dark:text-emerald-200">
                    <x-icon name="check" class="h-4 w-4" />
                </span>
            </div>
            <p class="mt-3 text-3xl font-black text-emerald-800 dark:text-emerald-200">{{ $dossiersComplets }}</p>
            <p class="mt-1 text-[11px] text-emerald-700/80 dark:text-emerald-300/80 flex items-center justify-between">
                <span>CIN & dates OK</span>
                <span class="group-hover:translate-x-0.5 transition-transform">→</span>
            </p>
        </a>

        {{-- Dossiers incomplets --}}
        <a href="{{ route('stagiaires.index', ['filter' => 'incomplets']) }}"
           class="group rounded-2xl border border-amber-300 bg-amber-50/40 p-5 shadow-xs hover:border-amber-500 hover:shadow-md transition-all dark:border-amber-800/60 dark:bg-amber-950/20">
            <div class="flex items-center justify-between">
                <span class="text-xs font-semibold uppercase tracking-wider text-amber-800 dark:text-amber-300">Incomplets</span>
                <span class="flex h-7 w-7 items-center justify-center rounded-lg bg-amber-200 text-amber-900 dark:bg-amber-900 dark:text-amber-200">
                    <x-icon name="warning" class="h-4 w-4" />
                </span>
            </div>
            <p class="mt-3 text-3xl font-black text-amber-800 dark:text-amber-200">{{ $dossiersIncomplets }}</p>
            <p class="mt-1 text-[11px] text-amber-700/80 dark:text-amber-300/80 flex items-center justify-between">
                <span>Pièces à régulariser</span>
                <span class="group-hover:translate-x-0.5 transition-transform">→</span>
            </p>
        </a>
    </div>

    {{-- ────────────────────────────────────────────────────────────
         ZONE DE GESTION : RACCOURCIS DIRECTS
    ──────────────────────────────────────────────────────────── --}}
    <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
        {{-- Raccourci 1 : Ajouter un stagiaire --}}
        <a href="{{ route('stagiaires.create') }}"
           class="group flex items-start gap-4 rounded-2xl border border-purple-200 bg-gradient-to-br from-purple-50/80 to-white p-5 shadow-xs hover:border-purple-400 hover:shadow-md transition-all dark:border-purple-900/50 dark:from-purple-950/20 dark:to-slate-900">
            <div class="flex h-11 w-11 shrink-0 items-center justify-center rounded-xl bg-purple-600 text-white shadow-2xs group-hover:scale-105 transition-transform">
                <x-icon name="plus" class="h-5 w-5" />
            </div>
            <div class="space-y-0.5 min-w-0">
                <h3 class="text-sm font-bold text-slate-900 dark:text-white group-hover:text-purple-700 dark:group-hover:text-purple-300 transition-colors">
                    Nouveau stagiaire
                </h3>
                <p class="text-xs text-slate-500 dark:text-slate-400 leading-relaxed">
                    Créer un dossier complet avec identité, stage et documents.
                </p>
            </div>
        </a>

        {{-- Raccourci 2 : Traiter les dossiers incomplets --}}
        <a href="{{ route('stagiaires.index', ['filter' => 'incomplets']) }}"
           class="group flex items-start gap-4 rounded-2xl border border-amber-200 bg-gradient-to-br from-amber-50/80 to-white p-5 shadow-xs hover:border-amber-400 hover:shadow-md transition-all dark:border-amber-900/50 dark:from-amber-950/20 dark:to-slate-900">
            <div class="flex h-11 w-11 shrink-0 items-center justify-center rounded-xl bg-amber-500 text-white shadow-2xs group-hover:scale-105 transition-transform">
                <x-icon name="warning" class="h-5 w-5" />
            </div>
            <div class="space-y-0.5 min-w-0">
                <h3 class="text-sm font-bold text-slate-900 dark:text-white group-hover:text-amber-700 dark:group-hover:text-amber-300 transition-colors">
                    Dossiers à compléter
                </h3>
                <p class="text-xs text-slate-500 dark:text-slate-400 leading-relaxed">
                    Identifier les stagiaires sans CIN ou dates de stage.
                </p>
            </div>
        </a>

        {{-- Raccourci 3 : Consulter tous les documents --}}
        <a href="{{ route('stagiaires.documents') }}"
           class="group flex items-start gap-4 rounded-2xl border border-teal-200 bg-gradient-to-br from-teal-50/80 to-white p-5 shadow-xs hover:border-teal-400 hover:shadow-md transition-all dark:border-teal-900/50 dark:from-teal-950/20 dark:to-slate-900">
            <div class="flex h-11 w-11 shrink-0 items-center justify-center rounded-xl bg-teal-600 text-white shadow-2xs group-hover:scale-105 transition-transform">
                <x-icon name="document" class="h-5 w-5" />
            </div>
            <div class="space-y-0.5 min-w-0">
                <h3 class="text-sm font-bold text-slate-900 dark:text-white group-hover:text-teal-700 dark:group-hover:text-teal-300 transition-colors">
                    Espace Documents
                </h3>
                <p class="text-xs text-slate-500 dark:text-slate-400 leading-relaxed">
                    Accès direct aux CIN, diplômes et attestations sécurisés.
                </p>
            </div>
        </a>

        {{-- Raccourci 4 : Changer de contexte (Retour aux domaines) --}}
        <a href="{{ route('super-admin.domaines') }}"
           class="group flex items-start gap-4 rounded-2xl border border-slate-200 bg-gradient-to-br from-slate-50 to-white p-5 shadow-xs hover:border-slate-400 hover:shadow-md transition-all dark:border-slate-800 dark:from-slate-800/40 dark:to-slate-900">
            <div class="flex h-11 w-11 shrink-0 items-center justify-center rounded-xl bg-slate-700 text-white shadow-2xs group-hover:scale-105 transition-transform">
                <x-icon name="arrow-uturn-left" class="h-5 w-5" />
            </div>
            <div class="space-y-0.5 min-w-0">
                <h3 class="text-sm font-bold text-slate-900 dark:text-white group-hover:text-slate-700 dark:group-hover:text-slate-300 transition-colors">
                    Portail Domaines
                </h3>
                <p class="text-xs text-slate-500 dark:text-slate-400 leading-relaxed">
                    Quitter l'espace stagiaires et basculer vers Santé.
                </p>
            </div>
        </a>
    </div>

    {{-- ────────────────────────────────────────────────────────────
         TABLEAU DES DERNIERS STAGIAIRES ENREGISTRÉS
    ──────────────────────────────────────────────────────────── --}}
    <div class="rounded-2xl border border-slate-200 bg-white overflow-hidden shadow-xs dark:border-slate-800 dark:bg-slate-900">
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between border-b border-slate-200/80 px-6 py-4 dark:border-slate-800 gap-3">
            <div>
                <h3 class="text-base font-bold text-slate-900 dark:text-white flex items-center gap-2">
                    <span>Derniers stagiaires enregistrés</span>
                </h3>
                <p class="text-xs text-slate-500 dark:text-slate-400">Dossiers administratifs récemment créés ou mis à jour</p>
            </div>
            <div class="flex items-center gap-2">
                <a href="{{ route('stagiaires.index') }}"
                   class="text-xs font-semibold text-purple-600 hover:text-purple-800 dark:text-purple-400 hover:underline">
                    Voir tous les stagiaires →
                </a>
            </div>
        </div>

        <div class="ec-table-wrap">
            <table class="ec-table">
                <thead>
                    <tr>
                        <th>Stagiaire</th>
                        <th>N° CIN</th>
                        <th>Centre</th>
                        <th>Période du stage</th>
                        <th>Prix convenu</th>
                        <th>Statut</th>
                        <th class="text-right">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($recentStagiaires as $stagiaire)
                        <tr>
                            <td>
                                <div class="font-bold text-slate-900 dark:text-white">
                                    <a href="{{ route('stagiaires.show', $stagiaire) }}" class="hover:text-purple-600 hover:underline dark:hover:text-purple-400">
                                        {{ $stagiaire->last_name }} {{ $stagiaire->first_name }}
                                    </a>
                                </div>
                                <div class="text-[11px] text-slate-500">
                                    {{ $stagiaire->email ?? $stagiaire->phone ?? 'Aucun contact' }}
                                </div>
                            </td>
                            <td>
                                @if($stagiaire->cin_number)
                                    <span class="font-mono text-xs font-semibold text-slate-700 dark:text-slate-300">
                                        {{ $stagiaire->cin_number }}
                                    </span>
                                @else
                                    <span class="text-xs text-amber-600 dark:text-amber-400 italic">Non renseigné</span>
                                @endif
                            </td>
                            <td>
                                <span class="text-xs font-medium text-slate-700 dark:text-slate-300">
                                    {{ $stagiaire->center?->name ?? 'Tous centres' }}
                                </span>
                            </td>
                            <td>
                                @if($stagiaire->stage_start_date && $stagiaire->stage_end_date)
                                    <div class="text-xs font-medium text-slate-800 dark:text-slate-200">
                                        {{ $stagiaire->stage_start_date->format('d/m/Y') }} → {{ $stagiaire->stage_end_date->format('d/m/Y') }}
                                    </div>
                                @else
                                    <span class="text-xs text-slate-400 italic">Dates non définies</span>
                                @endif
                            </td>
                            <td>
                                <span class="inline-flex items-center rounded-lg bg-purple-50 px-2 py-0.5 text-xs font-bold text-purple-900 dark:bg-purple-950/60 dark:text-purple-300 border border-purple-200/60 dark:border-purple-800/60">
                                    {{ number_format((float)$stagiaire->stage_price, 2, ',', ' ') }} {{ $stagiaire->currency ?? 'EUR' }}
                                </span>
                            </td>
                            <td>
                                <span class="inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-semibold {{ $stagiaire->status_badge_class }}">
                                    {{ $stagiaire->status_label }}
                                </span>
                            </td>
                            <td class="text-right">
                                <div class="flex items-center justify-end gap-1.5">
                                    <a href="{{ route('stagiaires.show', $stagiaire) }}"
                                       class="rounded-lg p-1.5 text-slate-500 hover:bg-slate-100 hover:text-purple-600 dark:hover:bg-slate-800 dark:hover:text-purple-400 transition"
                                       title="Consulter le dossier">
                                        <x-icon name="eye" class="h-4 w-4" />
                                    </a>
                                    <a href="{{ route('stagiaires.edit', $stagiaire) }}"
                                       class="rounded-lg p-1.5 text-slate-500 hover:bg-slate-100 hover:text-purple-600 dark:hover:bg-slate-800 dark:hover:text-purple-400 transition"
                                       title="Modifier le stagiaire">
                                        <x-icon name="pencil" class="h-4 w-4" />
                                    </a>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="py-12 text-center text-sm text-slate-500">
                                <span class="text-2xl block mb-2">🧑‍💼</span>
                                Aucun stagiaire enregistré pour l'instant.
                                <div class="mt-3">
                                    <a href="{{ route('stagiaires.create') }}"
                                       class="inline-flex items-center gap-1.5 rounded-xl bg-purple-600 px-4 py-2 text-xs font-bold text-white shadow-xs hover:bg-purple-700 transition">
                                        <x-icon name="plus" class="h-3.5 w-3.5" />
                                        <span>Ajouter le premier stagiaire</span>
                                    </a>
                                </div>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection
