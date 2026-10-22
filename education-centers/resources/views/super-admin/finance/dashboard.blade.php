@extends('layouts.app')

@section('title', 'Tableau de bord financier — Gestion financière')

@section('content')
@php
    $monthsNames = $monthsNames ?? [
        1 => 'Janvier', 2 => 'Février', 3 => 'Mars', 4 => 'Avril',
        5 => 'Mai', 6 => 'Juin', 7 => 'Juillet', 8 => 'Août',
        9 => 'Septembre', 10 => 'Octobre', 11 => 'Novembre', 12 => 'Décembre',
    ];
@endphp
<div class="space-y-6" x-data="{
    paymentModalOpen: false,
    salaryModalOpen: false,
    selectedStudentId: '',
    selectedFormationId: '',
    selectedTeacherId: '',
    paymentAmount: '',
    prefilledExpectedSalary: '',
    prefilledPaidSalary: '',
    studentFinancialData: {{ Js::from($studentFinancialMap ?? []) }},

    onStudentChange() {
        const student = this.studentFinancialData[this.selectedStudentId];
        if (student) {
            this.selectedFormationId = student.formation_id || '';
            this.paymentAmount = student.remaining_amount > 0 ? student.remaining_amount : '';
        } else {
            this.paymentAmount = '';
        }
    },

    openQuickPayment(studentId) {
        if (studentId) {
            this.selectedStudentId = String(studentId);
            this.onStudentChange();
        } else {
            this.selectedStudentId = '';
            this.selectedFormationId = '';
            this.paymentAmount = '';
        }
        this.paymentModalOpen = true;
    },

    openTeacherSalary(teacherId, expectedSalary, paidSalary) {
        this.selectedTeacherId = teacherId ? String(teacherId) : '';
        this.prefilledExpectedSalary = expectedSalary || '';
        const remaining = Math.max(0, parseFloat(expectedSalary || 0) - parseFloat(paidSalary || 0));
        this.prefilledPaidSalary = remaining > 0 ? remaining : expectedSalary;
        this.salaryModalOpen = true;
    }
}">

    {{-- ────────────────────────────────────────────────────────────
         EN-TÊTE DE LA PAGE (STYLE CAD SYNDIC)
    ──────────────────────────────────────────────────────────── --}}
    <div class="flex flex-col gap-4 lg:flex-row lg:items-center lg:justify-between border-b border-slate-200/90 pb-5 dark:border-slate-800">
        <div>
            <div class="inline-flex items-center gap-1.5 rounded-full bg-[#ECFDF5] px-2.5 py-0.5 text-xs font-semibold text-[#0DAD98] border border-[#0DAD98]/20 dark:bg-[#0DAD98]/15 dark:text-[#0DAD98] mb-1.5 shadow-2xs">
                <span>📊</span>
                <span>Gestion financière · Vue globale</span>
            </div>
            <h1 class="text-2xl sm:text-3xl font-extrabold tracking-tight text-slate-900 dark:text-white">
                Gestion financière
            </h1>
            <p class="mt-0.5 text-xs sm:text-sm text-slate-500 dark:text-slate-400">
                Vue d'ensemble des finances des centres de formation.
            </p>
        </div>

        {{-- Actions rapides --}}
        <div class="flex flex-wrap items-center gap-2.5">
            <button type="button"
                    x-on:click="openQuickPayment('')"
                    class="finance-btn-primary">
                <x-icon name="plus" class="h-4 w-4" />
                <span>Encaisser scolarité</span>
            </button>
            <button type="button"
                    x-on:click="openTeacherSalary('', '', '')"
                    class="finance-btn-secondary">
                <x-icon name="banknotes" class="h-4 w-4 text-slate-500" />
                <span>Régler salaire</span>
            </button>
        </div>
    </div>

    {{-- ────────────────────────────────────────────────────────────
         BARRE DE FILTRES COMPACTE (STYLE CAD SYNDIC)
    ──────────────────────────────────────────────────────────── --}}
    <form method="GET" action="{{ route('super-admin.finance.dashboard') }}"
          class="rounded-xl border border-slate-200/90 bg-white p-3.5 shadow-[0_1px_3px_rgba(0,0,0,0.02)] dark:border-slate-800 dark:bg-slate-900">
        <div class="grid grid-cols-2 gap-3 sm:grid-cols-4 lg:grid-cols-5 items-center">
            {{-- Filtre Domaine --}}
            <div>
                <label for="filter-domain" class="block text-[10px] font-bold uppercase tracking-wider text-slate-400 dark:text-slate-500 mb-1">
                    Domaine
                </label>
                <select name="domain" id="filter-domain" onchange="this.form.submit()"
                        class="w-full rounded-lg border-slate-200 bg-slate-50/50 py-1.5 px-2.5 text-xs font-medium text-slate-800 focus:border-[#0DAD98] focus:bg-white focus:ring-1 focus:ring-[#0DAD98] dark:border-slate-700 dark:bg-slate-800 dark:text-slate-100">
                    <option value="all" {{ $domain === 'all' ? 'selected' : '' }}>Tous les domaines</option>
                    @foreach($domains as $slug => $d)
                        <option value="{{ $slug }}" {{ $domain === $slug ? 'selected' : '' }}>{{ is_array($d) ? ($d['name'] ?? $slug) : $d }}</option>
                    @endforeach
                </select>
            </div>

            {{-- Filtre Centre --}}
            <div>
                <label for="filter-center" class="block text-[10px] font-bold uppercase tracking-wider text-slate-400 dark:text-slate-500 mb-1">
                    Centre
                </label>
                <select name="center_id" id="filter-center" onchange="this.form.submit()"
                        class="w-full rounded-lg border-slate-200 bg-slate-50/50 py-1.5 px-2.5 text-xs font-medium text-slate-800 focus:border-[#0DAD98] focus:bg-white focus:ring-1 focus:ring-[#0DAD98] dark:border-slate-700 dark:bg-slate-800 dark:text-slate-100">
                    <option value="" {{ empty($centerId) ? 'selected' : '' }}>Tous les centres</option>
                    @foreach($centers as $c)
                        <option value="{{ $c->id }}" {{ $centerId === $c->id ? 'selected' : '' }}>{{ $c->name }}</option>
                    @endforeach
                </select>
            </div>

            {{-- Filtre Année scolaire --}}
            <div>
                <label for="filter-school-year" class="block text-[10px] font-bold uppercase tracking-wider text-slate-400 dark:text-slate-500 mb-1">
                    Année scolaire
                </label>
                <select name="school_year" id="filter-school-year" onchange="this.form.submit()"
                        class="w-full rounded-lg border-slate-200 bg-slate-50/50 py-1.5 px-2.5 text-xs font-medium text-slate-800 focus:border-[#0DAD98] focus:bg-white focus:ring-1 focus:ring-[#0DAD98] dark:border-slate-700 dark:bg-slate-800 dark:text-slate-100">
                    @foreach($schoolYears as $sy)
                        <option value="{{ $sy }}" {{ $schoolYear === $sy ? 'selected' : '' }}>{{ $sy }}</option>
                    @endforeach
                </select>
            </div>

            {{-- Filtre Mois --}}
            <div>
                <label for="filter-month" class="block text-[10px] font-bold uppercase tracking-wider text-slate-400 dark:text-slate-500 mb-1">
                    Mois salaires
                </label>
                <select name="month" id="filter-month" onchange="this.form.submit()"
                        class="w-full rounded-lg border-slate-200 bg-slate-50/50 py-1.5 px-2.5 text-xs font-medium text-slate-800 focus:border-[#0DAD98] focus:bg-white focus:ring-1 focus:ring-[#0DAD98] dark:border-slate-700 dark:bg-slate-800 dark:text-slate-100">
                    @foreach($monthsNames as $mNum => $mLabel)
                        <option value="{{ $mNum }}" {{ $month === $mNum ? 'selected' : '' }}>{{ $mLabel }}</option>
                    @endforeach
                </select>
            </div>

            {{-- Filtre Année --}}
            <div>
                <label for="filter-year" class="block text-[10px] font-bold uppercase tracking-wider text-slate-400 dark:text-slate-500 mb-1">
                    Année civile
                </label>
                <select name="year" id="filter-year" onchange="this.form.submit()"
                        class="w-full rounded-lg border-slate-200 bg-slate-50/50 py-1.5 px-2.5 text-xs font-medium text-slate-800 focus:border-[#0DAD98] focus:bg-white focus:ring-1 focus:ring-[#0DAD98] dark:border-slate-700 dark:bg-slate-800 dark:text-slate-100">
                    @foreach([2025, 2026, 2027, 2028] as $y)
                        <option value="{{ $y }}" {{ $year === $y ? 'selected' : '' }}>{{ $y }}</option>
                    @endforeach
                </select>
            </div>
        </div>
    </form>

    {{-- ────────────────────────────────────────────────────────────
         PREMIÈRE SECTION : LIGNE DE 4 CARTES KPI (STYLE CAD SYNDIC)
    ──────────────────────────────────────────────────────────── --}}
    <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-4">

        {{-- Carte 1 : Total Encaissé --}}
        <div class="finance-card p-4.5">
            <div class="flex items-start justify-between">
                <div>
                    <span class="block text-[11px] font-bold uppercase tracking-wider text-slate-400 dark:text-slate-500">
                        Total encaissé
                    </span>
                    <div class="mt-2 text-2xl lg:text-3xl font-extrabold text-slate-900 dark:text-white whitespace-nowrap">
                        {{ format_price($kpis['total_encaisse'] ?? 0, 'MAD') }}
                    </div>
                </div>
                <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl bg-[#ECFDF5] text-[#0DAD98] dark:bg-[#0DAD98]/20 dark:text-[#0DAD98]">
                    <x-icon name="arrow-trending-up" class="h-5 w-5" />
                </div>
            </div>
            <div class="mt-3 flex items-center justify-between text-[11px] text-slate-500 dark:text-slate-400 border-t border-slate-100 dark:border-slate-800/80 pt-2.5">
                <span>Taux de recouvrement</span>
                <span class="font-bold text-[#0DAD98]">{{ $kpis['taux_recouvrement'] ?? 0 }}%</span>
            </div>
        </div>

        {{-- Carte 2 : Total Restant à Encaisser --}}
        <div class="finance-card p-4.5">
            <div class="flex items-start justify-between">
                <div>
                    <span class="block text-[11px] font-bold uppercase tracking-wider text-slate-400 dark:text-slate-500">
                        Restant à encaisser
                    </span>
                    <div class="mt-2 text-2xl lg:text-3xl font-extrabold text-slate-900 dark:text-white whitespace-nowrap">
                        {{ format_price($kpis['total_restant_etudiants'] ?? 0, 'MAD') }}
                    </div>
                </div>
                <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl bg-amber-50 text-amber-600 dark:bg-amber-950/40 dark:text-amber-400">
                    <x-icon name="clock" class="h-5 w-5" />
                </div>
            </div>
            <div class="mt-3 flex items-center justify-between text-[11px] text-slate-500 dark:text-slate-400 border-t border-slate-100 dark:border-slate-800/80 pt-2.5">
                <span>Reste sur scolarités</span>
                <span class="font-semibold text-slate-700 dark:text-slate-300">{{ $schoolYear }}</span>
            </div>
        </div>

        {{-- Carte 3 : Salaires Payés --}}
        <div class="finance-card p-4.5">
            <div class="flex items-start justify-between">
                <div>
                    <span class="block text-[11px] font-bold uppercase tracking-wider text-slate-400 dark:text-slate-500">
                        Salaires payés
                    </span>
                    <div class="mt-2 text-2xl lg:text-3xl font-extrabold text-slate-900 dark:text-white whitespace-nowrap">
                        {{ format_price($kpis['salaires_payes'] ?? 0, 'MAD') }}
                    </div>
                </div>
                <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl bg-slate-100 text-slate-600 dark:bg-slate-800 dark:text-slate-300">
                    <x-icon name="user-group" class="h-5 w-5" />
                </div>
            </div>
            <div class="mt-3 flex items-center justify-between text-[11px] text-slate-500 dark:text-slate-400 border-t border-slate-100 dark:border-slate-800/80 pt-2.5">
                <span>Salaires restant à payer</span>
                <span class="font-bold text-amber-600 dark:text-amber-400 whitespace-nowrap">
                    {{ format_price($kpis['salaires_restant'] ?? 0, 'MAD') }}
                </span>
            </div>
        </div>

        {{-- Carte 4 : Solde Net de Trésorerie --}}
        <div class="finance-card p-4.5">
            <div class="flex items-start justify-between">
                <div>
                    <span class="block text-[11px] font-bold uppercase tracking-wider text-slate-400 dark:text-slate-500">
                        Solde net de trésorerie
                    </span>
                    <div class="mt-2 text-2xl lg:text-3xl font-extrabold {{ ($kpis['solde_net'] ?? 0) >= 0 ? 'text-[#0DAD98]' : 'text-rose-600' }} whitespace-nowrap">
                        {{ format_price($kpis['solde_net'] ?? 0, 'MAD') }}
                    </div>
                </div>
                <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl bg-[#ECFDF5] text-[#0DAD98] dark:bg-[#0DAD98]/20 dark:text-[#0DAD98]">
                    <x-icon name="banknotes" class="h-5 w-5" />
                </div>
            </div>
            <div class="mt-3 flex items-center justify-between text-[11px] text-slate-500 dark:text-slate-400 border-t border-slate-100 dark:border-slate-800/80 pt-2.5">
                <span>Encaissements - Salaires</span>
                <span class="font-semibold text-slate-700 dark:text-slate-300">Activité réelle</span>
            </div>
        </div>
    </div>

    {{-- ────────────────────────────────────────────────────────────
         DEUXIÈME SECTION : RÉCAPITULATIF (STYLE BLOC BUDGET 2026 CAD SYNDIC)
    ──────────────────────────────────────────────────────────── --}}
    <div class="finance-card p-5 sm:p-6">
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 pb-4 border-b border-slate-100 dark:border-slate-800">
            <div>
                <span class="text-[10px] font-bold uppercase tracking-wider text-slate-400">Synthèse budgétaire</span>
                <h2 class="text-base sm:text-lg font-bold text-slate-900 dark:text-white">
                    Budget & Recouvrement Annuel · {{ $schoolYear }}
                </h2>
            </div>
            <div class="flex items-center gap-4 text-xs font-semibold">
                <div>
                    <span class="text-slate-400">Total attendu :</span>
                    <span class="text-slate-900 dark:text-white font-bold ml-1 whitespace-nowrap">
                        {{ format_price($kpis['total_attendu'] ?? 0, 'MAD') }}
                    </span>
                </div>
                <div class="h-4 w-px bg-slate-200 dark:bg-slate-700"></div>
                <div>
                    <span class="text-slate-400">Encaissé :</span>
                    <span class="text-[#0DAD98] font-bold ml-1 whitespace-nowrap">
                        {{ format_price($kpis['total_encaisse'] ?? 0, 'MAD') }}
                    </span>
                </div>
            </div>
        </div>

        {{-- Barre de progression fine style Cad Syndic --}}
        <div class="mt-5 space-y-2">
            <div class="flex items-center justify-between text-xs">
                <span class="font-medium text-slate-600 dark:text-slate-300">Progression globale des encaissements</span>
                <span class="font-bold text-[#0DAD98]">{{ $kpis['taux_recouvrement'] ?? 0 }}% recouvré</span>
            </div>
            <div class="h-2.5 w-full overflow-hidden rounded-full bg-slate-100 dark:bg-slate-800">
                <div class="h-full rounded-full bg-[#0DAD98] transition-all duration-500 ease-out"
                     style="width: {{ min(100, max(0, $kpis['taux_recouvrement'] ?? 0)) }}%"></div>
            </div>
            <div class="flex items-center justify-between text-[11px] text-slate-400 pt-1">
                <span>0 DH</span>
                <span>Restant à percevoir : {{ format_price($kpis['total_restant_etudiants'] ?? 0, 'MAD') }}</span>
                <span>{{ format_price($kpis['total_attendu'] ?? 0, 'MAD') }}</span>
            </div>
        </div>
    </div>

    {{-- ────────────────────────────────────────────────────────────
         TROISIÈME SECTION : DEUX GRAPHIQUES ÉPURÉS CÔTE À CÔTE
    ──────────────────────────────────────────────────────────── --}}
    <div class="grid gap-6 lg:grid-cols-2">

        {{-- Graphique 1 : Évolution Encaissements vs Salaires --}}
        <div class="finance-card p-5">
            <div class="flex items-center justify-between mb-4">
                <div>
                    <h3 class="text-sm font-bold text-slate-900 dark:text-white">
                        Évolution des flux mensuels
                    </h3>
                    <p class="text-[11px] text-slate-500">Comparatif encaissements et salaires décaissés</p>
                </div>
                <div class="flex items-center gap-3 text-[11px] font-semibold">
                    <span class="inline-flex items-center gap-1.5 text-slate-600 dark:text-slate-300">
                        <span class="h-2 w-2 rounded-full bg-[#0DAD98]"></span>
                        Encaissements
                    </span>
                    <span class="inline-flex items-center gap-1.5 text-slate-600 dark:text-slate-300">
                        <span class="h-2 w-2 rounded-full bg-slate-400"></span>
                        Salaires
                    </span>
                </div>
            </div>

            @php
                $monthlyEvo = $kpis['monthly_evolution'] ?? [];
                $evolution  = $monthlyEvo['items'] ?? [];
                $maxVal     = max(1, $monthlyEvo['max_amount'] ?? 1);
            @endphp

            @if(!empty($evolution))
                <div class="h-44 w-full flex items-end justify-between gap-3 pt-6 pb-2 border-b border-slate-100 dark:border-slate-800">
                    @foreach($evolution as $ev)
                        @php
                            $hEnc = $maxVal > 0 ? round((($ev['collected'] ?? 0) / $maxVal) * 100) : 0;
                            $hSal = $maxVal > 0 ? round((($ev['salaries'] ?? 0) / $maxVal) * 100) : 0;
                        @endphp
                        <div class="flex-1 flex flex-col items-center h-full justify-end group relative">
                            {{-- Tooltip au hover --}}
                            <div class="opacity-0 group-hover:opacity-100 transition-opacity absolute -top-10 bg-slate-800 text-white text-[10px] py-1 px-2 rounded-md pointer-events-none whitespace-nowrap shadow-md z-10">
                                Encaissé: {{ number_format($ev['collected'] ?? 0, 0, ',', ' ') }} DH<br>
                                Salaires: {{ number_format($ev['salaries'] ?? 0, 0, ',', ' ') }} DH
                            </div>

                            <div class="w-full flex items-end justify-center gap-1 h-full">
                                {{-- Barre Encaissement --}}
                                <div class="w-2.5 sm:w-3.5 bg-[#0DAD98] rounded-t-sm transition-all duration-300 group-hover:brightness-110"
                                     style="height: {{ max(4, $hEnc) }}%"></div>
                                {{-- Barre Salaires --}}
                                <div class="w-2.5 sm:w-3.5 bg-slate-300 dark:bg-slate-700 rounded-t-sm transition-all duration-300 group-hover:brightness-110"
                                     style="height: {{ max(4, $hSal) }}%"></div>
                            </div>
                            <span class="mt-2 text-[10px] font-semibold text-slate-400 truncate w-full text-center">
                                {{ $ev['label'] ?? '' }}
                            </span>
                        </div>
                    @endforeach
                </div>
            @else
                <div class="h-44 flex items-center justify-center text-xs text-slate-400">
                    Aucune donnée historique sur la période.
                </div>
            @endif
        </div>

        {{-- Graphique 2 : Répartition des statuts étudiants --}}
        <div class="finance-card p-5">
            <div class="flex items-center justify-between mb-4">
                <div>
                    <h3 class="text-sm font-bold text-slate-900 dark:text-white">
                        Répartition des paiements étudiants
                    </h3>
                    <p class="text-[11px] text-slate-500">Statuts d'acquittement sur la scolarité</p>
                </div>
                <a href="{{ route('super-admin.finance.students') }}" class="text-[11px] font-semibold text-[#0DAD98] hover:underline">
                    Détails &rarr;
                </a>
            </div>

            @php
                $cntPaid = $kpis['etudiants_payes'] ?? 0;
                $cntPart = $kpis['etudiants_partiels'] ?? 0;
                $cntUnp  = $kpis['etudiants_impayes'] ?? 0;
                $totalSt = max(1, $cntPaid + $cntPart + $cntUnp);
                $pctPaid = round(($cntPaid / $totalSt) * 100);
                $pctPart = round(($cntPart / $totalSt) * 100);
                $pctUnp  = max(0, 100 - $pctPaid - $pctPart);
            @endphp

            <div class="space-y-4 pt-3">
                {{-- Barre segmentée épurée --}}
                <div class="h-3 w-full flex rounded-full overflow-hidden bg-slate-100 dark:bg-slate-800">
                    <div style="width: {{ $pctPaid }}%" class="bg-[#0DAD98]" title="Payé : {{ $cntPaid }}"></div>
                    <div style="width: {{ $pctPart }}%" class="bg-amber-400" title="Partiel : {{ $cntPart }}"></div>
                    <div style="width: {{ $pctUnp }}%" class="bg-rose-400" title="Non payé : {{ $cntUnp }}"></div>
                </div>

                {{-- Légendes épurées avec compteurs --}}
                <div class="grid grid-cols-3 gap-3 pt-2">
                    <div class="rounded-lg bg-slate-50/80 p-2.5 dark:bg-slate-800/40">
                        <div class="flex items-center gap-1.5 text-[11px] font-semibold text-slate-600 dark:text-slate-300">
                            <span class="h-2 w-2 rounded-full bg-[#0DAD98]"></span>
                            Payé intégral
                        </div>
                        <div class="mt-1 text-base font-bold text-slate-900 dark:text-white">
                            {{ $cntPaid }} <span class="text-[11px] font-normal text-slate-400">({{ $pctPaid }}%)</span>
                        </div>
                    </div>

                    <div class="rounded-lg bg-slate-50/80 p-2.5 dark:bg-slate-800/40">
                        <div class="flex items-center gap-1.5 text-[11px] font-semibold text-slate-600 dark:text-slate-300">
                            <span class="h-2 w-2 rounded-full bg-amber-500"></span>
                            Partiel
                        </div>
                        <div class="mt-1 text-base font-bold text-slate-900 dark:text-white">
                            {{ $cntPart }} <span class="text-[11px] font-normal text-slate-400">({{ $pctPart }}%)</span>
                        </div>
                    </div>

                    <div class="rounded-lg bg-slate-50/80 p-2.5 dark:bg-slate-800/40">
                        <div class="flex items-center gap-1.5 text-[11px] font-semibold text-slate-600 dark:text-slate-300">
                            <span class="h-2 w-2 rounded-full bg-rose-500"></span>
                            Non payé
                        </div>
                        <div class="mt-1 text-base font-bold text-slate-900 dark:text-white">
                            {{ $cntUnp }} <span class="text-[11px] font-normal text-slate-400">({{ $pctUnp }}%)</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- ────────────────────────────────────────────────────────────
         QUATRIÈME SECTION : SYNTHÈSE PAR DOMAINE (CARTES COMPACTES)
    ──────────────────────────────────────────────────────────── --}}
    <div>
        <div class="flex items-center justify-between mb-3.5">
            <div>
                <h3 class="text-sm font-bold text-slate-900 dark:text-white">
                    Performance par domaine de formation
                </h3>
                <p class="text-[11px] text-slate-500">Avancement des encaissements par filière</p>
            </div>
            <a href="{{ route('super-admin.finance.students') }}"
               class="text-xs font-semibold text-[#0DAD98] hover:underline flex items-center gap-1">
                <span>Voir la liste des étudiants</span>
                <x-icon name="arrow-right" class="h-3 w-3" />
            </a>
        </div>

        @php
            $domainBreakdown = $kpis['domain_breakdown'] ?? [];
        @endphp

        <div class="grid gap-3.5 sm:grid-cols-2 lg:grid-cols-3">
            @forelse($domainBreakdown as $slug => $db)
                <div class="finance-card p-4 hover:border-slate-300 transition-colors">
                    <div class="flex items-center justify-between mb-3">
                        <div class="flex items-center gap-2 min-w-0">
                            <span class="text-base">📁</span>
                            <h4 class="text-xs font-bold text-slate-900 dark:text-white truncate">
                                {{ $db->label ?? $slug }}
                            </h4>
                        </div>
                        <span class="text-xs font-bold text-[#0DAD98]">
                            {{ $db->rate ?? 0 }}%
                        </span>
                    </div>

                    {{-- Jauge fine --}}
                    <div class="h-1.5 w-full rounded-full bg-slate-100 dark:bg-slate-800 overflow-hidden mb-3">
                        <div class="h-full rounded-full bg-[#0DAD98]" style="width: {{ min(100, $db->rate ?? 0) }}%"></div>
                    </div>

                    <div class="flex items-center justify-between text-[11px] text-slate-500 dark:text-slate-400 border-t border-slate-100 dark:border-slate-800/80 pt-2.5">
                        <span>Encaissé: <strong class="text-slate-800 dark:text-slate-200">{{ format_price($db->collected ?? 0, 'MAD') }}</strong></span>
                        <a href="{{ route('super-admin.finance.students', ['domain' => $slug]) }}"
                           class="text-[#0DAD98] hover:underline font-semibold">
                            Filtrer
                        </a>
                    </div>
                </div>
            @empty
                <div class="col-span-3 text-center py-6 text-xs text-slate-400">
                    Aucun domaine configuré.
                </div>
            @endforelse
        </div>
    </div>

    {{-- ────────────────────────────────────────────────────────────
         MODAL 1 : ENREGISTRER UN VERSEMENT ÉTUDIANT (ALPINE.JS)
    ──────────────────────────────────────────────────────────── --}}
    <div x-show="paymentModalOpen"
         x-cloak
         x-transition:enter="transition ease-out duration-200"
         x-transition:enter-start="opacity-0"
         x-transition:enter-end="opacity-100"
         x-transition:leave="transition ease-in duration-150"
         x-transition:leave-start="opacity-100"
         x-transition:leave-end="opacity-0"
         class="fixed inset-0 z-50 flex items-center justify-center bg-slate-900/40 p-4 backdrop-blur-xs">

        <div x-on:click.outside="paymentModalOpen = false"
             class="w-full max-w-lg rounded-2xl bg-white p-6 shadow-xl dark:bg-slate-900 dark:border dark:border-slate-800">

            <div class="flex items-center justify-between pb-4 border-b border-slate-100 dark:border-slate-800">
                <div class="flex items-center gap-2.5">
                    <span class="flex h-8 w-8 items-center justify-center rounded-lg bg-[#ECFDF5] text-[#0DAD98]">
                        💰
                    </span>
                    <div>
                        <h3 class="text-sm font-bold text-slate-900 dark:text-white">Enregistrer un versement</h3>
                        <p class="text-[11px] text-slate-500">Scolarité annuelle · Paiement partiel ou solde</p>
                    </div>
                </div>
                <button type="button" x-on:click="paymentModalOpen = false" class="text-slate-400 hover:text-slate-600">
                    <x-icon name="x-mark" class="h-5 w-5" />
                </button>
            </div>

            <form method="POST" action="{{ route('super-admin.finance.students.payment.store') }}" class="mt-4 space-y-4">
                @csrf

                <div>
                    <label class="block text-xs font-semibold text-slate-700 dark:text-slate-300 mb-1">
                        Étudiant <span class="text-rose-500">*</span>
                    </label>
                    <select name="student_id" x-model="selectedStudentId" x-on:change="onStudentChange()" required
                            class="w-full rounded-lg border-slate-200 bg-white text-xs font-medium text-slate-800 focus:border-[#0DAD98] focus:ring-1 focus:ring-[#0DAD98]">
                        <option value="">-- Sélectionner un étudiant --</option>
                        @foreach($studentsList as $st)
                            <option value="{{ $st->id }}">
                                {{ $st->full_name }} ({{ $st->student_number ?? 'ID: '.$st->id }}) — {{ $st->center->name ?? '' }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <div class="grid grid-cols-2 gap-3">
                    <div>
                        <label class="block text-xs font-semibold text-slate-700 dark:text-slate-300 mb-1">
                            Formation <span class="text-rose-500">*</span>
                        </label>
                        <select name="formation_id" x-model="selectedFormationId" required
                                class="w-full rounded-lg border-slate-200 bg-white text-xs font-medium text-slate-800 focus:border-[#0DAD98] focus:ring-1 focus:ring-[#0DAD98]">
                            <option value="">-- Formation --</option>
                            @foreach($formationsList as $fm)
                                <option value="{{ $fm->id }}">{{ $fm->name }} ({{ $fm->center->name ?? '' }})</option>
                            @endforeach
                        </select>
                    </div>

                    <div>
                        <label class="block text-xs font-semibold text-slate-700 dark:text-slate-300 mb-1">
                            Montant versé (DH) <span class="text-rose-500">*</span>
                        </label>
                        <input type="number" name="amount" step="0.01" min="1" x-model="paymentAmount" required placeholder="Ex: 5000"
                               class="w-full rounded-lg border-slate-200 bg-white text-xs font-semibold text-slate-900 focus:border-[#0DAD98] focus:ring-1 focus:ring-[#0DAD98]">
                    </div>
                </div>

                <div class="grid grid-cols-2 gap-3">
                    <div>
                        <label class="block text-xs font-semibold text-slate-700 dark:text-slate-300 mb-1">
                            Date du versement <span class="text-rose-500">*</span>
                        </label>
                        <input type="date" name="payment_date" value="{{ date('Y-m-d') }}" required
                               class="w-full rounded-lg border-slate-200 bg-white text-xs text-slate-800 focus:border-[#0DAD98] focus:ring-1 focus:ring-[#0DAD98]">
                    </div>

                    <div>
                        <label class="block text-xs font-semibold text-slate-700 dark:text-slate-300 mb-1">
                            Moyen de règlement <span class="text-rose-500">*</span>
                        </label>
                        <select name="payment_method" required
                                class="w-full rounded-lg border-slate-200 bg-white text-xs font-medium text-slate-800 focus:border-[#0DAD98] focus:ring-1 focus:ring-[#0DAD98]">
                            <option value="cash">Espèces</option>
                            <option value="bank_transfer">Virement bancaire</option>
                            <option value="credit_card">Carte bancaire</option>
                            <option value="check">Chèque</option>
                        </select>
                    </div>
                </div>

                <div>
                    <label class="block text-xs font-semibold text-slate-700 dark:text-slate-300 mb-1">
                        Référence / Notes (facultatif)
                    </label>
                    <input type="text" name="reference" placeholder="Ex: VIR-2026-OCT-09"
                           class="w-full rounded-lg border-slate-200 bg-white text-xs text-slate-800 focus:border-[#0DAD98] focus:ring-1 focus:ring-[#0DAD98]">
                </div>

                <div class="flex items-center justify-end gap-2.5 pt-4 border-t border-slate-100 dark:border-slate-800">
                    <button type="button" x-on:click="paymentModalOpen = false"
                            class="px-3.5 py-2 text-xs font-semibold text-slate-600 hover:bg-slate-100 rounded-lg">
                        Annuler
                    </button>
                    <button type="submit" class="finance-btn-primary">
                        Valider l'encaissement
                    </button>
                </div>
            </form>
        </div>
    </div>

    {{-- ────────────────────────────────────────────────────────────
         MODAL 2 : RÈGLEMENT DU SALAIRE PROFESSEUR (ALPINE.JS)
    ──────────────────────────────────────────────────────────── --}}
    <div x-show="salaryModalOpen"
         x-cloak
         x-transition:enter="transition ease-out duration-200"
         x-transition:enter-start="opacity-0"
         x-transition:enter-end="opacity-100"
         x-transition:leave="transition ease-in duration-150"
         x-transition:leave-start="opacity-100"
         x-transition:leave-end="opacity-0"
         class="fixed inset-0 z-50 flex items-center justify-center bg-slate-900/40 p-4 backdrop-blur-xs">

        <div x-on:click.outside="salaryModalOpen = false"
             class="w-full max-w-lg rounded-2xl bg-white p-6 shadow-xl dark:bg-slate-900 dark:border dark:border-slate-800">

            <div class="flex items-center justify-between pb-4 border-b border-slate-100 dark:border-slate-800">
                <div class="flex items-center gap-2.5">
                    <span class="flex h-8 w-8 items-center justify-center rounded-lg bg-[#ECFDF5] text-[#0DAD98]">
                        🧑‍🏫
                    </span>
                    <div>
                        <h3 class="text-sm font-bold text-slate-900 dark:text-white">Règlement salaire professeur</h3>
                        <p class="text-[11px] text-slate-500">Mois de {{ $monthsNames[$month] ?? $month }} {{ $year }}</p>
                    </div>
                </div>
                <button type="button" x-on:click="salaryModalOpen = false" class="text-slate-400 hover:text-slate-600">
                    <x-icon name="x-mark" class="h-5 w-5" />
                </button>
            </div>

            <form method="POST" action="{{ route('super-admin.finance.teachers.salary.store') }}" class="mt-4 space-y-4">
                @csrf
                <input type="hidden" name="month" value="{{ $month }}">
                <input type="hidden" name="year" value="{{ $year }}">
                <input type="hidden" name="school_year" value="{{ $schoolYear }}">

                <div>
                    <label class="block text-xs font-semibold text-slate-700 dark:text-slate-300 mb-1">
                        Professeur <span class="text-rose-500">*</span>
                    </label>
                    <select name="teacher_id" x-model="selectedTeacherId" required
                            class="w-full rounded-lg border-slate-200 bg-white text-xs font-medium text-slate-800 focus:border-[#0DAD98] focus:ring-1 focus:ring-[#0DAD98]">
                        <option value="">-- Sélectionner un enseignant --</option>
                        @foreach($teachersList as $tch)
                            <option value="{{ $tch->id }}">
                                {{ $tch->full_name }} ({{ $tch->center->name ?? '' }})
                            </option>
                        @endforeach
                    </select>
                </div>

                <div class="grid grid-cols-2 gap-3">
                    <div>
                        <label class="block text-xs font-semibold text-slate-700 dark:text-slate-300 mb-1">
                            Salaire mensuel prévu (DH)
                        </label>
                        <input type="number" step="0.01" min="0" name="monthly_salary" x-model="prefilledExpectedSalary" placeholder="Ex: 6000"
                               class="w-full rounded-lg border-slate-200 bg-white text-xs font-medium text-slate-900 focus:border-[#0DAD98] focus:ring-1 focus:ring-[#0DAD98]">
                    </div>

                    <div>
                        <label class="block text-xs font-semibold text-slate-700 dark:text-slate-300 mb-1">
                            Montant versé (DH) <span class="text-rose-500">*</span>
                        </label>
                        <input type="number" step="0.01" min="0" name="paid_amount" x-model="prefilledPaidSalary" required placeholder="Ex: 6000"
                               class="w-full rounded-lg border-slate-200 bg-white text-xs font-semibold text-slate-900 focus:border-[#0DAD98] focus:ring-1 focus:ring-[#0DAD98]">
                    </div>
                </div>

                <div class="grid grid-cols-2 gap-3">
                    <div>
                        <label class="block text-xs font-semibold text-slate-700 dark:text-slate-300 mb-1">
                            Date de versement <span class="text-rose-500">*</span>
                        </label>
                        <input type="date" name="payment_date" value="{{ date('Y-m-d') }}" required
                               class="w-full rounded-lg border-slate-200 bg-white text-xs text-slate-800 focus:border-[#0DAD98] focus:ring-1 focus:ring-[#0DAD98]">
                    </div>

                    <div>
                        <label class="block text-xs font-semibold text-slate-700 dark:text-slate-300 mb-1">
                            Mode de paiement
                        </label>
                        <select name="payment_method"
                                class="w-full rounded-lg border-slate-200 bg-white text-xs font-medium text-slate-800 focus:border-[#0DAD98] focus:ring-1 focus:ring-[#0DAD98]">
                            <option value="bank_transfer">Virement bancaire</option>
                            <option value="check">Chèque</option>
                            <option value="cash">Espèces</option>
                        </select>
                    </div>
                </div>

                <div>
                    <label class="block text-xs font-semibold text-slate-700 dark:text-slate-300 mb-1">
                        Note / Réf de virement
                    </label>
                    <input type="text" name="notes" placeholder="Ex: Salaire Octobre 2026 - Virement émis"
                           class="w-full rounded-lg border-slate-200 bg-white text-xs text-slate-800 focus:border-[#0DAD98] focus:ring-1 focus:ring-[#0DAD98]">
                </div>

                <div class="flex items-center justify-end gap-2.5 pt-4 border-t border-slate-100 dark:border-slate-800">
                    <button type="button" x-on:click="salaryModalOpen = false"
                            class="px-3.5 py-2 text-xs font-semibold text-slate-600 hover:bg-slate-100 rounded-lg">
                        Annuler
                    </button>
                    <button type="submit" class="finance-btn-primary">
                        Confirmer le règlement
                    </button>
                </div>
            </form>
        </div>
    </div>

</div>
@endsection
