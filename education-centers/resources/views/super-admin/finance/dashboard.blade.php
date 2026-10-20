@extends('layouts.app')

@section('title', 'Gestion financière globale — Super Admin')

@section('content')
<div class="space-y-8" x-data="{
    paymentModalOpen: false,
    salaryModalOpen: false,
    selectedStudent: '',
    selectedFormation: '',
    selectedTeacher: '',
    prefilledExpectedSalary: ''
}">

    {{-- En-tête avec titre, contexte et filtres rapides --}}
    <div class="flex flex-col lg:flex-row lg:items-center lg:justify-between gap-4 border-b border-slate-200/80 pb-6 dark:border-slate-800">
        <div>
            <div class="inline-flex items-center gap-2 rounded-full bg-emerald-50 border border-emerald-200/70 px-3.5 py-1 text-xs font-semibold text-emerald-800 dark:bg-emerald-950/50 dark:border-emerald-800/70 dark:text-emerald-300 mb-2 shadow-2xs">
                <span>💰</span>
                <span>Contexte Global de la Plateforme</span>
            </div>
            <h1 class="text-3xl font-extrabold tracking-tight text-slate-900 dark:text-white flex items-center gap-3">
                Gestion financière
                <span class="text-base font-normal text-slate-500 dark:text-slate-400">· Vue globale</span>
            </h1>
            <p class="mt-1 text-sm text-slate-600 dark:text-slate-400">
                Suivi consolidé de l'ensemble des encaissements étudiants, reliquats de scolarité et salaires de l'équipe pédagogique.
            </p>
        </div>

        <div class="flex flex-wrap items-center gap-2">
            <button type="button"
                    @click="paymentModalOpen = true"
                    class="inline-flex items-center gap-2 rounded-xl bg-emerald-600 px-4 py-2.5 text-sm font-semibold text-white shadow-sm hover:bg-emerald-500 transition-colors">
                <x-icon name="plus" class="h-4 w-4" />
                <span>Encaisser paiement</span>
            </button>
            <button type="button"
                    @click="salaryModalOpen = true"
                    class="inline-flex items-center gap-2 rounded-xl border border-slate-300 bg-white px-4 py-2.5 text-sm font-semibold text-slate-700 shadow-2xs hover:bg-slate-50 dark:border-slate-700 dark:bg-slate-800 dark:text-slate-200 dark:hover:bg-slate-700 transition-colors">
                <x-icon name="banknotes" class="h-4 w-4 text-emerald-600 dark:text-emerald-400" />
                <span>Payer un salaire</span>
            </button>
            <a href="{{ route('super-admin.domaines') }}"
               class="inline-flex items-center gap-1.5 rounded-xl border border-slate-200 bg-white px-3.5 py-2.5 text-sm font-semibold text-slate-700 shadow-2xs hover:bg-slate-50 dark:border-slate-700 dark:bg-slate-800 dark:text-slate-200 transition-colors">
                <x-icon name="arrow-uturn-left" class="h-4 w-4" />
                <span>Changer de contexte</span>
            </a>
        </div>
    </div>

    {{-- BARRE DE FILTRES UNIFIÉE (Domaine + Année scolaire + Mois + Centre) --}}
    <form method="GET" action="{{ route('super-admin.finance.dashboard') }}" class="rounded-2xl border border-slate-200 bg-white p-5 shadow-xs dark:border-slate-800 dark:bg-slate-900">
        <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
            {{-- Filtre Domaine --}}
            <div>
                <label for="filter-domain" class="block text-xs font-semibold uppercase tracking-wider text-slate-600 dark:text-slate-400 mb-1.5">
                    Domaine de formation
                </label>
                <select name="domain" id="filter-domain" onchange="this.form.submit()"
                        class="w-full rounded-xl border-slate-300 bg-white text-sm font-medium text-slate-800 focus:border-emerald-500 focus:ring-emerald-500 dark:border-slate-700 dark:bg-slate-800 dark:text-slate-100">
                    <option value="all" {{ $domain === 'all' ? 'selected' : '' }}>Tous les domaines (Global)</option>
                    @foreach($domains as $slug => $label)
                        <option value="{{ $slug }}" {{ $domain === $slug ? 'selected' : '' }}>{{ $label }}</option>
                    @endforeach
                </select>
            </div>

            {{-- Filtre Année scolaire --}}
            <div>
                <label for="filter-school-year" class="block text-xs font-semibold uppercase tracking-wider text-slate-600 dark:text-slate-400 mb-1.5">
                    Année scolaire
                </label>
                <select name="school_year" id="filter-school-year" onchange="this.form.submit()"
                        class="w-full rounded-xl border-slate-300 bg-white text-sm font-medium text-slate-800 focus:border-emerald-500 focus:ring-emerald-500 dark:border-slate-700 dark:bg-slate-800 dark:text-slate-100">
                    @foreach($schoolYears as $sy)
                        <option value="{{ $sy }}" {{ $schoolYear === $sy ? 'selected' : '' }}>{{ $sy }}</option>
                    @endforeach
                </select>
            </div>

            {{-- Filtre Mois (pour les salaires professeurs) --}}
            <div>
                <label for="filter-month" class="block text-xs font-semibold uppercase tracking-wider text-slate-600 dark:text-slate-400 mb-1.5">
                    Mois des salaires
                </label>
                <div class="grid grid-cols-2 gap-2">
                    <select name="month" id="filter-month" onchange="this.form.submit()"
                            class="w-full rounded-xl border-slate-300 bg-white text-sm font-medium text-slate-800 focus:border-emerald-500 focus:ring-emerald-500 dark:border-slate-700 dark:bg-slate-800 dark:text-slate-100">
                        @php
                            $monthsNames = [
                                1 => 'Janvier', 2 => 'Février', 3 => 'Mars', 4 => 'Avril',
                                5 => 'Mai', 6 => 'Juin', 7 => 'Juillet', 8 => 'Août',
                                9 => 'Septembre', 10 => 'Octobre', 11 => 'Novembre', 12 => 'Décembre',
                            ];
                        @endphp
                        @foreach($monthsNames as $mNum => $mLabel)
                            <option value="{{ $mNum }}" {{ $month === $mNum ? 'selected' : '' }}>{{ $mLabel }}</option>
                        @endforeach
                    </select>
                    <select name="year" id="filter-year" onchange="this.form.submit()"
                            class="w-full rounded-xl border-slate-300 bg-white text-sm font-medium text-slate-800 focus:border-emerald-500 focus:ring-emerald-500 dark:border-slate-700 dark:bg-slate-800 dark:text-slate-100">
                        @foreach([2026, 2027, 2028] as $y)
                            <option value="{{ $y }}" {{ $year === $y ? 'selected' : '' }}>{{ $y }}</option>
                        @endforeach
                    </select>
                </div>
            </div>

            {{-- Filtre Centre (Multi-centre) --}}
            <div>
                <label for="filter-center" class="block text-xs font-semibold uppercase tracking-wider text-slate-600 dark:text-slate-400 mb-1.5">
                    Centre éducatif
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

        @if($domain !== 'all' || $centerId)
            <div class="mt-3 flex items-center justify-between border-t border-slate-100 pt-3 dark:border-slate-800 text-xs text-slate-500">
                <span>Filtre actif : <strong class="text-slate-700 dark:text-slate-200">{{ $domain !== 'all' ? ($domains[$domain] ?? $domain) : 'Tous les domaines' }}</strong> {{ $centerId ? ' · Centre filtré' : '' }}</span>
                <a href="{{ route('super-admin.finance.dashboard') }}" class="font-semibold text-emerald-600 hover:text-emerald-700 hover:underline">Réinitialiser</a>
            </div>
        @endif
    </form>

    {{-- ────────────────────────────────────────────────────────────
         5 CARTES KPI PRINCIPALES (Exigence Spec)
    ──────────────────────────────────────────────────────────── --}}
    <div class="grid gap-5 sm:grid-cols-2 lg:grid-cols-5">

        {{-- KPI 1 : Total Encaissé --}}
        <div class="relative overflow-hidden rounded-2xl border-2 border-emerald-300 bg-white p-5 shadow-sm dark:border-emerald-800/80 dark:bg-slate-900">
            <div class="flex items-center justify-between">
                <span class="text-xs font-bold uppercase tracking-wider text-emerald-700 dark:text-emerald-400">Total encaissé</span>
                <span class="flex h-9 w-9 items-center justify-center rounded-xl bg-emerald-100 text-emerald-600 dark:bg-emerald-950/60 dark:text-emerald-300">
                    💰
                </span>
            </div>
            <div class="mt-3 text-2xl font-black tracking-tight text-slate-900 dark:text-white">
                {{ format_price($kpis['total_encaisse'], 'MAD') }}
            </div>
            <p class="mt-1 text-xs text-slate-500 dark:text-slate-400">
                Montant réellement payé par les étudiants
            </p>
        </div>

        {{-- KPI 2 : Total Restant à encaisser --}}
        <div class="relative overflow-hidden rounded-2xl border border-amber-300 bg-white p-5 shadow-sm dark:border-amber-800/80 dark:bg-slate-900">
            <div class="flex items-center justify-between">
                <span class="text-xs font-bold uppercase tracking-wider text-amber-700 dark:text-amber-400">Restant à encaisser</span>
                <span class="flex h-9 w-9 items-center justify-center rounded-xl bg-amber-100 text-amber-600 dark:bg-amber-950/60 dark:text-amber-300">
                    ⏳
                </span>
            </div>
            <div class="mt-3 text-2xl font-black tracking-tight text-amber-600 dark:text-amber-400">
                {{ format_price($kpis['total_restant_etudiants'], 'MAD') }}
            </div>
            <p class="mt-1 text-xs text-slate-500 dark:text-slate-400">
                Impayés et reliquats à régler
            </p>
        </div>

        {{-- KPI 3 : Total Facturé / Attendu --}}
        <div class="relative overflow-hidden rounded-2xl border border-slate-200 bg-white p-5 shadow-sm dark:border-slate-800 dark:bg-slate-900">
            <div class="flex items-center justify-between">
                <span class="text-xs font-bold uppercase tracking-wider text-slate-600 dark:text-slate-400">Total attendu / prévu</span>
                <span class="flex h-9 w-9 items-center justify-center rounded-xl bg-slate-100 text-slate-600 dark:bg-slate-800 dark:text-slate-300">
                    🎓
                </span>
            </div>
            <div class="mt-3 text-2xl font-black tracking-tight text-slate-900 dark:text-white">
                {{ format_price($kpis['total_attendu'], 'MAD') }}
            </div>
            <p class="mt-1 text-xs text-slate-500 dark:text-slate-400">
                Prix annuel des scolarités inscrites
            </p>
        </div>

        {{-- KPI 4 : Salaires Professeurs Payés --}}
        <div class="relative overflow-hidden rounded-2xl border border-blue-300 bg-white p-5 shadow-sm dark:border-blue-800/80 dark:bg-slate-900">
            <div class="flex items-center justify-between">
                <span class="text-xs font-bold uppercase tracking-wider text-blue-700 dark:text-blue-400">Salaires payés</span>
                <span class="flex h-9 w-9 items-center justify-center rounded-xl bg-blue-100 text-blue-600 dark:bg-blue-950/60 dark:text-blue-300">
                    👨‍🏫
                </span>
            </div>
            <div class="mt-3 text-2xl font-black tracking-tight text-blue-600 dark:text-blue-400">
                {{ format_price($kpis['salaires_payes'], 'MAD') }}
            </div>
            <p class="mt-1 text-xs text-slate-500 dark:text-slate-400">
                Salaires virés pour {{ $monthsNames[$month] ?? $month }} {{ $year }}
            </p>
        </div>

        {{-- KPI 5 : Salaires Professeurs Restant à Payer --}}
        <div class="relative overflow-hidden rounded-2xl border border-rose-300 bg-white p-5 shadow-sm dark:border-rose-800/80 dark:bg-slate-900">
            <div class="flex items-center justify-between">
                <span class="text-xs font-bold uppercase tracking-wider text-rose-700 dark:text-rose-400">Salaires restant à payer</span>
                <span class="flex h-9 w-9 items-center justify-center rounded-xl bg-rose-100 text-rose-600 dark:bg-rose-950/60 dark:text-rose-300">
                    ⏳
                </span>
            </div>
            <div class="mt-3 text-2xl font-black tracking-tight text-rose-600 dark:text-rose-400">
                {{ format_price($kpis['salaires_restant'], 'MAD') }}
            </div>
            <p class="mt-1 text-xs text-slate-500 dark:text-slate-400">
                À verser pour le mois sélectionné
            </p>
        </div>
    </div>

    {{-- ────────────────────────────────────────────────────────────
         SECTION 1 : PAIEMENTS ÉTUDIANTS (TABLEAU COMPLET)
    ──────────────────────────────────────────────────────────── --}}
    <div class="rounded-2xl border border-slate-200 bg-white shadow-xs dark:border-slate-800 dark:bg-slate-900">
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between border-b border-slate-200/80 p-5 dark:border-slate-800 gap-3">
            <div>
                <h2 class="text-lg font-bold text-slate-900 dark:text-white flex items-center gap-2">
                    <span class="flex h-2.5 w-2.5 rounded-full bg-emerald-500"></span>
                    Paiements étudiants
                </h2>
                <p class="text-xs text-slate-500 dark:text-slate-400">
                    Année scolaire <strong class="text-slate-700 dark:text-slate-200">{{ $schoolYear }}</strong> · {{ $studentRows->count() }} inscription(s)
                </p>
            </div>
            <div class="flex items-center gap-2">
                <a href="{{ route('super-admin.finance.students', ['domain' => $domain, 'school_year' => $schoolYear]) }}"
                   class="text-xs font-semibold text-emerald-600 hover:text-emerald-700 hover:underline dark:text-emerald-400">
                    Voir la vue détaillée →
                </a>
            </div>
        </div>

        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-slate-200 dark:divide-slate-800 text-left text-sm">
                <thead class="bg-slate-50/80 text-xs font-bold uppercase tracking-wider text-slate-500 dark:bg-slate-800/50 dark:text-slate-400">
                    <tr>
                        <th class="px-5 py-3.5">Étudiant</th>
                        <th class="px-5 py-3.5">Centre</th>
                        <th class="px-5 py-3.5">Domaine</th>
                        <th class="px-5 py-3.5">Formation</th>
                        <th class="px-5 py-3.5 text-right">Prix annuel</th>
                        <th class="px-5 py-3.5 text-right">Payé</th>
                        <th class="px-5 py-3.5 text-right">Reste</th>
                        <th class="px-5 py-3.5 text-center">Statut</th>
                        <th class="px-5 py-3.5 text-right">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100 dark:divide-slate-800/80">
                    @forelse($studentRows as $row)
                        <tr class="hover:bg-slate-50/60 dark:hover:bg-slate-800/40 transition-colors">
                            <td class="px-5 py-4 font-semibold text-slate-900 dark:text-white">
                                <div class="flex items-center gap-3">
                                    <span class="flex h-8 w-8 items-center justify-center rounded-full bg-slate-100 text-slate-600 font-bold text-xs dark:bg-slate-800 dark:text-slate-300">
                                        {{ strtoupper(substr($row->student->first_name, 0, 1) . substr($row->student->last_name, 0, 1)) }}
                                    </span>
                                    <div>
                                        <div class="font-bold">{{ $row->student->full_name }}</div>
                                        <div class="text-xs text-slate-400">{{ $row->student->student_number }}</div>
                                    </div>
                                </div>
                            </td>
                            <td class="px-5 py-4 text-xs text-slate-600 dark:text-slate-300">
                                <span class="rounded-md bg-slate-100 px-2 py-0.5 font-medium dark:bg-slate-800">
                                    {{ $row->center?->name ?? 'N/A' }}
                                </span>
                            </td>
                            <td class="px-5 py-4">
                                <span class="inline-flex items-center rounded-md bg-slate-100 px-2 py-1 text-xs font-semibold text-slate-700 dark:bg-slate-800 dark:text-slate-300">
                                    {{ $row->domain_label }}
                                </span>
                            </td>
                            <td class="px-5 py-4 text-xs font-medium text-slate-700 dark:text-slate-300">
                                {{ $row->formation->name }}
                            </td>
                            <td class="px-5 py-4 text-right font-bold text-slate-900 dark:text-white">
                                {{ format_price($row->annual_price, 'MAD') }}
                            </td>
                            <td class="px-5 py-4 text-right font-black text-emerald-600 dark:text-emerald-400">
                                {{ format_price($row->paid_amount, 'MAD') }}
                            </td>
                            <td class="px-5 py-4 text-right font-black {{ $row->remaining_amount > 0 ? 'text-amber-600 dark:text-amber-400' : 'text-slate-400' }}">
                                {{ format_price($row->remaining_amount, 'MAD') }}
                            </td>
                            <td class="px-5 py-4 text-center">
                                <span class="inline-flex items-center gap-1.5 rounded-full px-2.5 py-1 text-xs font-bold {{ $row->status_badge }}">
                                    @if($row->status === 'paid')
                                        <span class="h-1.5 w-1.5 rounded-full bg-emerald-500"></span>
                                    @elseif($row->status === 'partial')
                                        <span class="h-1.5 w-1.5 rounded-full bg-amber-500"></span>
                                    @else
                                        <span class="h-1.5 w-1.5 rounded-full bg-rose-500"></span>
                                    @endif
                                    {{ $row->status_label }}
                                </span>
                            </td>
                            <td class="px-5 py-4 text-right">
                                <button type="button"
                                        @click="
                                            selectedStudent = '{{ $row->student->id }}';
                                            selectedFormation = '{{ $row->formation->id }}';
                                            paymentModalOpen = true;
                                        "
                                        class="inline-flex items-center gap-1 rounded-lg border border-slate-200 bg-white px-2.5 py-1 text-xs font-semibold text-slate-700 hover:bg-slate-50 dark:border-slate-700 dark:bg-slate-800 dark:text-slate-200 transition-colors shadow-2xs">
                                    <x-icon name="plus" class="h-3.5 w-3.5 text-emerald-600" />
                                    <span>Paiement</span>
                                </button>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="9" class="px-5 py-8 text-center text-sm text-slate-500 dark:text-slate-400">
                                Aucun étudiant trouvé pour les critères sélectionnés.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    {{-- ────────────────────────────────────────────────────────────
         SECTION 2 : SALAIRES PROFESSEURS (TABLEAU MENSUEL)
    ──────────────────────────────────────────────────────────── --}}
    <div class="rounded-2xl border border-slate-200 bg-white shadow-xs dark:border-slate-800 dark:bg-slate-900">
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between border-b border-slate-200/80 p-5 dark:border-slate-800 gap-3">
            <div>
                <h2 class="text-lg font-bold text-slate-900 dark:text-white flex items-center gap-2">
                    <span class="flex h-2.5 w-2.5 rounded-full bg-blue-500"></span>
                    Salaires des professeurs
                </h2>
                <p class="text-xs text-slate-500 dark:text-slate-400">
                    Mois de <strong class="text-slate-700 dark:text-slate-200">{{ $monthsNames[$month] ?? $month }} {{ $year }}</strong> · {{ $teacherRows->count() }} enseignant(s)
                </p>
            </div>
            <div class="flex items-center gap-2">
                <a href="{{ route('super-admin.finance.teachers', ['domain' => $domain, 'month' => $month, 'year' => $year]) }}"
                   class="text-xs font-semibold text-blue-600 hover:text-blue-700 hover:underline dark:text-blue-400">
                    Voir la vue détaillée →
                </a>
            </div>
        </div>

        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-slate-200 dark:divide-slate-800 text-left text-sm">
                <thead class="bg-slate-50/80 text-xs font-bold uppercase tracking-wider text-slate-500 dark:bg-slate-800/50 dark:text-slate-400">
                    <tr>
                        <th class="px-5 py-3.5">Professeur</th>
                        <th class="px-5 py-3.5">Centre</th>
                        <th class="px-5 py-3.5">Domaine</th>
                        <th class="px-5 py-3.5">Mois</th>
                        <th class="px-5 py-3.5 text-right">Salaire mensuel</th>
                        <th class="px-5 py-3.5 text-right">Payé</th>
                        <th class="px-5 py-3.5 text-right">Reste</th>
                        <th class="px-5 py-3.5 text-center">Statut</th>
                        <th class="px-5 py-3.5 text-right">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100 dark:divide-slate-800/80">
                    @forelse($teacherRows as $row)
                        <tr class="hover:bg-slate-50/60 dark:hover:bg-slate-800/40 transition-colors">
                            <td class="px-5 py-4 font-semibold text-slate-900 dark:text-white">
                                <div class="flex items-center gap-3">
                                    <span class="flex h-8 w-8 items-center justify-center rounded-full bg-blue-50 text-blue-700 font-bold text-xs dark:bg-blue-950/60 dark:text-blue-300">
                                        {{ strtoupper(substr($row->teacher->first_name, 0, 1) . substr($row->teacher->last_name, 0, 1)) }}
                                    </span>
                                    <div>
                                        <div class="font-bold">{{ $row->teacher->full_name }}</div>
                                        <div class="text-xs text-slate-400">{{ $row->teacher->teacher_number }}</div>
                                    </div>
                                </div>
                            </td>
                            <td class="px-5 py-4 text-xs text-slate-600 dark:text-slate-300">
                                <span class="rounded-md bg-slate-100 px-2 py-0.5 font-medium dark:bg-slate-800">
                                    {{ $row->center?->name ?? 'N/A' }}
                                </span>
                            </td>
                            <td class="px-5 py-4">
                                <span class="inline-flex items-center rounded-md bg-slate-100 px-2 py-1 text-xs font-semibold text-slate-700 dark:bg-slate-800 dark:text-slate-300">
                                    {{ $row->domain_label }}
                                </span>
                            </td>
                            <td class="px-5 py-4 text-xs font-semibold text-slate-700 dark:text-slate-300">
                                {{ $monthsNames[$month] ?? $month }} {{ $year }}
                            </td>
                            <td class="px-5 py-4 text-right font-bold text-slate-900 dark:text-white">
                                {{ format_price($row->monthly_salary, 'MAD') }}
                            </td>
                            <td class="px-5 py-4 text-right font-black text-emerald-600 dark:text-emerald-400">
                                {{ format_price($row->paid_amount, 'MAD') }}
                            </td>
                            <td class="px-5 py-4 text-right font-black {{ $row->remaining_amount > 0 ? 'text-rose-600 dark:text-rose-400' : 'text-slate-400' }}">
                                {{ format_price($row->remaining_amount, 'MAD') }}
                            </td>
                            <td class="px-5 py-4 text-center">
                                <span class="inline-flex items-center gap-1.5 rounded-full px-2.5 py-1 text-xs font-bold {{ $row->status_badge }}">
                                    @if($row->status === 'paid')
                                        <span class="h-1.5 w-1.5 rounded-full bg-emerald-500"></span>
                                    @elseif($row->status === 'partial')
                                        <span class="h-1.5 w-1.5 rounded-full bg-amber-500"></span>
                                    @else
                                        <span class="h-1.5 w-1.5 rounded-full bg-rose-500"></span>
                                    @endif
                                    {{ $row->status_label }}
                                </span>
                            </td>
                            <td class="px-5 py-4 text-right">
                                <button type="button"
                                        @click="
                                            selectedTeacher = '{{ $row->teacher->id }}';
                                            prefilledExpectedSalary = '{{ $row->monthly_salary }}';
                                            salaryModalOpen = true;
                                        "
                                        class="inline-flex items-center gap-1 rounded-lg border border-slate-200 bg-white px-2.5 py-1 text-xs font-semibold text-slate-700 hover:bg-slate-50 dark:border-slate-700 dark:bg-slate-800 dark:text-slate-200 transition-colors shadow-2xs">
                                    <x-icon name="banknotes" class="h-3.5 w-3.5 text-blue-600" />
                                    <span>Régler</span>
                                </button>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="9" class="px-5 py-8 text-center text-sm text-slate-500 dark:text-slate-400">
                                Aucun professeur trouvé pour les critères sélectionnés.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    {{-- ────────────────────────────────────────────────────────────
         MODAL 1 : ENCAISSER PAIEMENT ÉTUDIANT
    ──────────────────────────────────────────────────────────── --}}
    <div x-show="paymentModalOpen" x-cloak class="relative z-50" aria-labelledby="modal-payment-title" role="dialog" aria-modal="true">
        <div class="fixed inset-0 bg-slate-900/60 backdrop-blur-xs transition-opacity" @click="paymentModalOpen = false"></div>
        <div class="fixed inset-0 z-10 overflow-y-auto p-4 sm:p-6 lg:p-8 flex min-h-full items-center justify-center">
            <div class="relative transform overflow-hidden rounded-2xl bg-white p-6 text-left shadow-2xl transition-all sm:w-full sm:max-w-lg dark:bg-slate-900 border border-slate-200 dark:border-slate-800"
                 @click.outside="paymentModalOpen = false">
                
                <div class="flex items-center justify-between pb-4 border-b border-slate-200 dark:border-slate-800">
                    <h3 class="text-lg font-bold text-slate-900 dark:text-white flex items-center gap-2" id="modal-payment-title">
                        <span>💰</span> Enregistrer un paiement étudiant
                    </h3>
                    <button type="button" @click="paymentModalOpen = false" class="text-slate-400 hover:text-slate-600">
                        <x-icon name="x" class="h-5 w-5" />
                    </button>
                </div>

                <form method="POST" action="{{ route('super-admin.finance.students.payment.store') }}" class="mt-4 space-y-4">
                    @csrf
                    <input type="hidden" name="currency" value="MAD">

                    <div>
                        <label class="block text-xs font-semibold uppercase tracking-wider text-slate-700 dark:text-slate-300 mb-1">
                            Étudiant <span class="text-red-500">*</span>
                        </label>
                        <select name="student_id" x-model="selectedStudent" required
                                class="w-full rounded-xl border-slate-300 bg-white text-sm text-slate-800 focus:border-emerald-500 focus:ring-emerald-500 dark:border-slate-700 dark:bg-slate-800 dark:text-slate-100">
                            <option value="">Sélectionner un étudiant...</option>
                            @foreach($studentsList as $st)
                                <option value="{{ $st->id }}">{{ $st->full_name }} ({{ $st->student_number }} - {{ $st->center?->name }})</option>
                            @endforeach
                        </select>
                    </div>

                    <div class="grid grid-cols-2 gap-3">
                        <div>
                            <label class="block text-xs font-semibold uppercase tracking-wider text-slate-700 dark:text-slate-300 mb-1">
                                Formation
                            </label>
                            <select name="formation_id" x-model="selectedFormation"
                                    class="w-full rounded-xl border-slate-300 bg-white text-sm text-slate-800 focus:border-emerald-500 focus:ring-emerald-500 dark:border-slate-700 dark:bg-slate-800 dark:text-slate-100">
                                <option value="">Optionnelle / Globale</option>
                                @foreach($formationsList as $f)
                                    <option value="{{ $f->id }}">{{ $f->name }} ({{ $f->domain }})</option>
                                @endforeach
                            </select>
                        </div>

                        <div>
                            <label class="block text-xs font-semibold uppercase tracking-wider text-slate-700 dark:text-slate-300 mb-1">
                                Année scolaire <span class="text-red-500">*</span>
                            </label>
                            <select name="school_year" required
                                    class="w-full rounded-xl border-slate-300 bg-white text-sm text-slate-800 focus:border-emerald-500 focus:ring-emerald-500 dark:border-slate-700 dark:bg-slate-800 dark:text-slate-100">
                                @foreach($schoolYears as $sy)
                                    <option value="{{ $sy }}" {{ $schoolYear === $sy ? 'selected' : '' }}>{{ $sy }}</option>
                                @endforeach
                            </select>
                        </div>
                    </div>

                    <div class="grid grid-cols-2 gap-3">
                        <div>
                            <label class="block text-xs font-semibold uppercase tracking-wider text-slate-700 dark:text-slate-300 mb-1">
                                Montant (DH) <span class="text-red-500">*</span>
                            </label>
                            <input type="number" step="0.01" min="1" name="amount" required placeholder="Ex: 3000"
                                   class="w-full rounded-xl border-slate-300 bg-white text-sm text-slate-800 focus:border-emerald-500 focus:ring-emerald-500 dark:border-slate-700 dark:bg-slate-800 dark:text-slate-100">
                        </div>

                        <div>
                            <label class="block text-xs font-semibold uppercase tracking-wider text-slate-700 dark:text-slate-300 mb-1">
                                Date de paiement <span class="text-red-500">*</span>
                            </label>
                            <input type="date" name="payment_date" value="{{ now()->toDateString() }}" required
                                   class="w-full rounded-xl border-slate-300 bg-white text-sm text-slate-800 focus:border-emerald-500 focus:ring-emerald-500 dark:border-slate-700 dark:bg-slate-800 dark:text-slate-100">
                        </div>
                    </div>

                    <div class="grid grid-cols-2 gap-3">
                        <div>
                            <label class="block text-xs font-semibold uppercase tracking-wider text-slate-700 dark:text-slate-300 mb-1">
                                Méthode de paiement <span class="text-red-500">*</span>
                            </label>
                            <select name="method" required
                                    class="w-full rounded-xl border-slate-300 bg-white text-sm text-slate-800 focus:border-emerald-500 focus:ring-emerald-500 dark:border-slate-700 dark:bg-slate-800 dark:text-slate-100">
                                <option value="cash">Espèces</option>
                                <option value="bank_transfer">Virement bancaire</option>
                                <option value="card">Carte bancaire</option>
                                <option value="other">Autre</option>
                            </select>
                        </div>

                        <div>
                            <label class="block text-xs font-semibold uppercase tracking-wider text-slate-700 dark:text-slate-300 mb-1">
                                Référence (optionnel)
                            </label>
                            <input type="text" name="reference" placeholder="Ex: VIR-2026-001"
                                   class="w-full rounded-xl border-slate-300 bg-white text-sm text-slate-800 focus:border-emerald-500 focus:ring-emerald-500 dark:border-slate-700 dark:bg-slate-800 dark:text-slate-100">
                        </div>
                    </div>

                    <div>
                        <label class="block text-xs font-semibold uppercase tracking-wider text-slate-700 dark:text-slate-300 mb-1">
                            Notes / Observations
                        </label>
                        <textarea name="notes" rows="2" placeholder="Informations complémentaires..."
                                  class="w-full rounded-xl border-slate-300 bg-white text-sm text-slate-800 focus:border-emerald-500 focus:ring-emerald-500 dark:border-slate-700 dark:bg-slate-800 dark:text-slate-100"></textarea>
                    </div>

                    <div class="pt-4 border-t border-slate-200 dark:border-slate-800 flex justify-end gap-2">
                        <button type="button" @click="paymentModalOpen = false" class="rounded-xl border border-slate-300 px-4 py-2 text-sm font-semibold text-slate-700 hover:bg-slate-50 dark:border-slate-700 dark:text-slate-300">
                            Annuler
                        </button>
                        <button type="submit" class="rounded-xl bg-emerald-600 px-4 py-2 text-sm font-semibold text-white hover:bg-emerald-500 shadow-sm">
                            Enregistrer le paiement
                        </button>
                    </div>
                </form>

            </div>
        </div>
    </div>

    {{-- ────────────────────────────────────────────────────────────
         MODAL 2 : VERSER SALAIRE PROFESSEUR
    ──────────────────────────────────────────────────────────── --}}
    <div x-show="salaryModalOpen" x-cloak class="relative z-50" aria-labelledby="modal-salary-title" role="dialog" aria-modal="true">
        <div class="fixed inset-0 bg-slate-900/60 backdrop-blur-xs transition-opacity" @click="salaryModalOpen = false"></div>
        <div class="fixed inset-0 z-10 overflow-y-auto p-4 sm:p-6 lg:p-8 flex min-h-full items-center justify-center">
            <div class="relative transform overflow-hidden rounded-2xl bg-white p-6 text-left shadow-2xl transition-all sm:w-full sm:max-w-lg dark:bg-slate-900 border border-slate-200 dark:border-slate-800"
                 @click.outside="salaryModalOpen = false">
                
                <div class="flex items-center justify-between pb-4 border-b border-slate-200 dark:border-slate-800">
                    <h3 class="text-lg font-bold text-slate-900 dark:text-white flex items-center gap-2" id="modal-salary-title">
                        <span>👨‍🏫</span> Régler un salaire professeur
                    </h3>
                    <button type="button" @click="salaryModalOpen = false" class="text-slate-400 hover:text-slate-600">
                        <x-icon name="x" class="h-5 w-5" />
                    </button>
                </div>

                <form method="POST" action="{{ route('super-admin.finance.teachers.salary.store') }}" class="mt-4 space-y-4">
                    @csrf
                    <input type="hidden" name="currency" value="MAD">
                    <input type="hidden" name="school_year" value="{{ $schoolYear }}">

                    <div>
                        <label class="block text-xs font-semibold uppercase tracking-wider text-slate-700 dark:text-slate-300 mb-1">
                            Professeur <span class="text-red-500">*</span>
                        </label>
                        <select name="teacher_id" x-model="selectedTeacher" required
                                class="w-full rounded-xl border-slate-300 bg-white text-sm text-slate-800 focus:border-blue-500 focus:ring-blue-500 dark:border-slate-700 dark:bg-slate-800 dark:text-slate-100">
                            <option value="">Sélectionner un professeur...</option>
                            @foreach($teachersList as $t)
                                <option value="{{ $t->id }}">{{ $t->full_name }} ({{ $t->teacher_number }} - {{ $t->center?->name }})</option>
                            @endforeach
                        </select>
                    </div>

                    <div class="grid grid-cols-2 gap-3">
                        <div>
                            <label class="block text-xs font-semibold uppercase tracking-wider text-slate-700 dark:text-slate-300 mb-1">
                                Mois <span class="text-red-500">*</span>
                            </label>
                            <select name="month" required
                                    class="w-full rounded-xl border-slate-300 bg-white text-sm text-slate-800 focus:border-blue-500 focus:ring-blue-500 dark:border-slate-700 dark:bg-slate-800 dark:text-slate-100">
                                @foreach($monthsNames as $mNum => $mLabel)
                                    <option value="{{ $mNum }}" {{ $month === $mNum ? 'selected' : '' }}>{{ $mLabel }}</option>
                                @endforeach
                            </select>
                        </div>

                        <div>
                            <label class="block text-xs font-semibold uppercase tracking-wider text-slate-700 dark:text-slate-300 mb-1">
                                Année <span class="text-red-500">*</span>
                            </label>
                            <select name="year" required
                                    class="w-full rounded-xl border-slate-300 bg-white text-sm text-slate-800 focus:border-blue-500 focus:ring-blue-500 dark:border-slate-700 dark:bg-slate-800 dark:text-slate-100">
                                @foreach([2026, 2027, 2028] as $y)
                                    <option value="{{ $y }}" {{ $year === $y ? 'selected' : '' }}>{{ $y }}</option>
                                @endforeach
                            </select>
                        </div>
                    </div>

                    <div class="grid grid-cols-2 gap-3">
                        <div>
                            <label class="block text-xs font-semibold uppercase tracking-wider text-slate-700 dark:text-slate-300 mb-1">
                                Montant payé (DH) <span class="text-red-500">*</span>
                            </label>
                            <input type="number" step="0.01" min="0" name="paid_amount" x-model="prefilledExpectedSalary" required placeholder="Ex: 6000"
                                   class="w-full rounded-xl border-slate-300 bg-white text-sm text-slate-800 focus:border-blue-500 focus:ring-blue-500 dark:border-slate-700 dark:bg-slate-800 dark:text-slate-100">
                        </div>

                        <div>
                            <label class="block text-xs font-semibold uppercase tracking-wider text-slate-700 dark:text-slate-300 mb-1">
                                Date de virement
                            </label>
                            <input type="date" name="payment_date" value="{{ now()->toDateString() }}"
                                   class="w-full rounded-xl border-slate-300 bg-white text-sm text-slate-800 focus:border-blue-500 focus:ring-blue-500 dark:border-slate-700 dark:bg-slate-800 dark:text-slate-100">
                        </div>
                    </div>

                    <div>
                        <label class="block text-xs font-semibold uppercase tracking-wider text-slate-700 dark:text-slate-300 mb-1">
                            Mode de règlement
                        </label>
                        <input type="text" name="payment_method" value="Virement bancaire"
                               class="w-full rounded-xl border-slate-300 bg-white text-sm text-slate-800 focus:border-blue-500 focus:ring-blue-500 dark:border-slate-700 dark:bg-slate-800 dark:text-slate-100">
                    </div>

                    <div class="pt-4 border-t border-slate-200 dark:border-slate-800 flex justify-end gap-2">
                        <button type="button" @click="salaryModalOpen = false" class="rounded-xl border border-slate-300 px-4 py-2 text-sm font-semibold text-slate-700 hover:bg-slate-50 dark:border-slate-700 dark:text-slate-300">
                            Annuler
                        </button>
                        <button type="submit" class="rounded-xl bg-blue-600 px-4 py-2 text-sm font-semibold text-white hover:bg-blue-500 shadow-sm">
                            Enregistrer le salaire
                        </button>
                    </div>
                </form>

            </div>
        </div>
    </div>

</div>
@endsection
