@extends('layouts.app')

@section('title', 'Journal des opérations — Gestion financière')

@section('content')
@php
    $paymentMethod = $paymentMethod ?? $method ?? 'all';
@endphp
<div class="space-y-6">

    {{-- ────────────────────────────────────────────────────────────
         EN-TÊTE DE LA PAGE (STYLE CAD SYNDIC COMPTABILITÉ)
    ──────────────────────────────────────────────────────────── --}}
    <div class="flex flex-col gap-4 lg:flex-row lg:items-center lg:justify-between border-b border-slate-200/90 pb-5 dark:border-slate-800">
        <div>
            <div class="inline-flex items-center gap-1.5 rounded-full bg-[#ECFDF5] px-2.5 py-0.5 text-xs font-semibold text-[#0DAD98] border border-[#0DAD98]/20 dark:bg-[#0DAD98]/15 dark:text-[#0DAD98] mb-1.5 shadow-2xs">
                <span>📑</span>
                <span>Gestion financière · Journal d'audit des encaissements</span>
            </div>
            <h1 class="text-2xl sm:text-3xl font-extrabold tracking-tight text-slate-900 dark:text-white">
                Journal des opérations
            </h1>
            <p class="mt-0.5 text-xs sm:text-sm text-slate-500 dark:text-slate-400">
                Traçabilité chronologique de tous les encaissements et pièces justificatives.
            </p>
        </div>

        <div class="flex items-center gap-2.5">
            <a href="{{ route('super-admin.finance.reports') }}"
               class="finance-btn-secondary">
                <x-icon name="arrow-down-tray" class="h-4 w-4 text-slate-500" />
                <span>Exports & Rapports</span>
            </a>
            <a href="{{ route('super-admin.finance.dashboard') }}"
               class="finance-btn-secondary">
                <x-icon name="arrow-left" class="h-4 w-4 text-slate-500" />
                <span>Dashboard</span>
            </a>
        </div>
    </div>

    {{-- ────────────────────────────────────────────────────────────
         4 CARTES KPI MAXIMALES (STYLE CAD SYNDIC COMPTABILITÉ)
    ──────────────────────────────────────────────────────────── --}}
    <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
        {{-- Total Transactions --}}
        <div class="finance-card p-4.5">
            <div class="flex items-start justify-between">
                <div>
                    <span class="block text-[11px] font-bold uppercase tracking-wider text-slate-400">Total écritures</span>
                    <div class="mt-2 text-2xl font-extrabold text-slate-900 dark:text-white whitespace-nowrap">
                        {{ $stats['total_count'] ?? 0 }}
                    </div>
                </div>
                <div class="flex h-9 w-9 items-center justify-center rounded-xl bg-slate-100 text-slate-600 dark:bg-slate-800 dark:text-slate-300">
                    <x-icon name="document-text" class="h-5 w-5" />
                </div>
            </div>
            <div class="mt-3 flex items-center justify-between text-[11px] text-slate-500 border-t border-slate-100 dark:border-slate-800/80 pt-2">
                <span>Volume audité</span>
                <span class="font-bold text-slate-800 dark:text-slate-200">100% tracé</span>
            </div>
        </div>

        {{-- Montant Total Cumulé --}}
        <div class="finance-card p-4.5">
            <div class="flex items-start justify-between">
                <div>
                    <span class="block text-[11px] font-bold uppercase tracking-wider text-slate-400">Total encaissé</span>
                    <div class="mt-2 text-2xl font-extrabold text-[#0DAD98] whitespace-nowrap">
                        {{ format_price($stats['total_amount'] ?? 0, 'MAD') }}
                    </div>
                </div>
                <div class="flex h-9 w-9 items-center justify-center rounded-xl bg-[#ECFDF5] text-[#0DAD98] dark:bg-[#0DAD98]/20">
                    <x-icon name="banknotes" class="h-5 w-5" />
                </div>
            </div>
            <div class="mt-3 flex items-center justify-between text-[11px] text-slate-500 border-t border-slate-100 dark:border-slate-800/80 pt-2">
                <span>Trésorerie perçue</span>
                <span class="font-bold text-[#0DAD98]">Validée</span>
            </div>
        </div>

        {{-- Espèces --}}
        <div class="finance-card p-4.5">
            <div class="flex items-start justify-between">
                <div>
                    <span class="block text-[11px] font-bold uppercase tracking-wider text-slate-400">Règlements espèces</span>
                    <div class="mt-2 text-2xl font-extrabold text-slate-900 dark:text-white whitespace-nowrap">
                        {{ format_price($stats['cash_total'] ?? 0, 'MAD') }}
                    </div>
                </div>
                <div class="flex h-9 w-9 items-center justify-center rounded-xl bg-amber-50 text-amber-600 dark:bg-amber-950/40">
                    💵
                </div>
            </div>
            <div class="mt-3 flex items-center justify-between text-[11px] text-slate-500 border-t border-slate-100 dark:border-slate-800/80 pt-2">
                <span>Caisse centres</span>
                <span class="font-semibold text-slate-700 dark:text-slate-300">Encaissé sur place</span>
            </div>
        </div>

        {{-- Virements & Cartes --}}
        <div class="finance-card p-4.5">
            <div class="flex items-start justify-between">
                <div>
                    <span class="block text-[11px] font-bold uppercase tracking-wider text-slate-400">Virements & Cartes</span>
                    <div class="mt-2 text-2xl font-extrabold text-slate-900 dark:text-white whitespace-nowrap">
                        {{ format_price(($stats['transfer_total'] ?? 0) + ($stats['card_total'] ?? 0), 'MAD') }}
                    </div>
                </div>
                <div class="flex h-9 w-9 items-center justify-center rounded-xl bg-[#ECFDF5] text-[#0DAD98] dark:bg-[#0DAD98]/20">
                    🏦
                </div>
            </div>
            <div class="mt-3 flex items-center justify-between text-[11px] text-slate-500 border-t border-slate-100 dark:border-slate-800/80 pt-2">
                <span>Compte bancaire</span>
                <span class="font-semibold text-slate-700 dark:text-slate-300">Flux bancarisés</span>
            </div>
        </div>
    </div>

    {{-- ────────────────────────────────────────────────────────────
         BARRE DE FILTRES COMPACTE (STYLE CAD SYNDIC COMPTABILITÉ)
    ──────────────────────────────────────────────────────────── --}}
    <form method="GET" action="{{ route('super-admin.finance.history') }}"
          class="rounded-xl border border-slate-200/90 bg-white p-3.5 shadow-[0_1px_3px_rgba(0,0,0,0.02)] dark:border-slate-800 dark:bg-slate-900">
        <div class="grid grid-cols-2 gap-3 sm:grid-cols-4 lg:grid-cols-5 items-center">
            {{-- Recherche --}}
            <div class="col-span-2 sm:col-span-1">
                <label for="filter-search" class="block text-[10px] font-bold uppercase tracking-wider text-slate-400 mb-1">
                    Recherche
                </label>
                <input type="text" name="search" id="filter-search" value="{{ $search ?? '' }}"
                       placeholder="Réf, étudiant, motif..."
                       class="w-full rounded-lg border-slate-200 bg-slate-50/50 py-1.5 px-2.5 text-xs text-slate-800 placeholder:text-slate-400 focus:border-[#0DAD98] focus:bg-white focus:ring-1 focus:ring-[#0DAD98] dark:border-slate-700 dark:bg-slate-800 dark:text-slate-100">
            </div>

            {{-- Année scolaire --}}
            <div>
                <label for="filter-sy" class="block text-[10px] font-bold uppercase tracking-wider text-slate-400 mb-1">
                    Année
                </label>
                <select name="school_year" id="filter-sy" onchange="this.form.submit()"
                        class="w-full rounded-lg border-slate-200 bg-slate-50/50 py-1.5 px-2.5 text-xs font-medium text-slate-800 focus:border-[#0DAD98] focus:bg-white focus:ring-1 focus:ring-[#0DAD98] dark:border-slate-700 dark:bg-slate-800 dark:text-slate-100">
                    <option value="all" {{ $schoolYear === 'all' ? 'selected' : '' }}>Toutes années</option>
                    @foreach($schoolYears as $sy)
                        <option value="{{ $sy }}" {{ $schoolYear === $sy ? 'selected' : '' }}>{{ $sy }}</option>
                    @endforeach
                </select>
            </div>

            {{-- Mode de règlement --}}
            <div>
                <label for="filter-method" class="block text-[10px] font-bold uppercase tracking-wider text-slate-400 mb-1">
                    Mode de règlement
                </label>
                <select name="payment_method" id="filter-method" onchange="this.form.submit()"
                        class="w-full rounded-lg border-slate-200 bg-slate-50/50 py-1.5 px-2.5 text-xs font-medium text-slate-800 focus:border-[#0DAD98] focus:bg-white focus:ring-1 focus:ring-[#0DAD98] dark:border-slate-700 dark:bg-slate-800 dark:text-slate-100">
                    <option value="all" {{ $paymentMethod === 'all' ? 'selected' : '' }}>Tous modes</option>
                    <option value="cash" {{ $paymentMethod === 'cash' ? 'selected' : '' }}>Espèces</option>
                    <option value="bank_transfer" {{ $paymentMethod === 'bank_transfer' ? 'selected' : '' }}>Virement bancaire</option>
                    <option value="credit_card" {{ $paymentMethod === 'credit_card' ? 'selected' : '' }}>Carte bancaire</option>
                    <option value="check" {{ $paymentMethod === 'check' ? 'selected' : '' }}>Chèque</option>
                </select>
            </div>

            {{-- Centre --}}
            <div>
                <label for="filter-cnt" class="block text-[10px] font-bold uppercase tracking-wider text-slate-400 mb-1">
                    Centre
                </label>
                <select name="center_id" id="filter-cnt" onchange="this.form.submit()"
                        class="w-full rounded-lg border-slate-200 bg-slate-50/50 py-1.5 px-2.5 text-xs font-medium text-slate-800 focus:border-[#0DAD98] focus:bg-white focus:ring-1 focus:ring-[#0DAD98] dark:border-slate-700 dark:bg-slate-800 dark:text-slate-100">
                    <option value="" {{ empty($centerId) ? 'selected' : '' }}>Tous centres</option>
                    @foreach($centers as $c)
                        <option value="{{ $c->id }}" {{ $centerId === $c->id ? 'selected' : '' }}>{{ $c->name }}</option>
                    @endforeach
                </select>
            </div>

            {{-- Domaine --}}
            <div>
                <label for="filter-dom" class="block text-[10px] font-bold uppercase tracking-wider text-slate-400 mb-1">
                    Domaine
                </label>
                <select name="domain" id="filter-dom" onchange="this.form.submit()"
                        class="w-full rounded-lg border-slate-200 bg-slate-50/50 py-1.5 px-2.5 text-xs font-medium text-slate-800 focus:border-[#0DAD98] focus:bg-white focus:ring-1 focus:ring-[#0DAD98] dark:border-slate-700 dark:bg-slate-800 dark:text-slate-100">
                    <option value="all" {{ $domain === 'all' ? 'selected' : '' }}>Tous domaines</option>
                    @foreach($domains as $slug => $d)
                        <option value="{{ $slug }}" {{ $domain === $slug ? 'selected' : '' }}>{{ is_array($d) ? ($d['name'] ?? $slug) : $d }}</option>
                    @endforeach
                </select>
            </div>
        </div>
    </form>

    {{-- ────────────────────────────────────────────────────────────
         GRAND TABLEAU BLANC ÉPURÉ (STYLE CAD SYNDIC COMPTABILITÉ)
    ──────────────────────────────────────────────────────────── --}}
    <div class="finance-card overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full text-left text-xs">
                <thead class="bg-slate-50/80 border-b border-slate-100 text-[10px] font-bold uppercase tracking-wider text-slate-400 dark:bg-slate-800/50 dark:border-slate-800">
                    <tr>
                        <th class="py-3 px-4">Référence</th>
                        <th class="py-3 px-4">Date</th>
                        <th class="py-3 px-4">Type</th>
                        <th class="py-3 px-4">Libellé</th>
                        <th class="py-3 px-4">Étudiant</th>
                        <th class="py-3 px-4">Centre</th>
                        <th class="py-3 px-4">Mode</th>
                        <th class="py-3 px-4 text-right">Montant</th>
                        <th class="py-3 px-4 text-center">Statut</th>
                        <th class="py-3 px-4 text-center">Reçu</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100 dark:divide-slate-800">
                    @forelse($payments as $payment)
                        <tr class="hover:bg-slate-50/50 dark:hover:bg-slate-800/30">
                            <td class="py-3 px-4 font-mono font-bold text-slate-900 dark:text-white whitespace-nowrap">
                                {{ $payment->reference ?? ('ENC-'.str_pad($payment->id, 5, '0', STR_PAD_LEFT)) }}
                            </td>
                            <td class="py-3 px-4 text-slate-600 dark:text-slate-300 whitespace-nowrap">
                                {{ $payment->payment_date ? $payment->payment_date->format('d/m/Y') : '—' }}
                            </td>
                            <td class="py-3 px-4 whitespace-nowrap">
                                <span class="text-[11px] font-semibold text-slate-700 dark:text-slate-300">
                                    Encaissement
                                </span>
                            </td>
                            <td class="py-3 px-4 text-slate-700 dark:text-slate-300 max-w-xs truncate" title="{{ $payment->notes ?? $payment->enrollment->formation->name ?? 'Frais de scolarité' }}">
                                {{ $payment->notes ?? ($payment->enrollment->formation->name ?? 'Frais de scolarité') }}
                            </td>
                            <td class="py-3 px-4 font-semibold text-slate-900 dark:text-white whitespace-nowrap">
                                {{ $payment->enrollment->student->full_name ?? '—' }}
                            </td>
                            <td class="py-3 px-4 text-slate-600 dark:text-slate-400 whitespace-nowrap">
                                {{ $payment->enrollment->formation->center->name ?? '—' }}
                            </td>
                            <td class="py-3 px-4 whitespace-nowrap">
                                <span class="text-[11px] text-slate-600 dark:text-slate-300 font-medium">
                                    @if($payment->payment_method === 'cash') Espèces
                                    @elseif($payment->payment_method === 'bank_transfer') Virement
                                    @elseif($payment->payment_method === 'credit_card') Carte bancaire
                                    @elseif($payment->payment_method === 'check') Chèque
                                    @else {{ ucfirst($payment->payment_method) }}
                                    @endif
                                </span>
                            </td>
                            <td class="py-3 px-4 text-right font-extrabold text-[#0DAD98] whitespace-nowrap">
                                {{ format_price($payment->amount, 'MAD') }}
                            </td>
                            <td class="py-3 px-4 text-center whitespace-nowrap">
                                <span class="finance-badge-paid">Validé</span>
                            </td>
                            <td class="py-3 px-4 text-center whitespace-nowrap">
                                @if($payment->receipt_number)
                                    <a href="{{ route('super-admin.finance.receipt.pdf', $payment->id) }}"
                                       target="_blank"
                                       class="inline-flex items-center gap-1 rounded-md bg-slate-100 hover:bg-slate-200 px-2 py-0.5 text-[10px] font-semibold text-slate-700 dark:bg-slate-800 dark:text-slate-300">
                                        <span>PDF</span>
                                    </a>
                                @else
                                    <span class="text-slate-400 text-[11px]">—</span>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="10" class="py-12 text-center text-xs text-slate-400">
                                Aucune écriture enregistrée pour cette recherche.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if($payments->hasPages())
            <div class="p-3 border-t border-slate-100 dark:border-slate-800 bg-slate-50/50 dark:bg-slate-800/30">
                {{ $payments->links() }}
            </div>
        @endif
    </div>

</div>
@endsection
