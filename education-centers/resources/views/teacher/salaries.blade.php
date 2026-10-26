@extends('layouts.app')

@section('title', __('Mes salaires') . ' - ' . config('app.name'))

@php
    $toneFor = [
        'paid' => 'success',
        'partial' => 'warning',
        'pending' => 'info',
        'overdue' => 'danger',
        'none' => 'neutral',
    ];
    $current = $situation['current'];
@endphp

@section('content')
    <x-ui.page-header
        :title="__('Mes salaires')"
        :description="__('Le suivi de vos salaires, calculé à partir des fiches de paie enregistrées par l’administration. Un statut « en retard » n’apparaît que si une date de versement était prévue.')"
    />

    {{-- Alerte personnelle (sans date prévue, jamais « en retard ») --}}
    @if($situation['has_alert'])
        <div class="mb-6 rounded-xl border border-rose-200 bg-rose-50 p-4 sm:p-5 dark:border-rose-500/30 dark:bg-rose-500/10">
            <div class="flex items-start gap-3">
                <span class="ec-icon-chip shrink-0 bg-rose-100 text-rose-600 dark:bg-rose-500/20 dark:text-rose-300">
                    <x-icon name="warning" class="h-5 w-5" />
                </span>
                <div class="min-w-0">
                    <h2 class="text-sm font-bold text-rose-800 dark:text-rose-200">{{ __('Salaire en attente de versement') }}</h2>
                    <ul class="mt-1.5 space-y-1 text-sm text-rose-700 dark:text-rose-300">
                        @foreach($situation['overdue_salaries'] as $salary)
                            <li>
                                {{ __('Votre salaire de :periode est en attente de versement. Solde dû : :montant.', [
                                    'periode' => $salary->month_name.' '.$salary->year,
                                    'montant' => format_price($salary->remaining_amount, $salary->currency),
                                ]) }}
                            </li>
                        @endforeach
                    </ul>
                </div>
            </div>
        </div>
    @endif

    @if($current === null)
        <x-ui.card>
            <x-ui.empty
                :title="__('Aucun salaire enregistré')"
                :description="__('Aucune fiche de salaire n’est encore associée à votre compte.')"
            />
        </x-ui.card>
    @else
        {{-- Période courante --}}
        <article class="ec-panel mb-6 overflow-hidden">
            <div class="flex flex-col gap-3 border-b border-slate-100 p-5 dark:border-slate-800 sm:flex-row sm:items-start sm:justify-between">
                <div class="min-w-0">
                    <p class="text-xs font-bold uppercase tracking-wide text-brand-600 dark:text-brand-300">{{ __('Période en cours') }}</p>
                    <h2 class="mt-0.5 text-lg font-bold text-slate-900 dark:text-white">
                        {{ __('Salaire') }} — {{ $current->month_name }} {{ $current->year }}
                    </h2>
                    @if($current->school_year)
                        <p class="mt-0.5 text-sm text-slate-500 dark:text-slate-400">{{ $current->school_year }}</p>
                    @endif
                </div>
                <x-ui.badge :tone="$toneFor[$current->personal_status] ?? 'neutral'">{{ $current->personal_status_label }}</x-ui.badge>
            </div>

            <div class="grid gap-4 p-5 sm:grid-cols-2 xl:grid-cols-4">
                <div class="rounded-lg bg-slate-50 px-3.5 py-3 dark:bg-slate-800/60">
                    <p class="text-xs font-semibold uppercase tracking-wide text-slate-400">{{ __('Montant dû') }}</p>
                    <p class="mt-1 text-lg font-bold text-slate-900 dark:text-white">{{ format_price($current->expected_amount, $current->currency) }}</p>
                </div>
                <div class="rounded-lg bg-emerald-50 px-3.5 py-3 dark:bg-emerald-500/10">
                    <p class="text-xs font-semibold uppercase tracking-wide text-emerald-600 dark:text-emerald-300">{{ __('Montant versé') }}</p>
                    <p class="mt-1 text-lg font-bold text-emerald-700 dark:text-emerald-200">{{ format_price($current->paid_amount, $current->currency) }}</p>
                </div>
                <div class="rounded-lg bg-amber-50 px-3.5 py-3 dark:bg-amber-500/10">
                    <p class="text-xs font-semibold uppercase tracking-wide text-amber-600 dark:text-amber-300">{{ __('Solde restant') }}</p>
                    <p class="mt-1 text-lg font-bold text-amber-700 dark:text-amber-200">{{ format_price($current->remaining_amount, $current->currency) }}</p>
                </div>
                <div class="rounded-lg bg-blue-50 px-3.5 py-3 dark:bg-blue-500/10">
                    <p class="text-xs font-semibold uppercase tracking-wide text-blue-600 dark:text-blue-300">{{ __('Date de versement') }}</p>
                    <p class="mt-1 text-sm font-bold text-blue-800 dark:text-blue-200">
                        @if($current->payment_date)
                            {{ format_date($current->payment_date, 'd/m/Y') }}
                        @elseif($current->scheduled_payment_date)
                            {{ __('Prévue le') }} {{ format_date($current->scheduled_payment_date, 'd/m/Y') }}
                        @else
                            —
                        @endif
                    </p>
                    @if($current->payment_method)
                        <p class="mt-0.5 text-xs text-blue-600/80 dark:text-blue-300/80">{{ $current->payment_method }}</p>
                    @endif
                </div>
            </div>
        </article>

        {{-- Récapitulatif global --}}
        <div class="mb-6 grid gap-4 sm:grid-cols-3">
            <x-ui.stat :label="__('Total dû')" :value="format_price($situation['total_expected'], $situation['currency'])" icon="banknotes" tone="primary" />
            <x-ui.stat :label="__('Total versé')" :value="format_price($situation['total_paid'], $situation['currency'])" icon="check" tone="success" />
            <x-ui.stat :label="__('Solde global')" :value="format_price($situation['total_remaining'], $situation['currency'])" icon="clock" tone="warning" />
        </div>

        {{-- Historique des salaires --}}
        <section class="ec-panel overflow-hidden">
            <div class="ec-panel__head">
                <div class="ec-panel__title">
                    <span class="ec-icon-chip bg-brand-50 text-brand-600 dark:bg-brand-500/15 dark:text-brand-300">
                        <x-icon name="banknotes" class="h-5 w-5" />
                    </span>
                    {{ __('Historique des salaires') }}
                </div>
            </div>
            <div class="overflow-x-auto">
                <table class="ec-table min-w-full text-sm">
                    <thead>
                        <tr class="text-start text-xs uppercase tracking-wide text-slate-400">
                            <th class="px-4 py-2.5 text-start">{{ __('Période') }}</th>
                            <th class="px-4 py-2.5 text-start">{{ __('Montant dû') }}</th>
                            <th class="px-4 py-2.5 text-start">{{ __('Versé') }}</th>
                            <th class="px-4 py-2.5 text-start">{{ __('Restant') }}</th>
                            <th class="px-4 py-2.5 text-start">{{ __('Date') }}</th>
                            <th class="px-4 py-2.5 text-start">{{ __('Mode') }}</th>
                            <th class="px-4 py-2.5 text-start">{{ __('Statut') }}</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100 dark:divide-slate-800">
                        @foreach($situation['salaries'] as $salary)
                            <tr>
                                <td class="px-4 py-2.5 font-medium text-slate-800 dark:text-slate-100">{{ $salary->month_name }} {{ $salary->year }}</td>
                                <td class="px-4 py-2.5 text-slate-700 dark:text-slate-200">{{ format_price($salary->expected_amount, $salary->currency) }}</td>
                                <td class="px-4 py-2.5 text-emerald-700 dark:text-emerald-300">{{ format_price($salary->paid_amount, $salary->currency) }}</td>
                                <td class="px-4 py-2.5 text-amber-700 dark:text-amber-300">{{ format_price($salary->remaining_amount, $salary->currency) }}</td>
                                <td class="px-4 py-2.5 text-slate-600 dark:text-slate-300">
                                    {{ $salary->payment_date ? format_date($salary->payment_date, 'd/m/Y') : ($salary->scheduled_payment_date ? format_date($salary->scheduled_payment_date, 'd/m/Y') : '—') }}
                                </td>
                                <td class="px-4 py-2.5 text-slate-600 dark:text-slate-300">{{ $salary->payment_method ?? '—' }}</td>
                                <td class="px-4 py-2.5">
                                    <x-ui.badge :tone="$toneFor[$salary->personal_status] ?? 'neutral'">{{ $salary->personal_status_label }}</x-ui.badge>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </section>
    @endif
@endsection
