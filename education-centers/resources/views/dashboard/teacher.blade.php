@extends('layouts.app')

@section('title', __('Tableau de bord') . ' — ' . config('app.name'))

@php
    $user = auth()->user();
@endphp

@section('content')

    {{-- ── EN-TÊTE ────────────────────────────────────────────────────── --}}
    <div class="mb-8 flex flex-col gap-4 sm:flex-row sm:items-start sm:justify-between">
        <div>
            <h1 class="text-[1.9rem] font-extrabold tracking-tight text-slate-900 sm:text-[2.15rem] dark:text-white">
                {{ __('Bonjour,') }} {{ $user->name }} 👋
            </h1>
            <p class="mt-1.5 text-base text-slate-500 dark:text-slate-400">
                {{ __('Espace professeur') }} · {{ $user->center?->name ?? '' }}
            </p>
        </div>
        <div class="flex shrink-0 items-center gap-2 rounded-xl border border-slate-200 bg-white px-3.5 py-2 text-sm font-semibold text-slate-700 shadow-2xs dark:border-slate-700 dark:bg-slate-800 dark:text-slate-200">
            <x-icon name="calendar" class="h-4 w-4 text-brand-600 dark:text-brand-400" />
            {{ now()->translatedFormat('d F Y') }}
        </div>
    </div>

    {{-- ── KPI ────────────────────────────────────────────────────────── --}}
    <div class="mb-6 grid gap-4 sm:grid-cols-3">
        <x-ui.stat :label="__('Mes classes')" :value="format_number($classes->count())" icon="classes" tone="primary" :hint="__('assignées')" />
        <x-ui.stat :label="__('Séances à venir')" :value="format_number($upcoming->count())" icon="calendar" tone="info" :hint="__('planifiées')" />
        <x-ui.stat :label="__('Notifications')" :value="format_number($notifications->count())" icon="bell" tone="warning" :hint="__('non lues')" />
    </div>

    {{-- ── CONTENU ────────────────────────────────────────────────────── --}}
    <div class="grid gap-6 xl:grid-cols-3">
        {{-- Mes classes --}}
        <div class="ec-panel">
            <div class="ec-panel__head">
                <div class="ec-panel__title">
                    <span class="ec-icon-chip bg-brand-50 text-brand-600 dark:bg-brand-500/15 dark:text-brand-300">
                        <x-icon name="classes" class="h-5 w-5" />
                    </span>
                    {{ __('Mes classes') }}
                </div>
            </div>
            <div class="divide-y divide-slate-100 dark:divide-slate-800">
                @forelse($classes as $class)
                    <div class="flex items-center gap-3 px-5 py-3">
                        <span class="ec-icon-chip bg-slate-100 text-slate-500 dark:bg-slate-800 dark:text-slate-300">
                            <x-icon name="book" class="h-4 w-4" />
                        </span>
                        <div class="min-w-0">
                            <p class="truncate text-sm font-semibold text-slate-800 dark:text-white">{{ $class->name }}</p>
                            <p class="truncate text-xs text-slate-500">{{ $class->formation?->name ?? '—' }}</p>
                        </div>
                    </div>
                @empty
                    <p class="px-5 py-8 text-center text-sm text-slate-500">{{ __('Aucune classe affectée.') }}</p>
                @endforelse
            </div>
        </div>

        {{-- Prochaines séances --}}
        <div class="ec-panel xl:col-span-2">
            <div class="ec-panel__head">
                <div class="ec-panel__title">
                    <span class="ec-icon-chip bg-emerald-50 text-emerald-600 dark:bg-emerald-500/15 dark:text-emerald-300">
                        <x-icon name="calendar" class="h-5 w-5" />
                    </span>
                    {{ __('Prochaines séances') }}
                </div>
                <div class="flex gap-2">
                    <a href="{{ route('attendances.index') }}" class="ec-btn-secondary ec-btn-sm">{{ __('Présences') }}</a>
                    <a href="{{ route('evaluations.index') }}" class="ec-btn-secondary ec-btn-sm">{{ __('Évaluations') }}</a>
                </div>
            </div>
            <div class="divide-y divide-slate-100 dark:divide-slate-800">
                @forelse($upcoming as $s)
                    <div class="flex items-center gap-3 px-5 py-3">
                        <div class="flex w-16 shrink-0 flex-col items-center rounded-lg bg-brand-50 py-1.5 text-center dark:bg-brand-500/15">
                            <span class="text-xs font-bold text-brand-700 dark:text-brand-300">{{ substr($s->start_time, 0, 5) }}</span>
                            <span class="text-[10px] text-slate-400">{{ substr($s->end_time, 0, 5) }}</span>
                        </div>
                        <div class="min-w-0 flex-1">
                            <p class="truncate text-sm font-semibold text-slate-800 dark:text-white">{{ $s->classRoom?->name ?? '—' }}</p>
                            <p class="truncate text-xs text-slate-500">{{ $s->room?->code ?? '—' }}</p>
                        </div>
                        <span class="shrink-0 text-xs font-medium text-slate-400">{{ format_date($s->date, 'd/m/Y') }}</span>
                    </div>
                @empty
                    <p class="px-5 py-8 text-center text-sm text-slate-500">{{ __('Aucune séance planifiée.') }}</p>
                @endforelse
            </div>
        </div>
    </div>

@endsection
