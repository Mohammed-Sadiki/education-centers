@extends('layouts.app')

@section('title', __('Tableau de bord') . ' — ' . config('app.name'))

@section('content')
    <x-ui.page-header title="Bienvenue, {{ auth()->user()->name }}" description="Espace professeur — {{ auth()->user()->center?->name }}" />

    <div class="mb-8 grid gap-4 sm:grid-cols-3">
        <x-ui.stat :label="__('Mes classes')" :value="$classes->count()" icon="classes" />
        <x-ui.stat :label="__('Séances à venir')" :value="$upcoming->count()" icon="calendar" tone="info" />
        <x-ui.stat :label="__('Notifications')" :value="$notifications->count()" icon="bell" tone="warning" />
    </div>

    <div class="grid gap-6 lg:grid-cols-2">
        <x-ui.card>
            <h2 class="mb-4 font-semibold">{{ __('Mes classes') }}</h2>
            <ul class="space-y-2 text-sm">
                @forelse($classes as $class)
                    <li class="rounded-lg bg-slate-50 px-3 py-2 dark:bg-slate-800">{{ $class->name }} · {{ $class->formation?->name }}</li>
                @empty
                    <li class="text-slate-500">{{ __('Aucune classe affectée.') }}</li>
                @endforelse
            </ul>
        </x-ui.card>
        <x-ui.card>
            <h2 class="mb-4 font-semibold">{{ __('Prochaines séances') }}</h2>
            <ul class="space-y-3">
                @forelse($upcoming as $s)
                    <li class="rounded-lg border border-slate-100 p-3 text-sm dark:border-slate-800">
                        <p class="font-medium">{{ format_date($s->date, 'd/m/Y') }} · {{ $s->start_time }} — {{ $s->end_time }}</p>
                        <p class="text-slate-500">{{ $s->classRoom?->name }} · {{ $s->room?->code }}</p>
                    </li>
                @empty
                    <li class="text-sm text-slate-500">{{ __('Aucune séance planifiée.') }}</li>
                @endforelse
            </ul>
            <div class="mt-4 flex gap-2">
                <a href="{{ route('attendances.index') }}" class="ec-btn-secondary ec-btn-sm">{{ __('Présences') }}</a>
                <a href="{{ route('evaluations.index') }}" class="ec-btn-secondary ec-btn-sm">{{ __('Évaluations') }}</a>
            </div>
        </x-ui.card>
    </div>
@endsection
