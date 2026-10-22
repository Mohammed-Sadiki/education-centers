@extends('layouts.app')

@section('title', 'Dépenses & Achats — Gestion financière')

@section('content')
<div class="space-y-6" x-data="{
    expenseModalOpen: false,
    detailModalOpen: false,
    activeExpense: null,
    items: [
        { name: '', quantity: 1, unit_price: '' }
    ],

    addItem() {
        this.items.push({ name: '', quantity: 1, unit_price: '' });
    },

    removeItem(index) {
        if (this.items.length > 1) {
            this.items.splice(index, 1);
        } else {
            this.items[0] = { name: '', quantity: 1, unit_price: '' };
        }
    },

    getTotal() {
        return this.items.reduce((sum, item) => {
            const q = parseFloat(item.quantity) || 0;
            const p = parseFloat(item.unit_price) || 0;
            return sum + (q * p);
        }, 0);
    },

    openDetail(exp) {
        this.activeExpense = exp;
        this.detailModalOpen = true;
    }
}">

    {{-- En-tête de la page --}}
    <div class="flex flex-col gap-4 lg:flex-row lg:items-center lg:justify-between border-b border-slate-200/90 pb-5 dark:border-slate-800">
        <div>
            <div class="inline-flex items-center gap-1.5 rounded-full bg-rose-50 px-2.5 py-0.5 text-xs font-semibold text-rose-600 border border-rose-200 dark:bg-rose-950/30 dark:text-rose-400 mb-1.5 shadow-2xs">
                <span>🧾</span>
                <span>Gestion financière · Achats, Prestations & Charges d'exploitation</span>
            </div>
            <h1 class="text-2xl sm:text-3xl font-extrabold tracking-tight text-slate-900 dark:text-white">
                Dépenses & Charges
            </h1>
            <p class="mt-0.5 text-xs sm:text-sm text-slate-500 dark:text-slate-400">
                Enregistrement réel des achats, déduction automatique sur le budget alloué et gestion des justificatifs.
            </p>
        </div>

        <div class="flex items-center gap-2.5">
            <button type="button" x-on:click="expenseModalOpen = true" class="finance-btn-primary">
                <x-icon name="plus" class="h-4 w-4" />
                <span>+ Ajouter une dépense</span>
            </button>
            <a href="{{ route('super-admin.finance.dashboard') }}" class="finance-btn-secondary">
                <x-icon name="arrow-left" class="h-4 w-4 text-slate-500" />
                <span>Dashboard</span>
            </a>
        </div>
    </div>

    {{-- Messages de feedback --}}
    @if(session('success'))
        <div class="flex items-center gap-2.5 rounded-xl border border-emerald-200 bg-emerald-50 p-3.5 text-xs font-semibold text-emerald-800 dark:border-emerald-800/60 dark:bg-emerald-950/40 dark:text-emerald-200 shadow-2xs">
            <x-icon name="check" class="h-4 w-4 text-[#0DAD98]" />
            <span>{{ session('success') }}</span>
        </div>
    @endif
    @if(session('error'))
        <div class="flex items-center gap-2.5 rounded-xl border border-rose-200 bg-rose-50 p-3.5 text-xs font-semibold text-rose-800 dark:border-rose-800/60 dark:bg-rose-950/40 dark:text-rose-200 shadow-2xs">
            <x-icon name="x-mark" class="h-4 w-4 text-rose-600" />
            <span>{{ session('error') }}</span>
        </div>
    @endif

    {{-- Quatre KPI Cad Syndic avec déduction automatique --}}
    <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
        {{-- Budget annuel alloué --}}
        <div class="finance-card p-4.5">
            <span class="block text-[11px] font-bold uppercase tracking-wider text-slate-400">Budget alloué</span>
            <div class="mt-2 text-2xl font-extrabold text-slate-900 dark:text-white whitespace-nowrap">
                {{ format_price($annualBudget, 'MAD') }}
            </div>
            <div class="mt-3 text-[11px] text-slate-400 border-t border-slate-100 dark:border-slate-800/80 pt-2">
                Plafond prévisionnel annuel
            </div>
        </div>

        {{-- Dépenses engagées --}}
        <div class="finance-card p-4.5">
            <span class="block text-[11px] font-bold uppercase tracking-wider text-slate-400">Dépenses engagées</span>
            <div class="mt-2 text-2xl font-extrabold text-rose-600 dark:text-rose-400 whitespace-nowrap">
                {{ format_price($totalExpenses, 'MAD') }}
            </div>
            <div class="mt-3 text-[11px] text-slate-400 border-t border-slate-100 dark:border-slate-800/80 pt-2">
                {{ $expenses->where('status', '!=', 'cancelled')->count() }} achat(s) validé(s)
            </div>
        </div>

        {{-- Budget restant (calculé automatiquement) --}}
        <div class="finance-card p-4.5">
            <span class="block text-[11px] font-bold uppercase tracking-wider text-slate-400">Budget restant</span>
            <div class="mt-2 text-2xl font-extrabold text-[#0DAD98] whitespace-nowrap">
                {{ format_price($remainingBudget, 'MAD') }}
            </div>
            <div class="mt-3 text-[11px] text-slate-400 border-t border-slate-100 dark:border-slate-800/80 pt-2">
                Solde disponible déduit en temps réel
            </div>
        </div>

        {{-- Taux de consommation --}}
        <div class="finance-card p-4.5">
            <span class="block text-[11px] font-bold uppercase tracking-wider text-slate-400">Taux de consommation</span>
            <div class="mt-2 text-2xl font-extrabold text-slate-900 dark:text-white whitespace-nowrap">
                {{ $consumptionRate }}%
            </div>
            <div class="mt-3 text-[11px] text-slate-400 border-t border-slate-100 dark:border-slate-800/80 pt-2">
                Consommé sur budget initial
            </div>
        </div>
    </div>

    {{-- Filtres compacts --}}
    <form method="GET" action="{{ route('super-admin.finance.expenses') }}"
          class="rounded-xl border border-slate-200/90 bg-white p-3.5 shadow-[0_1px_3px_rgba(0,0,0,0.02)] dark:border-slate-800 dark:bg-slate-900">
        <div class="grid grid-cols-2 gap-3 sm:grid-cols-4 items-center">
            <div>
                <label class="block text-[10px] font-bold uppercase tracking-wider text-slate-400 mb-1">Recherche</label>
                <input type="text" name="search" value="{{ $search ?? '' }}" placeholder="Libellé, fournisseur, réf..."
                       class="w-full rounded-lg border-slate-200 bg-slate-50/50 py-1.5 px-2.5 text-xs text-slate-800 placeholder:text-slate-400 focus:border-[#0DAD98] focus:bg-white focus:ring-1 focus:ring-[#0DAD98]">
            </div>
            <div>
                <label class="block text-[10px] font-bold uppercase tracking-wider text-slate-400 mb-1">Centre</label>
                <select name="center_id" onchange="this.form.submit()"
                        class="w-full rounded-lg border-slate-200 bg-slate-50/50 py-1.5 px-2.5 text-xs font-medium text-slate-800 focus:border-[#0DAD98] focus:bg-white focus:ring-1 focus:ring-[#0DAD98]">
                    <option value="" {{ empty($centerId) ? 'selected' : '' }}>Tous centres</option>
                    @foreach($centers as $c)
                        <option value="{{ $c->id }}" {{ $centerId === $c->id ? 'selected' : '' }}>{{ $c->name }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label class="block text-[10px] font-bold uppercase tracking-wider text-slate-400 mb-1">Catégorie</label>
                <select name="category_id" onchange="this.form.submit()"
                        class="w-full rounded-lg border-slate-200 bg-slate-50/50 py-1.5 px-2.5 text-xs font-medium text-slate-800 focus:border-[#0DAD98] focus:bg-white focus:ring-1 focus:ring-[#0DAD98]">
                    <option value="" {{ empty($categoryId) ? 'selected' : '' }}>Toutes catégories</option>
                    @foreach($categories as $cat)
                        <option value="{{ $cat->id }}" {{ $categoryId === $cat->id ? 'selected' : '' }}>{{ $cat->name }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label class="block text-[10px] font-bold uppercase tracking-wider text-slate-400 mb-1">Année</label>
                <select name="school_year" onchange="this.form.submit()"
                        class="w-full rounded-lg border-slate-200 bg-slate-50/50 py-1.5 px-2.5 text-xs font-medium text-slate-800 focus:border-[#0DAD98] focus:bg-white focus:ring-1 focus:ring-[#0DAD98]">
                    @foreach($schoolYears as $sy)
                        <option value="{{ $sy }}" {{ $schoolYear === $sy ? 'selected' : '' }}>{{ $sy }}</option>
                    @endforeach
                </select>
            </div>
        </div>
    </form>

    {{-- Accordéons Mensuels Cad Syndic --}}
    <div class="space-y-3.5">
        @forelse($groupedExpenses as $gKey => $group)
            <div x-data="{ open: true }" class="finance-card overflow-hidden transition-all">
                {{-- En-tête accordéon --}}
                <div x-on:click="open = !open"
                     class="flex cursor-pointer items-center justify-between p-4 bg-slate-50/50 hover:bg-slate-50 dark:bg-slate-800/40 dark:hover:bg-slate-800/60 border-b border-slate-100 dark:border-slate-800 transition-colors">
                    <div class="flex items-center gap-3">
                        <span class="flex h-7 w-12 items-center justify-center rounded-lg bg-[#ECFDF5] text-xs font-bold text-[#0DAD98] tracking-wider uppercase">
                            {{ $group->badge }}
                        </span>
                        <div>
                            <h3 class="text-xs sm:text-sm font-bold text-slate-900 dark:text-white">
                                {{ $group->period_label }}
                            </h3>
                            <p class="text-[11px] text-slate-400">
                                {{ $group->expenses_count }} dépense(s) et achat(s) enregistrés
                            </p>
                        </div>
                    </div>

                    <div class="flex items-center gap-4">
                        <div class="text-right">
                            <span class="text-xs sm:text-sm font-extrabold text-rose-600 dark:text-rose-400 whitespace-nowrap">
                                {{ format_price($group->total_amount, 'MAD') }}
                            </span>
                            <span class="block text-[10px] text-slate-400">Total mois</span>
                        </div>
                        <span class="text-slate-400 transition-transform duration-200" :class="{ 'rotate-180': open }">
                            ▼
                        </span>
                    </div>
                </div>

                {{-- Tableau à l'ouverture --}}
                <div x-show="open" class="overflow-x-auto">
                    <table class="w-full text-left text-xs">
                        <thead class="bg-slate-50/80 text-[10px] uppercase font-bold text-slate-400 border-b border-slate-100 dark:bg-slate-800/50 dark:border-slate-800">
                            <tr>
                                <th class="py-2.5 px-4">Date</th>
                                <th class="py-2.5 px-4">Titre & Fournisseur</th>
                                <th class="py-2.5 px-4">Articles achetés</th>
                                <th class="py-2.5 px-4">Catégorie</th>
                                <th class="py-2.5 px-4">Mode</th>
                                <th class="py-2.5 px-4 text-right">Montant</th>
                                <th class="py-2.5 px-4 text-center">Justificatif</th>
                                <th class="py-2.5 px-4 text-right">Actions</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100 dark:divide-slate-800">
                            @foreach($group->items as $exp)
                                @php
                                    $isCancelled = $exp->status === 'cancelled';
                                @endphp
                                <tr class="hover:bg-slate-50/50 dark:hover:bg-slate-800/30 {{ $isCancelled ? 'opacity-50 line-through' : '' }}">
                                    <td class="py-3 px-4 text-slate-600 whitespace-nowrap">
                                        {{ $exp->expense_date ? $exp->expense_date->format('d/m/Y') : '—' }}
                                    </td>
                                    <td class="py-3 px-4 font-semibold text-slate-900 dark:text-white whitespace-nowrap">
                                        {{ $exp->title }}
                                        @if($exp->supplier)
                                            <span class="text-[#0DAD98] text-[10px] font-medium block">🏢 {{ $exp->supplier }}</span>
                                        @endif
                                        @if($exp->reference)
                                            <span class="text-slate-400 text-[10px] font-normal block font-mono">Réf: {{ $exp->reference }}</span>
                                        @endif
                                    </td>
                                    <td class="py-3 px-4 text-slate-700 dark:text-slate-300">
                                        @if($exp->items->isNotEmpty())
                                            <div class="flex flex-wrap gap-1">
                                                @foreach($exp->items as $item)
                                                    <span class="inline-flex items-center gap-1 rounded bg-slate-100 dark:bg-slate-800 px-2 py-0.5 text-[10px] text-slate-700 dark:text-slate-300">
                                                        <strong>{{ $item->quantity }}x</strong> {{ $item->name }} ({{ format_price($item->unit_price, 'MAD') }})
                                                    </span>
                                                @endforeach
                                            </div>
                                        @else
                                            <span class="text-slate-400 text-[11px]">Charge globale</span>
                                        @endif
                                    </td>
                                    <td class="py-3 px-4 whitespace-nowrap">
                                        <span class="inline-flex items-center rounded-full bg-slate-100 dark:bg-slate-800 px-2 py-0.5 text-[10px] font-semibold text-slate-700 dark:text-slate-300">
                                            {{ $exp->category->name ?? 'Générale' }}
                                        </span>
                                    </td>
                                    <td class="py-3 px-4 whitespace-nowrap text-slate-600">
                                        @if($exp->payment_method === 'cash') 💵 Espèces
                                        @elseif($exp->payment_method === 'check') 📝 Chèque
                                        @elseif($exp->payment_method === 'bank_transfer') 🏦 Virement
                                        @else 💳 {{ ucfirst($exp->payment_method) }}
                                        @endif
                                    </td>
                                    <td class="py-3 px-4 text-right font-bold {{ $isCancelled ? 'text-slate-400' : 'text-rose-600' }} whitespace-nowrap">
                                        {{ format_price($exp->amount, 'MAD') }}
                                        @if($isCancelled)
                                            <span class="block text-[9px] text-rose-500 font-bold uppercase no-underline">Annulé</span>
                                        @endif
                                    </td>
                                    <td class="py-3 px-4 text-center whitespace-nowrap">
                                        @if($exp->receipt_path)
                                            <a href="{{ route('super-admin.finance.expenses.receipt', $exp->id) }}" target="_blank"
                                               class="inline-flex items-center gap-1 text-[11px] font-bold text-[#0DAD98] hover:underline">
                                                <span>📎 Reçu</span>
                                            </a>
                                        @else
                                            <span class="text-slate-400 text-[10px]">Aucun</span>
                                        @endif
                                    </td>
                                    <td class="py-3 px-4 text-right whitespace-nowrap">
                                        <div class="flex items-center justify-end gap-1.5">
                                            <button type="button" x-on:click="openDetail({{ Js::from($exp) }})"
                                                    class="rounded-md bg-slate-100 hover:bg-slate-200 px-2 py-1 text-[10px] font-semibold text-slate-700 dark:bg-slate-800 dark:text-slate-300">
                                                Détails
                                            </button>
                                            @if(! $isCancelled)
                                                <form method="POST" action="{{ route('super-admin.finance.expenses.cancel', $exp->id) }}" onsubmit="return confirm('Confirmer l\'annulation de cette dépense ? Son montant sera réintégré au budget.');">
                                                    @csrf
                                                    @method('PATCH')
                                                    <button type="submit" class="rounded-md bg-amber-50 hover:bg-amber-100 px-2 py-1 text-[10px] font-semibold text-amber-700">
                                                        Annuler
                                                    </button>
                                                </form>
                                            @endif
                                        </div>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>

                    {{-- Pied de tableau avec totaux par mode (Cad Syndic) --}}
                    <div class="flex flex-wrap items-center justify-between gap-3 bg-slate-50/40 p-3.5 border-t border-slate-100 dark:border-slate-800 text-[11px] font-semibold text-slate-600 dark:text-slate-300">
                        <div class="flex items-center gap-4">
                            <span>Espèces : <strong class="text-slate-900 dark:text-white">{{ format_price($group->cash_total, 'MAD') }}</strong></span>
                            <span>Chèques : <strong class="text-slate-900 dark:text-white">{{ format_price($group->check_total, 'MAD') }}</strong></span>
                            <span>Virements : <strong class="text-slate-900 dark:text-white">{{ format_price($group->transfer_total, 'MAD') }}</strong></span>
                        </div>
                        <div class="text-rose-600 font-extrabold text-xs">
                            Total mois : {{ format_price($group->total_amount, 'MAD') }}
                        </div>
                    </div>
                </div>
            </div>
        @empty
            <div class="finance-card p-12 text-center text-slate-400">
                <p class="text-xs font-semibold">Aucune dépense enregistrée sur cette sélection.</p>
            </div>
        @endforelse
    </div>

    {{-- MODALE COMPLÈTE AJOUTER UNE DÉPENSE / ACHAT MULTI-ARTICLES (STYLE CAD SYNDIC) --}}
    <div x-show="expenseModalOpen" x-cloak
         class="fixed inset-0 z-50 flex items-center justify-center bg-slate-900/40 p-4 backdrop-blur-xs overflow-y-auto">
        <div x-on:click.outside="expenseModalOpen = false"
             class="w-full max-w-2xl rounded-2xl bg-white p-6 shadow-2xl dark:bg-slate-900 dark:border dark:border-slate-800 my-8">

            <div class="flex items-center justify-between pb-4 border-b border-slate-100 dark:border-slate-800">
                <div class="flex items-center gap-2.5">
                    <span class="flex h-9 w-9 items-center justify-center rounded-xl bg-rose-50 text-rose-600 text-lg font-bold">
                        🛒
                    </span>
                    <div>
                        <h3 class="text-sm font-bold text-slate-900 dark:text-white">Enregistrer une dépense ou un achat</h3>
                        <p class="text-[11px] text-slate-500">Ajout multi-articles, calcul automatique et déduction budgétaire</p>
                    </div>
                </div>
                <button type="button" x-on:click="expenseModalOpen = false" class="text-slate-400 hover:text-slate-600">✕</button>
            </div>

            <form method="POST" action="{{ route('super-admin.finance.expenses.store') }}" enctype="multipart/form-data" class="mt-4 space-y-4">
                @csrf
                <input type="hidden" name="school_year" value="{{ $schoolYear }}">

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                    {{-- Titre de la dépense --}}
                    <div>
                        <label class="block text-xs font-semibold text-slate-700 dark:text-slate-300 mb-1">
                            Titre de la dépense <span class="text-rose-500">*</span>
                        </label>
                        <input type="text" name="title" required placeholder="Ex: Achat de fournitures scolaires"
                               class="w-full rounded-lg border-slate-200 text-xs text-slate-800 focus:border-[#0DAD98] focus:ring-1 focus:ring-[#0DAD98]">
                    </div>

                    {{-- Centre concerné --}}
                    <div>
                        <label class="block text-xs font-semibold text-slate-700 dark:text-slate-300 mb-1">
                            Centre concerné <span class="text-rose-500">*</span>
                        </label>
                        <select name="center_id" required class="w-full rounded-lg border-slate-200 text-xs text-slate-800 focus:border-[#0DAD98] focus:ring-1 focus:ring-[#0DAD98]">
                            @foreach($centers as $c)
                                <option value="{{ $c->id }}" {{ $centerId === $c->id ? 'selected' : '' }}>{{ $c->name }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-3 gap-3">
                    {{-- Catégorie --}}
                    <div>
                        <label class="block text-xs font-semibold text-slate-700 dark:text-slate-300 mb-1">
                            Catégorie <span class="text-rose-500">*</span>
                        </label>
                        <select name="category_id" required class="w-full rounded-lg border-slate-200 text-xs text-slate-800 focus:border-[#0DAD98] focus:ring-1 focus:ring-[#0DAD98]">
                            @foreach($categories as $cat)
                                <option value="{{ $cat->id }}">{{ $cat->name }}</option>
                            @endforeach
                        </select>
                    </div>

                    {{-- Fournisseur --}}
                    <div>
                        <label class="block text-xs font-semibold text-slate-700 dark:text-slate-300 mb-1">
                            Fournisseur (facultatif)
                        </label>
                        <input type="text" name="supplier" placeholder="Ex: Librairie Al Maaref, HP Maroc..."
                               class="w-full rounded-lg border-slate-200 text-xs text-slate-800 focus:border-[#0DAD98] focus:ring-1 focus:ring-[#0DAD98]">
                    </div>

                    {{-- Date d'achat --}}
                    <div>
                        <label class="block text-xs font-semibold text-slate-700 dark:text-slate-300 mb-1">
                            Date d'achat <span class="text-rose-500">*</span>
                        </label>
                        <input type="date" name="expense_date" value="{{ date('Y-m-d') }}" required
                               class="w-full rounded-lg border-slate-200 text-xs text-slate-800 focus:border-[#0DAD98] focus:ring-1 focus:ring-[#0DAD98]">
                    </div>
                </div>

                {{-- SECTION ARTICLES ACHETÉS (MULTI-LIGNES AVEC CALCUL AUTO) --}}
                <div class="rounded-xl border border-slate-200 bg-slate-50/50 p-4 dark:border-slate-800 dark:bg-slate-800/40">
                    <div class="flex items-center justify-between mb-3">
                        <span class="text-xs font-bold text-slate-800 dark:text-white uppercase tracking-wider">
                            Articles / Produits achetés
                        </span>
                        <button type="button" x-on:click="addItem()" class="inline-flex items-center gap-1 rounded-md bg-[#ECFDF5] px-2.5 py-1 text-[11px] font-bold text-[#0DAD98] hover:bg-[#0DAD98]/20 transition-colors">
                            <span>+ Ajouter un article</span>
                        </button>
                    </div>

                    <div class="space-y-2">
                        <template x-for="(item, index) in items" :key="index">
                            <div class="flex items-center gap-2">
                                <div class="flex-1">
                                    <input type="text" :name="'items['+index+'][name]'" x-model="item.name" required
                                           placeholder="Ex: Feutres, Papier A4..."
                                           class="w-full rounded-lg border-slate-200 bg-white py-1.5 px-2.5 text-xs text-slate-800">
                                </div>
                                <div class="w-24">
                                    <input type="number" min="1" step="1" :name="'items['+index+'][quantity]'" x-model.number="item.quantity" required
                                           placeholder="Qté"
                                           class="w-full rounded-lg border-slate-200 bg-white py-1.5 px-2.5 text-xs text-slate-800 text-center">
                                </div>
                                <div class="w-32">
                                    <div class="relative">
                                        <input type="number" min="0" step="0.01" :name="'items['+index+'][unit_price]'" x-model.number="item.unit_price" required
                                               placeholder="Prix unit."
                                               class="w-full rounded-lg border-slate-200 bg-white py-1.5 pl-2.5 pr-8 text-xs font-semibold text-slate-900">
                                        <span class="absolute inset-y-0 right-2 flex items-center text-[10px] text-slate-400 font-bold">DH</span>
                                    </div>
                                </div>
                                <div class="w-28 text-right font-extrabold text-xs text-slate-900 dark:text-white">
                                    <span x-text="((item.quantity || 0) * (item.unit_price || 0)).toFixed(2)"></span> DH
                                </div>
                                <button type="button" x-on:click="removeItem(index)" class="text-slate-400 hover:text-rose-500 px-1 text-sm font-bold">
                                    ✕
                                </button>
                            </div>
                        </template>
                    </div>

                    {{-- Résumé du calcul automatique --}}
                    <div class="mt-4 pt-3 border-t border-slate-200 flex items-center justify-between text-xs font-bold text-slate-800 dark:text-white">
                        <span>Montant total calculé :</span>
                        <div class="text-base text-rose-600 font-black">
                            <span x-text="getTotal().toFixed(2)"></span> MAD
                        </div>
                    </div>
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                    {{-- Mode de règlement --}}
                    <div>
                        <label class="block text-xs font-semibold text-slate-700 dark:text-slate-300 mb-1">
                            Mode de paiement <span class="text-rose-500">*</span>
                        </label>
                        <select name="payment_method" required class="w-full rounded-lg border-slate-200 text-xs text-slate-800 focus:border-[#0DAD98] focus:ring-1 focus:ring-[#0DAD98]">
                            <option value="bank_transfer">Virement bancaire</option>
                            <option value="check">Chèque</option>
                            <option value="cash">Espèces</option>
                            <option value="credit_card">Carte bancaire</option>
                        </select>
                    </div>

                    {{-- Justificatif (facture/reçu) --}}
                    <div>
                        <label class="block text-xs font-semibold text-slate-700 dark:text-slate-300 mb-1">
                            Justificatif (PDF, Image)
                        </label>
                        <input type="file" name="receipt" accept=".pdf,.png,.jpg,.jpeg"
                               class="w-full text-xs text-slate-500 file:mr-2 file:py-1.5 file:px-3 file:rounded-lg file:border-0 file:text-xs file:font-semibold file:bg-[#ECFDF5] file:text-[#0DAD98] hover:file:bg-[#0DAD98]/20">
                    </div>
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                    <div>
                        <label class="block text-xs font-semibold text-slate-700 dark:text-slate-300 mb-1">
                            N° Référence / Facture
                        </label>
                        <input type="text" name="reference" placeholder="Ex: FACT-2026-902"
                               class="w-full rounded-lg border-slate-200 text-xs text-slate-800">
                    </div>
                    <div>
                        <label class="block text-xs font-semibold text-slate-700 dark:text-slate-300 mb-1">
                            Description complémentaire
                        </label>
                        <input type="text" name="description" placeholder="Ex: Remplacement du stock épuisé"
                               class="w-full rounded-lg border-slate-200 text-xs text-slate-800">
                    </div>
                </div>

                <div class="flex justify-end gap-2.5 pt-4 border-t border-slate-100 dark:border-slate-800">
                    <button type="button" x-on:click="expenseModalOpen = false" class="px-3.5 py-2 text-xs font-semibold text-slate-600 hover:bg-slate-100 rounded-lg">
                        Annuler
                    </button>
                    <button type="submit" class="finance-btn-primary">
                        Enregistrer la dépense
                    </button>
                </div>
            </form>
        </div>
    </div>

    {{-- MODALE DE CONSULTATION DU DÉTAIL D'UNE DÉPENSE --}}
    <div x-show="detailModalOpen" x-cloak
         class="fixed inset-0 z-50 flex items-center justify-center bg-slate-900/40 p-4 backdrop-blur-xs">
        <div x-on:click.outside="detailModalOpen = false"
             class="w-full max-w-lg rounded-2xl bg-white p-6 shadow-2xl dark:bg-slate-900 dark:border dark:border-slate-800">
            <template x-if="activeExpense">
                <div>
                    <div class="flex items-center justify-between pb-4 border-b border-slate-100">
                        <div>
                            <h3 class="text-sm font-bold text-slate-900 dark:text-white" x-text="activeExpense.title"></h3>
                            <span class="text-[11px] text-slate-400" x-text="activeExpense.expense_date"></span>
                        </div>
                        <button type="button" x-on:click="detailModalOpen = false" class="text-slate-400 hover:text-slate-600">✕</button>
                    </div>

                    <div class="mt-4 space-y-3 text-xs">
                        <div class="flex justify-between py-1 border-b border-slate-50">
                            <span class="text-slate-400">Montant total :</span>
                            <span class="font-extrabold text-rose-600" x-text="activeExpense.amount + ' MAD'"></span>
                        </div>
                        <div class="flex justify-between py-1 border-b border-slate-50">
                            <span class="text-slate-400">Mode de paiement :</span>
                            <span class="font-semibold text-slate-800" x-text="activeExpense.payment_method"></span>
                        </div>
                        <template x-if="activeExpense.supplier">
                            <div class="flex justify-between py-1 border-b border-slate-50">
                                <span class="text-slate-400">Fournisseur :</span>
                                <span class="font-semibold text-[#0DAD98]" x-text="activeExpense.supplier"></span>
                            </div>
                        </template>
                        <template x-if="activeExpense.items && activeExpense.items.length > 0">
                            <div class="pt-2">
                                <span class="block text-[11px] font-bold text-slate-700 mb-1.5 uppercase">Articles inclus :</span>
                                <div class="rounded-lg bg-slate-50 p-2.5 space-y-1">
                                    <template x-for="it in activeExpense.items" :key="it.id">
                                        <div class="flex justify-between text-[11px]">
                                            <span x-text="it.quantity + 'x ' + it.name"></span>
                                            <strong x-text="it.total_price + ' MAD'"></strong>
                                        </div>
                                    </template>
                                </div>
                            </div>
                        </template>
                    </div>

                    <div class="mt-5 flex justify-end">
                        <button type="button" x-on:click="detailModalOpen = false" class="finance-btn-secondary py-1.5 px-3 text-xs">Fermer</button>
                    </div>
                </div>
            </template>
        </div>
    </div>

</div>
@endsection
