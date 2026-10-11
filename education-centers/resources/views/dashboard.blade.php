@extends('layouts.app')

@section('title', 'Tableau de bord — ' . config('app.name'))

@section('content')
    <x-ui.page-header title="Bienvenue, {{ $user->name }}" description="Tableau de bord de votre espace." />

    <div class="grid gap-4 md:grid-cols-2">
        <x-ui.card>
            <h2 class="mb-4 text-sm font-semibold uppercase tracking-wide text-slate-500">Profil Utilisateur</h2>
            <dl class="space-y-3 text-sm">
                <div class="flex justify-between gap-4"><dt class="text-slate-500">Nom complet</dt><dd class="font-medium">{{ $user->name }}</dd></div>
                <div class="flex justify-between gap-4"><dt class="text-slate-500">Adresse e-mail</dt><dd class="font-medium">{{ $user->email }}</dd></div>
                <div class="flex justify-between gap-4"><dt class="text-slate-500">Rôle attribué</dt><dd><x-role-label :role="$user->role" /></dd></div>
                <div class="flex justify-between gap-4">
                    <dt class="text-slate-500">Centre associé</dt>
                    <dd class="font-medium">{{ $user->isSuperAdmin() ? 'Tous les centres' : ($user->center?->name ?? 'Aucun centre rattaché') }}</dd>
                </div>
            </dl>
        </x-ui.card>
        <x-ui.card>
            <h2 class="mb-4 text-sm font-semibold uppercase tracking-wide text-slate-500">Périmètre d'accès</h2>
            <p class="text-sm text-slate-600 dark:text-slate-300">
                @if($user->isSuperAdmin())
                    Accès global à l'ensemble des centres.
                @else
                    Accès restreint au centre {{ $user->center?->name }}.
                @endif
            </p>
        </x-ui.card>
    </div>
@endsection
