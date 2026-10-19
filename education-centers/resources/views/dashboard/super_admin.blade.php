@extends('layouts.app')

@section('title', 'Tableau de bord — Super Admin')

@section('content')
    <x-ui.page-header title="Tableau de bord" description="Vue d’ensemble de tous les centres — Super Admin." />

    <div class="mb-8 grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
        <x-ui.stat label="Centres" :value="$stats['centers']" icon="building" />
        <x-ui.stat label="Étudiants" :value="$stats['students']" icon="academic" tone="info" />
        <x-ui.stat label="Professeurs" :value="$stats['teachers']" icon="user" tone="success" />
        <x-ui.stat label="Formations" :value="$stats['formations']" icon="book" />
        <x-ui.stat label="Classes" :value="$stats['classes']" icon="classes" tone="info" />
        <x-ui.stat label="Inscriptions" :value="$stats['enrollments']" icon="clipboard" />
        <x-ui.stat label="En attente" :value="$stats['pending_enrollments']" icon="clock" tone="warning" />
        <x-ui.stat label="Revenus" :value="format_price($stats['payments_total'])" icon="banknotes" tone="success" />
    </div>

    <div class="grid gap-6 xl:grid-cols-2">
        <x-ui.card :padding="false">
            <div class="border-b border-slate-100 px-5 py-4 dark:border-slate-800">
                <h2 class="font-semibold">Dernières inscriptions</h2>
            </div>
            <div class="ec-table-wrap border-0">
                <table class="ec-table">
                    <thead>
                        <tr>
                            <th>Étudiant</th>
                            <th>Formation</th>
                            <th>Centre</th>
                            <th>Statut</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($recentEnrollments as $e)
                            <tr>
                                <td>{{ $e->student?->full_name ?? '—' }}</td>
                                <td>{{ $e->formation?->name ?? '—' }}</td>
                                <td>{{ $e->center?->name ?? '—' }}</td>
                                <td><x-status-badge type="enrollment" :status="$e->status" /></td>
                            </tr>
                        @empty
                            <tr><td colspan="4" class="py-8 text-center text-slate-500">Aucune inscription récente.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </x-ui.card>

        <x-ui.card :padding="false">
            <div class="border-b border-slate-100 px-5 py-4 dark:border-slate-800">
                <h2 class="font-semibold">Activité par centre</h2>
            </div>
            <div class="divide-y divide-slate-100 dark:divide-slate-800">
                @forelse($centers as $center)
                    <div class="flex items-center justify-between px-5 py-3 text-sm">
                        <div>
                            <p class="font-medium">{{ $center->name }}</p>
                            <p class="text-xs text-slate-500">{{ $center->students_count }} étudiants · {{ $center->formations_count }} formations</p>
                        </div>
                        <span class="text-slate-500">{{ $center->enrollments_count }} inscriptions</span>
                    </div>
                @empty
                    <p class="px-5 py-8 text-center text-sm text-slate-500">Aucun centre.</p>
                @endforelse
            </div>
        </x-ui.card>
    </div>
@endsection
