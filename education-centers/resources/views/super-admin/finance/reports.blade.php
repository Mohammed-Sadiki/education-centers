@extends('layouts.app')

@section('title', 'Rapports financiers — Gestion financière')

@section('content')
<div class="space-y-6">

    {{-- En-tête --}}
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 border-b border-slate-200/80 pb-6 dark:border-slate-800">
        <div>
            <div class="inline-flex items-center gap-2 rounded-full bg-emerald-50 border border-emerald-200/70 px-3.5 py-1 text-xs font-semibold text-emerald-800 dark:bg-emerald-950/50 dark:border-emerald-800/70 dark:text-emerald-300 mb-2">
                <span>📊</span>
                <span>Gestion financière · Analyses</span>
            </div>
            <h1 class="text-3xl font-extrabold tracking-tight text-slate-900 dark:text-white">
                Rapports financiers
            </h1>
            <p class="mt-1 text-sm text-slate-600 dark:text-slate-400">
                Exports comptables consolidés, bilans annuels et analyses par filière.
            </p>
        </div>

        <div class="flex items-center gap-2">
            <a href="{{ route('super-admin.finance.dashboard') }}"
               class="inline-flex items-center gap-1.5 rounded-xl border border-slate-200 bg-white px-3.5 py-2 text-sm font-semibold text-slate-700 shadow-2xs hover:bg-slate-50 dark:border-slate-700 dark:bg-slate-800 dark:text-slate-200 transition-colors">
                <x-icon name="arrow-uturn-left" class="h-4 w-4" />
                <span>Retour Dashboard</span>
            </a>
        </div>
    </div>

    {{-- Carte d'état "Bientôt disponible" propre --}}
    <div class="rounded-2xl border border-slate-200 bg-white p-12 text-center shadow-xs dark:border-slate-800 dark:bg-slate-900">
        <div class="mx-auto flex h-16 w-16 items-center justify-center rounded-2xl bg-emerald-50 text-3xl dark:bg-emerald-950/50">
            📊
        </div>
        <h2 class="mt-4 text-xl font-bold text-slate-900 dark:text-white">
            Module de reporting avancé en cours de finalisation
        </h2>
        <p class="mx-auto mt-2 max-w-lg text-sm text-slate-600 dark:text-slate-400">
            Les fonctions d'export comptable PDF/Excel, les graphiques d'évolution mensuelle de trésorerie et les états comparatifs inter-domaines seront disponibles dans la prochaine version.
        </p>
        <div class="mt-6 inline-flex items-center gap-2 rounded-full bg-slate-100 px-3.5 py-1 text-xs font-bold uppercase tracking-wider text-slate-600 dark:bg-slate-800 dark:text-slate-300">
            <span class="h-2 w-2 rounded-full bg-emerald-500 animate-pulse"></span>
            Bientôt disponible
        </div>
    </div>

</div>
@endsection
