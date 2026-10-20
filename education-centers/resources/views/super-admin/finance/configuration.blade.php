@extends('layouts.app')

@section('title', 'Configuration des prix & salaires — Gestion financière')

@section('content')
<div class="space-y-8">

    {{-- En-tête --}}
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 border-b border-slate-200/80 pb-6 dark:border-slate-800">
        <div>
            <div class="inline-flex items-center gap-2 rounded-full bg-emerald-50 border border-emerald-200/70 px-3.5 py-1 text-xs font-semibold text-emerald-800 dark:bg-emerald-950/50 dark:border-emerald-800/70 dark:text-emerald-300 mb-2">
                <span>⚙️</span>
                <span>Gestion financière · Paramètres tarifaires</span>
            </div>
            <h1 class="text-3xl font-extrabold tracking-tight text-slate-900 dark:text-white">
                Configuration des prix & salaires
            </h1>
            <p class="mt-1 text-sm text-slate-600 dark:text-slate-400">
                Ajustez le prix annuel de chaque formation et le salaire mensuel fixe de chaque enseignant selon vos besoins réels.
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

    @if(session('success'))
        <div class="rounded-xl border border-emerald-200 bg-emerald-50 p-4 text-sm font-semibold text-emerald-800 dark:border-emerald-800/60 dark:bg-emerald-950/40 dark:text-emerald-200">
            {{ session('success') }}
        </div>
    @endif

    <form method="POST" action="{{ route('super-admin.finance.configuration.update') }}" class="space-y-8">
        @csrf
        @method('PUT')

        {{-- Section 1 : Formations et prix annuels --}}
        <div class="rounded-2xl border border-slate-200 bg-white p-6 shadow-xs dark:border-slate-800 dark:bg-slate-900">
            <div class="border-b border-slate-100 pb-4 dark:border-slate-800 mb-5">
                <h2 class="text-lg font-bold text-slate-900 dark:text-white flex items-center gap-2">
                    <span class="flex h-2.5 w-2.5 rounded-full bg-emerald-500"></span>
                    Prix annuels des formations (MAD / DH)
                </h2>
                <p class="text-xs text-slate-500 dark:text-slate-400">
                    Ces prix déterminent le montant attendu par étudiant pour chaque inscription annuelle.
                </p>
            </div>

            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-slate-200 dark:divide-slate-800 text-left text-sm">
                    <thead class="bg-slate-50/80 text-xs font-bold uppercase tracking-wider text-slate-500 dark:bg-slate-800/50 dark:text-slate-400">
                        <tr>
                            <th class="px-4 py-3">Formation</th>
                            <th class="px-4 py-3">Centre</th>
                            <th class="px-4 py-3">Domaine de rattachement</th>
                            <th class="px-4 py-3 text-right">Prix annuel configuré (DH)</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100 dark:divide-slate-800">
                        @forelse($formations as $i => $formation)
                            <tr>
                                <td class="px-4 py-3 font-semibold text-slate-900 dark:text-white">
                                    <input type="hidden" name="formations[{{ $i }}][id]" value="{{ $formation->id }}">
                                    <div>{{ $formation->name }}</div>
                                    <div class="text-xs text-slate-400 font-mono">{{ $formation->code }}</div>
                                </td>
                                <td class="px-4 py-3 text-xs text-slate-600 dark:text-slate-300">
                                    {{ $formation->center?->name ?? 'N/A' }}
                                </td>
                                <td class="px-4 py-3">
                                    <select name="formations[{{ $i }}][domain]"
                                            class="rounded-xl border-slate-300 bg-white text-xs font-medium text-slate-800 focus:border-emerald-500 focus:ring-emerald-500 dark:border-slate-700 dark:bg-slate-800 dark:text-slate-100">
                                        <option value="">Sélectionner un domaine...</option>
                                        @foreach($domains as $slug => $label)
                                            <option value="{{ $slug }}" {{ ($formation->domain ?? '') === $slug ? 'selected' : '' }}>
                                                {{ $label }}
                                            </option>
                                        @endforeach
                                    </select>
                                </td>
                                <td class="px-4 py-3 text-right">
                                    <div class="inline-flex items-center gap-2">
                                        <input type="number" step="0.01" min="0"
                                               name="formations[{{ $i }}][annual_price]"
                                               value="{{ old("formations.{$i}.annual_price", $formation->annual_price ?? \App\Services\FinanceService::getFormationAnnualPrice($formation)) }}"
                                               class="w-32 rounded-xl border-slate-300 bg-white text-right font-bold text-sm text-slate-900 focus:border-emerald-500 focus:ring-emerald-500 dark:border-slate-700 dark:bg-slate-800 dark:text-white">
                                        <span class="text-xs font-bold text-slate-500">DH</span>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="4" class="px-4 py-6 text-center text-sm text-slate-500">
                                    Aucune formation enregistrée.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        {{-- Section 2 : Professeurs et salaires mensuels --}}
        <div class="rounded-2xl border border-slate-200 bg-white p-6 shadow-xs dark:border-slate-800 dark:bg-slate-900">
            <div class="border-b border-slate-100 pb-4 dark:border-slate-800 mb-5">
                <h2 class="text-lg font-bold text-slate-900 dark:text-white flex items-center gap-2">
                    <span class="flex h-2.5 w-2.5 rounded-full bg-blue-500"></span>
                    Salaires mensuels fixes des enseignants (MAD / DH)
                </h2>
                <p class="text-xs text-slate-500 dark:text-slate-400">
                    Chaque professeur peut avoir une rémunération mensuelle personnalisée.
                </p>
            </div>

            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-slate-200 dark:divide-slate-800 text-left text-sm">
                    <thead class="bg-slate-50/80 text-xs font-bold uppercase tracking-wider text-slate-500 dark:bg-slate-800/50 dark:text-slate-400">
                        <tr>
                            <th class="px-4 py-3">Professeur</th>
                            <th class="px-4 py-3">Centre</th>
                            <th class="px-4 py-3">Domaine principal</th>
                            <th class="px-4 py-3 text-right">Salaire mensuel (DH)</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100 dark:divide-slate-800">
                        @forelse($teachers as $j => $teacher)
                            <tr>
                                <td class="px-4 py-3 font-semibold text-slate-900 dark:text-white">
                                    <input type="hidden" name="teachers[{{ $j }}][id]" value="{{ $teacher->id }}">
                                    <div>{{ $teacher->full_name }}</div>
                                    <div class="text-xs text-slate-400 font-mono">{{ $teacher->teacher_number }}</div>
                                </td>
                                <td class="px-4 py-3 text-xs text-slate-600 dark:text-slate-300">
                                    {{ $teacher->center?->name ?? 'N/A' }}
                                </td>
                                <td class="px-4 py-3">
                                    <select name="teachers[{{ $j }}][domain]"
                                            class="rounded-xl border-slate-300 bg-white text-xs font-medium text-slate-800 focus:border-blue-500 focus:ring-blue-500 dark:border-slate-700 dark:bg-slate-800 dark:text-slate-100">
                                        <option value="">Sélectionner un domaine...</option>
                                        @foreach($domains as $slug => $label)
                                            <option value="{{ $slug }}" {{ ($teacher->domain ?? '') === $slug ? 'selected' : '' }}>
                                                {{ $label }}
                                            </option>
                                        @endforeach
                                    </select>
                                </td>
                                <td class="px-4 py-3 text-right">
                                    <div class="inline-flex items-center gap-2">
                                        <input type="number" step="0.01" min="0"
                                               name="teachers[{{ $j }}][monthly_salary]"
                                               value="{{ old("teachers.{$j}.monthly_salary", $teacher->monthly_salary ?? \App\Services\FinanceService::getTeacherMonthlySalary($teacher)) }}"
                                               class="w-32 rounded-xl border-slate-300 bg-white text-right font-bold text-sm text-slate-900 focus:border-blue-500 focus:ring-blue-500 dark:border-slate-700 dark:bg-slate-800 dark:text-white">
                                        <span class="text-xs font-bold text-slate-500">DH</span>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="4" class="px-4 py-6 text-center text-sm text-slate-500">
                                    Aucun professeur enregistré.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        <div class="flex justify-end gap-3">
            <button type="submit" class="rounded-xl bg-emerald-600 px-6 py-2.5 text-sm font-bold text-white shadow-sm hover:bg-emerald-500 transition-colors">
                Enregistrer toutes les modifications
            </button>
        </div>
    </form>

</div>
@endsection
