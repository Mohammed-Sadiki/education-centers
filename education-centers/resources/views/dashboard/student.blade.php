@extends('layouts.app')

@section('title', __('Tableau de bord') . ' — ' . config('app.name'))

@php
    $user = auth()->user();
    $avg = $grades->whereNotNull('grade')->avg('grade');
@endphp

@section('content')

    {{-- ── EN-TÊTE ────────────────────────────────────────────────────── --}}
    <div class="mb-8 flex flex-col gap-4 sm:flex-row sm:items-start sm:justify-between">
        <div>
            <h1 class="text-[1.9rem] font-extrabold tracking-tight text-slate-900 sm:text-[2.15rem] dark:text-white">
                {{ __('Bonjour,') }} {{ $user->name }} 👋
            </h1>
            <p class="mt-1.5 text-base text-slate-500 dark:text-slate-400">
                {{ __('Espace élève') }} · {{ $user->center?->name ?? '' }}
            </p>
        </div>
        <div class="flex shrink-0 items-center gap-2 rounded-xl border border-slate-200 bg-white px-3.5 py-2 text-sm font-semibold text-slate-700 shadow-2xs dark:border-slate-700 dark:bg-slate-800 dark:text-slate-200">
            <x-icon name="calendar" class="h-4 w-4 text-brand-600 dark:text-brand-400" />
            {{ now()->translatedFormat('d F Y') }}
        </div>
    </div>

    {{-- ── KPI ────────────────────────────────────────────────────────── --}}
    <div class="mb-6 grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
        <x-ui.stat :label="__('Présences')" :value="format_number($presentCount)" icon="check" tone="success" :hint="__('ce mois')" />
        <x-ui.stat :label="__('Absences')" :value="format_number($absentCount)" icon="x" tone="danger" :hint="__('ce mois')" />
        <x-ui.stat :label="__('Retards')" :value="format_number($lateCount)" icon="clock" tone="warning" :hint="__('ce mois')" />
        <x-ui.stat :label="__('Moyenne')" :value="$avg !== null ? format_number($avg, 2) : '—'" icon="chart" tone="primary" :hint="__('notes')" />
    </div>

    {{-- ── GRAPHIQUE + MA FORMATION ───────────────────────────────────── --}}
    <div class="mb-6 grid gap-6 xl:grid-cols-3">
        <div class="ec-panel">
            <div class="ec-panel__head">
                <div class="ec-panel__title">
                    <span class="ec-icon-chip bg-brand-50 text-brand-600 dark:bg-brand-500/15 dark:text-brand-300">
                        <x-icon name="check" class="h-5 w-5" />
                    </span>
                    {{ __('Assiduité') }}
                </div>
            </div>
            <div class="p-4 sm:p-5">
                <div class="mx-auto h-60 max-w-[16rem]"><canvas id="attendanceChart"></canvas></div>
            </div>
        </div>

        <div class="ec-panel xl:col-span-2">
            <div class="ec-panel__head">
                <div class="ec-panel__title">
                    <span class="ec-icon-chip bg-turquoise-50 text-turquoise-600 dark:bg-turquoise-500/15 dark:text-turquoise-400">
                        <x-icon name="book" class="h-5 w-5" />
                    </span>
                    {{ __('Ma formation') }}
                </div>
            </div>
            <div class="divide-y divide-slate-100 dark:divide-slate-800">
                @forelse($enrollments as $e)
                    <div class="flex items-center justify-between gap-3 px-5 py-3">
                        <div class="min-w-0">
                            <p class="truncate text-sm font-semibold text-slate-800 dark:text-white">{{ $e->formation?->name ?? '—' }}</p>
                            <p class="truncate text-xs text-slate-500">{{ __('Classe :') }} {{ $e->classRoom?->name ?? __('Non affectée') }}</p>
                        </div>
                        <x-status-badge type="enrollment" :status="$e->status" />
                    </div>
                @empty
                    <p class="px-5 py-8 text-center text-sm text-slate-500">{{ __('Aucune inscription.') }}</p>
                @endforelse
            </div>
        </div>
    </div>

    {{-- ── PAIEMENTS + NOTES + CERTIFICATS ────────────────────────────── --}}
    <div class="grid gap-6 xl:grid-cols-3">
        <div class="ec-panel">
            <div class="ec-panel__head">
                <div class="ec-panel__title">
                    <span class="ec-icon-chip bg-emerald-50 text-emerald-600 dark:bg-emerald-500/15 dark:text-emerald-300">
                        <x-icon name="banknotes" class="h-5 w-5" />
                    </span>
                    {{ __('Paiements récents') }}
                </div>
            </div>
            <div class="divide-y divide-slate-100 dark:divide-slate-800">
                @forelse($payments as $p)
                    <div class="flex items-center justify-between gap-3 px-5 py-3 text-sm">
                        <span class="text-slate-500">{{ format_date($p->payment_date, 'd/m/Y') }}</span>
                        <span class="font-semibold text-slate-800 dark:text-white">{{ format_price($p->amount) }}</span>
                    </div>
                @empty
                    <p class="px-5 py-8 text-center text-sm text-slate-500">{{ __('Aucun paiement.') }}</p>
                @endforelse
            </div>
        </div>

        <div class="ec-panel">
            <div class="ec-panel__head">
                <div class="ec-panel__title">
                    <span class="ec-icon-chip bg-blue-50 text-blue-600 dark:bg-blue-500/15 dark:text-blue-300">
                        <x-icon name="chart" class="h-5 w-5" />
                    </span>
                    {{ __('Mes notes') }}
                </div>
            </div>
            <div class="divide-y divide-slate-100 dark:divide-slate-800">
                @forelse($grades->take(6) as $g)
                    <div class="flex items-center justify-between gap-3 px-5 py-3 text-sm">
                        <span class="min-w-0 truncate text-slate-700 dark:text-slate-200">{{ $g->evaluation?->title ?? '—' }}</span>
                        <span class="font-semibold text-slate-800 dark:text-white">{{ $g->grade !== null ? format_number($g->grade, 2) : '—' }}</span>
                    </div>
                @empty
                    <p class="px-5 py-8 text-center text-sm text-slate-500">{{ __('Aucune note.') }}</p>
                @endforelse
            </div>
        </div>

        <div class="ec-panel">
            <div class="ec-panel__head">
                <div class="ec-panel__title">
                    <span class="ec-icon-chip bg-amber-50 text-amber-600 dark:bg-amber-500/15 dark:text-amber-300">
                        <x-icon name="document" class="h-5 w-5" />
                    </span>
                    {{ __('Certificats') }}
                </div>
            </div>
            <div class="divide-y divide-slate-100 dark:divide-slate-800">
                @forelse($certificates as $c)
                    <div class="flex items-center justify-between gap-3 px-5 py-3 text-sm">
                        <span class="min-w-0 truncate text-slate-700 dark:text-slate-200">{{ $c->certificate_number }}</span>
                        <span class="text-xs text-slate-500">{{ $c->type_label }}</span>
                    </div>
                @empty
                    <p class="px-5 py-8 text-center text-sm text-slate-500">{{ __('Aucun certificat.') }}</p>
                @endforelse
            </div>
        </div>
    </div>

@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', () => {
    if (typeof Chart === 'undefined') return;
    const el = document.getElementById('attendanceChart');
    if (!el) return;
    const isDark = document.documentElement.classList.contains('dark');
    const tickColor = isDark ? '#94a3b8' : '#64748b';
    new Chart(el, {
        type: 'doughnut',
        data: {
            labels: @js([__('Présents'), __('Absents'), __('Retards')]),
            datasets: [{
                data: @js([(int) $presentCount, (int) $absentCount, (int) $lateCount]),
                backgroundColor: ['#0dad98', '#ef4444', '#f59e0b'],
                borderWidth: 0,
                hoverOffset: 6,
            }],
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            cutout: '62%',
            plugins: { legend: { position: 'bottom', labels: { color: tickColor, usePointStyle: true, padding: 14, boxWidth: 8 } } },
        },
    });
});
</script>
@endpush
