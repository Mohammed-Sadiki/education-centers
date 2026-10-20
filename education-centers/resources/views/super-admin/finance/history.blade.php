@extends('layouts.app')

@section('title', 'Historique des paiements — Gestion financière')

@section('content')
<div class="space-y-6">

    {{-- En-tête --}}
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 border-b border-slate-200/80 pb-6 dark:border-slate-800">
        <div>
            <div class="inline-flex items-center gap-2 rounded-full bg-emerald-50 border border-emerald-200/70 px-3.5 py-1 text-xs font-semibold text-emerald-800 dark:bg-emerald-950/50 dark:border-emerald-800/70 dark:text-emerald-300 mb-2">
                <span>💰</span>
                <span>Gestion financière · Journal des encaissements</span>
            </div>
            <h1 class="text-3xl font-extrabold tracking-tight text-slate-900 dark:text-white">
                Historique des paiements
            </h1>
            <p class="mt-1 text-sm text-slate-600 dark:text-slate-400">
                Chaque transaction enregistrée avec référence, date, mode et utilisateur ayant opéré la saisie.
            </p>
        </div>

        <div class="flex items-center gap-2">
            <a href="{{ route('super-admin.finance.dashboard') }}"
               class="inline-flex items-center gap-1.5 rounded-xl border border-slate-200 bg-white px-3.5 py-2 text-sm font-semibold text-slate-700 shadow-2xs hover:bg-slate-50 dark:border-slate-700 dark:bg-slate-800 dark:text-slate-200 transition-colors">
                <x-icon name="arrow-uturn-left" class="h-4 w-4" />
                <span>Retour Dashboard financier</span>
            </a>
        </div>
    </div>

    {{-- Filtres --}}
    <form method="GET" action="{{ route('super-admin.finance.history') }}" class="rounded-2xl border border-slate-200 bg-white p-5 shadow-xs dark:border-slate-800 dark:bg-slate-900">
        <div class="grid gap-4 sm:grid-cols-3">
            <div>
                <label for="filter-domain" class="block text-xs font-semibold uppercase tracking-wider text-slate-600 dark:text-slate-400 mb-1.5">
                    Domaine
                </label>
                <select name="domain" id="filter-domain" onchange="this.form.submit()"
                        class="w-full rounded-xl border-slate-300 bg-white text-sm font-medium text-slate-800 focus:border-emerald-500 focus:ring-emerald-500 dark:border-slate-700 dark:bg-slate-800 dark:text-slate-100">
                    <option value="all" {{ $domain === 'all' ? 'selected' : '' }}>Tous les domaines</option>
                    @foreach($domains as $slug => $label)
                        <option value="{{ $slug }}" {{ $domain === $slug ? 'selected' : '' }}>{{ $label }}</option>
                    @endforeach
                </select>
            </div>

            <div>
                <label for="filter-school-year" class="block text-xs font-semibold uppercase tracking-wider text-slate-600 dark:text-slate-400 mb-1.5">
                    Année scolaire
                </label>
                <select name="school_year" id="filter-school-year" onchange="this.form.submit()"
                        class="w-full rounded-xl border-slate-300 bg-white text-sm font-medium text-slate-800 focus:border-emerald-500 focus:ring-emerald-500 dark:border-slate-700 dark:bg-slate-800 dark:text-slate-100">
                    <option value="all" {{ $schoolYear === 'all' ? 'selected' : '' }}>Toutes les années</option>
                    @foreach($schoolYears as $sy)
                        <option value="{{ $sy }}" {{ $schoolYear === $sy ? 'selected' : '' }}>{{ $sy }}</option>
                    @endforeach
                </select>
            </div>

            <div>
                <label for="filter-center" class="block text-xs font-semibold uppercase tracking-wider text-slate-600 dark:text-slate-400 mb-1.5">
                    Centre
                </label>
                <select name="center_id" id="filter-center" onchange="this.form.submit()"
                        class="w-full rounded-xl border-slate-300 bg-white text-sm font-medium text-slate-800 focus:border-emerald-500 focus:ring-emerald-500 dark:border-slate-700 dark:bg-slate-800 dark:text-slate-100">
                    <option value="">Tous les centres</option>
                    @foreach($centers as $c)
                        <option value="{{ $c->id }}" {{ $centerId === $c->id ? 'selected' : '' }}>{{ $c->name }} ({{ $c->code }})</option>
                    @endforeach
                </select>
            </div>
        </div>
    </form>

    {{-- Tableau d'historique --}}
    <div class="rounded-2xl border border-slate-200 bg-white shadow-xs dark:border-slate-800 dark:bg-slate-900 overflow-hidden">
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-slate-200 dark:divide-slate-800 text-left text-sm">
                <thead class="bg-slate-50/80 text-xs font-bold uppercase tracking-wider text-slate-500 dark:bg-slate-800/50 dark:text-slate-400">
                    <tr>
                        <th class="px-5 py-3.5">Référence</th>
                        <th class="px-5 py-3.5">Date</th>
                        <th class="px-5 py-3.5">Étudiant</th>
                        <th class="px-5 py-3.5">Centre</th>
                        <th class="px-5 py-3.5">Domaine / Formation</th>
                        <th class="px-5 py-3.5">Méthode</th>
                        <th class="px-5 py-3.5 text-right">Montant</th>
                        <th class="px-5 py-3.5 text-center">Statut</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100 dark:divide-slate-800/80">
                    @forelse($payments as $p)
                        <tr class="hover:bg-slate-50/60 dark:hover:bg-slate-800/40 transition-colors">
                            <td class="px-5 py-4 font-mono font-bold text-xs text-slate-900 dark:text-white">
                                {{ $p->reference ?? '—' }}
                            </td>
                            <td class="px-5 py-4 text-xs text-slate-600 dark:text-slate-300">
                                {{ $p->payment_date?->format('d/m/Y') ?? '—' }}
                            </td>
                            <td class="px-5 py-4 font-semibold text-slate-900 dark:text-white">
                                {{ $p->student?->full_name ?? '—' }}
                            </td>
                            <td class="px-5 py-4 text-xs text-slate-600 dark:text-slate-300">
                                <span class="rounded-md bg-slate-100 px-2 py-0.5 font-medium dark:bg-slate-800">
                                    {{ $p->center?->name ?? 'N/A' }}
                                </span>
                            </td>
                            <td class="px-5 py-4 text-xs text-slate-600 dark:text-slate-300">
                                {{ $p->formation?->name ?? ($domains[$p->domain] ?? '—') }}
                            </td>
                            <td class="px-5 py-4 text-xs">
                                <span class="rounded-md bg-slate-100 px-2 py-1 font-medium text-slate-700 dark:bg-slate-800 dark:text-slate-300">
                                    {{ $p->method_label }}
                                </span>
                            </td>
                            <td class="px-5 py-4 text-right font-black text-emerald-600 dark:text-emerald-400">
                                {{ format_price($p->amount, $p->currency ?? 'MAD') }}
                            </td>
                            <td class="px-5 py-4 text-center">
                                <span class="inline-flex items-center gap-1 rounded-full px-2.5 py-0.5 text-xs font-bold {{ $p->status === 'paid' ? 'bg-emerald-100 text-emerald-800 dark:bg-emerald-900/50 dark:text-emerald-300' : 'bg-slate-100 text-slate-800' }}">
                                    {{ $p->status_label }}
                                </span>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="8" class="px-5 py-8 text-center text-sm text-slate-500 dark:text-slate-400">
                                Aucun paiement enregistré.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if($payments->hasPages())
            <div class="p-4 border-t border-slate-200 dark:border-slate-800">
                {{ $payments->links() }}
            </div>
        @endif
    </div>

</div>
@endsection
