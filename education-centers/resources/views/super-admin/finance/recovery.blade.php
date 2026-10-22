@extends('layouts.app')

@section('title', 'Recouvrement & Impayés — Gestion financière')

@section('content')
<div class="space-y-6" x-data="{
    openWhatsApp(phone, studentName, amount, formation) {
        if (!phone) {
            alert('Aucun numéro de téléphone enregistré pour cet étudiant.');
            return;
        }
        const cleanPhone = phone.replace(/[^0-9]/g, '');
        const text = encodeURIComponent(`Bonjour ${studentName}, nous vous informons qu'un solde restant de ${amount} MAD est dû au titre de votre formation ${formation} chez Education Centers. Merci de régulariser votre situation auprès de l'administration.`);
        window.open(`https://wa.me/${cleanPhone}?text=${text}`, '_blank');
    }
}">

    {{-- En-tête de la page --}}
    <div class="flex flex-col gap-4 lg:flex-row lg:items-center lg:justify-between border-b border-slate-200/90 pb-5 dark:border-slate-800">
        <div>
            <div class="inline-flex items-center gap-1.5 rounded-full bg-amber-50 px-2.5 py-0.5 text-xs font-semibold text-amber-600 border border-amber-200 dark:bg-amber-950/30 dark:text-amber-400 mb-1.5 shadow-2xs">
                <span>⚖️</span>
                <span>Gestion financière · Créances & Relances étudiants</span>
            </div>
            <h1 class="text-2xl sm:text-3xl font-extrabold tracking-tight text-slate-900 dark:text-white">
                Recouvrement & Impayés
            </h1>
            <p class="mt-0.5 text-xs sm:text-sm text-slate-500 dark:text-slate-400">
                Suivi des échéances impayées, relances directes WhatsApp et encaissement des créances de scolarité.
            </p>
        </div>

        <div class="flex items-center gap-2.5">
            <a href="{{ route('super-admin.finance.dashboard') }}" class="finance-btn-secondary">
                <x-icon name="arrow-left" class="h-4 w-4 text-slate-500" />
                <span>Dashboard</span>
            </a>
            <a href="{{ route('super-admin.finance.students') }}" class="finance-btn-primary">
                <span>Tous les paiements</span>
            </a>
        </div>
    </div>

    {{-- 3 KPI Épurés (Capture 6 Cad Syndic) --}}
    <div class="grid gap-4 sm:grid-cols-3">
        <div class="finance-card p-4.5">
            <span class="block text-[11px] font-bold uppercase tracking-wider text-slate-400">Créance totale</span>
            <div class="mt-2 text-2xl lg:text-3xl font-extrabold text-rose-600 dark:text-rose-400 whitespace-nowrap">
                {{ format_price($totalDebt, 'MAD') }}
            </div>
            <div class="mt-3 text-[11px] text-slate-400 border-t border-slate-100 dark:border-slate-800/80 pt-2">
                Cumul des montants restant dus
            </div>
        </div>

        <div class="finance-card p-4.5">
            <span class="block text-[11px] font-bold uppercase tracking-wider text-slate-400">Dossiers débiteurs</span>
            <div class="mt-2 text-2xl lg:text-3xl font-extrabold text-amber-600 dark:text-amber-400 whitespace-nowrap">
                {{ $debtorsCount }} <span class="text-xs font-normal text-slate-400">étudiants</span>
            </div>
            <div class="mt-3 text-[11px] text-slate-400 border-t border-slate-100 dark:border-slate-800/80 pt-2">
                Avec solde scolarité positif
            </div>
        </div>

        <div class="finance-card p-4.5">
            <span class="block text-[11px] font-bold uppercase tracking-wider text-slate-400">Impayés totaux</span>
            <div class="mt-2 text-2xl lg:text-3xl font-extrabold text-slate-900 dark:text-white whitespace-nowrap">
                {{ $unpaidCount }} <span class="text-xs font-normal text-slate-400">sans aucun versement</span>
            </div>
            <div class="mt-3 text-[11px] text-slate-400 border-t border-slate-100 dark:border-slate-800/80 pt-2">
                Dossiers à relancer d'urgence
            </div>
        </div>
    </div>

    {{-- Filtres compacts --}}
    <form method="GET" action="{{ route('super-admin.finance.recovery') }}"
          class="rounded-xl border border-slate-200/90 bg-white p-3.5 shadow-[0_1px_3px_rgba(0,0,0,0.02)] dark:border-slate-800 dark:bg-slate-900">
        <div class="grid grid-cols-2 gap-3 sm:grid-cols-4 items-center">
            <div>
                <label class="block text-[10px] font-bold uppercase tracking-wider text-slate-400 mb-1">Recherche</label>
                <input type="text" name="search" value="{{ $search ?? '' }}" placeholder="Nom, matricule, formation..."
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
                <label class="block text-[10px] font-bold uppercase tracking-wider text-slate-400 mb-1">Domaine</label>
                <select name="domain" onchange="this.form.submit()"
                        class="w-full rounded-lg border-slate-200 bg-slate-50/50 py-1.5 px-2.5 text-xs font-medium text-slate-800 focus:border-[#0DAD98] focus:bg-white focus:ring-1 focus:ring-[#0DAD98]">
                    <option value="all" {{ $domain === 'all' ? 'selected' : '' }}>Tous domaines</option>
                    @foreach($domains as $slug => $d)
                        <option value="{{ $slug }}" {{ $domain === $slug ? 'selected' : '' }}>{{ is_array($d) ? ($d['name'] ?? $slug) : $d }}</option>
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

    {{-- Tableau : Liste des impayés avec bouton WhatsApp (Capture 6 Cad Syndic) --}}
    <div class="finance-card overflow-hidden">
        <div class="p-4 border-b border-slate-100 dark:border-slate-800 flex items-center justify-between">
            <h2 class="text-sm font-bold text-slate-900 dark:text-white">Liste des impayés & échéances dues</h2>
            <span class="text-xs text-slate-400">{{ $debtors->count() }} débiteur(s)</span>
        </div>

        <div class="overflow-x-auto">
            <table class="w-full text-left text-xs">
                <thead class="bg-slate-50/80 text-[10px] uppercase font-bold text-slate-400 border-b border-slate-100 dark:bg-slate-800/50 dark:border-slate-800">
                    <tr>
                        <th class="py-3 px-4">Étudiant</th>
                        <th class="py-3 px-4">Centre</th>
                        <th class="py-3 px-4">Formation</th>
                        <th class="py-3 px-4 text-right">Total attendu</th>
                        <th class="py-3 px-4 text-right">Déjà versé</th>
                        <th class="py-3 px-4 text-right">Cumul restant</th>
                        <th class="py-3 px-4 text-center">Relance</th>
                        <th class="py-3 px-4 text-right">Action</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100 dark:divide-slate-800">
                    @forelse($debtors as $debtor)
                        @php
                            $phone = $debtor->student->phone ?? $debtor->student->user->phone ?? null;
                        @endphp
                        <tr class="hover:bg-slate-50/50 dark:hover:bg-slate-800/30">
                            <td class="py-3 px-4 font-semibold text-slate-900 dark:text-white whitespace-nowrap">
                                {{ $debtor->student->full_name }}
                                <span class="block text-[10px] text-slate-400 font-mono">{{ $debtor->student->student_number ?? 'ID: '.$debtor->student->id }}</span>
                            </td>
                            <td class="py-3 px-4 text-slate-600 whitespace-nowrap">
                                {{ $debtor->center->name ?? 'Centre' }}
                            </td>
                            <td class="py-3 px-4 text-slate-700 whitespace-nowrap">
                                {{ $debtor->formation->name }}
                            </td>
                            <td class="py-3 px-4 text-right text-slate-600 whitespace-nowrap">
                                {{ format_price($debtor->annual_price, 'MAD') }}
                            </td>
                            <td class="py-3 px-4 text-right font-semibold text-[#0DAD98] whitespace-nowrap">
                                {{ format_price($debtor->paid_amount, 'MAD') }}
                            </td>
                            <td class="py-3 px-4 text-right font-black text-rose-600 whitespace-nowrap">
                                {{ format_price($debtor->remaining_amount, 'MAD') }}
                            </td>
                            <td class="py-3 px-4 text-center whitespace-nowrap">
                                <button type="button"
                                        x-on:click="openWhatsApp('{{ $phone }}', '{{ addslashes($debtor->student->full_name) }}', '{{ number_format($debtor->remaining_amount, 2) }}', '{{ addslashes($debtor->formation->name) }}')"
                                        class="inline-flex items-center gap-1.5 rounded-lg bg-[#25D366]/10 hover:bg-[#25D366]/20 px-2.5 py-1 text-[11px] font-bold text-[#128C7E] transition-colors"
                                        title="{{ $phone ? 'Relancer sur WhatsApp ('.$phone.')' : 'Aucun numéro disponible' }}">
                                    <span>💬</span>
                                    <span>WhatsApp</span>
                                </button>
                            </td>
                            <td class="py-3 px-4 text-right whitespace-nowrap">
                                <a href="{{ route('super-admin.finance.students', ['search' => $debtor->student->full_name, 'view' => 'students']) }}"
                                   class="finance-btn-secondary py-1 px-2.5 text-[11px]">
                                    <span>Encaisser</span>
                                </a>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="8" class="py-12 text-center text-xs text-slate-400">
                                Aucun impayé trouvé pour cette sélection.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

</div>
@endsection
