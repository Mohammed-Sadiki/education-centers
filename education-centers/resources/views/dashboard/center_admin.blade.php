@extends('layouts.app')

@section('title', 'Tableau de bord — Admin Centre')

@section('content')
    <x-ui.page-header title="Tableau de bord" :description="'Centre : '.(auth()->user()->center?->name ?? '').' — Admin Centre'" />

    <div class="mb-8 grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
        <x-ui.stat label="Étudiants" :value="$stats['students']" icon="academic" />
        <x-ui.stat label="Professeurs" :value="$stats['teachers']" icon="user" tone="success" />
        <x-ui.stat label="Formations" :value="$stats['formations']" icon="book" />
        <x-ui.stat label="Classes" :value="$stats['classes']" icon="classes" tone="info" />
        <x-ui.stat label="Inscriptions acceptées" :value="$stats['enrollments_accepted']" icon="clipboard" tone="success" />
        <x-ui.stat label="Revenus" :value="format_price($stats['payments_total'])" icon="banknotes" tone="success" />
        <x-ui.stat label="Factures en attente" :value="format_price($stats['invoices_pending'])" icon="document" tone="danger" />
    </div>

    <div class="grid gap-6 xl:grid-cols-2">
        <x-ui.card>
            <h2 class="mb-4 font-semibold">Prochaines séances</h2>
            <ul class="space-y-3">
                @forelse($upcoming as $s)
                    <li class="rounded-lg border border-slate-100 p-3 text-sm dark:border-slate-800">
                        <p class="font-medium">{{ $s->start_time }} — {{ $s->end_time }}</p>
                        <p class="text-slate-500">{{ $s->classRoom?->name }} · {{ $s->teacher?->user?->name ?? $s->teacher?->full_name }} · {{ $s->room?->code }}</p>
                    </li>
                @empty
                    <li class="text-sm text-slate-500">Aucune séance à venir.</li>
                @endforelse
            </ul>
        </x-ui.card>
        <x-ui.card>
            <h2 class="mb-4 font-semibold">Dernières inscriptions</h2>
            <ul class="space-y-3 text-sm">
                @forelse($recentEnrollments as $e)
                    <li class="flex items-center justify-between gap-3">
                        <span>{{ $e->student?->full_name }} · {{ $e->formation?->name }}</span>
                        <x-status-badge type="enrollment" :status="$e->status" />
                    </li>
                @empty
                    <li class="text-slate-500">Aucune inscription récente.</li>
                @endforelse
            </ul>
        </x-ui.card>
    </div>
@endsection
