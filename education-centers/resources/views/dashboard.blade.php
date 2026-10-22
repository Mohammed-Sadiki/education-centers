@extends('layouts.app')

@section('title', __('Tableau de bord') . ' — ' . config('app.name'))

@section('content')
    <x-ui.page-header :title="__('Bienvenue,') . ' ' . $user->name" :description="__('Tableau de bord de votre espace.')" />

    <div class="grid gap-4 md:grid-cols-2">
        <x-ui.card>
            <h2 class="mb-4 text-sm font-semibold uppercase tracking-wide text-slate-500">{{ __('Profil Utilisateur') }}</h2>
            <dl class="space-y-3 text-sm">
                <div class="flex justify-between gap-4"><dt class="text-slate-500">{{ __('Nom complet') }}</dt><dd class="font-medium">{{ $user->name }}</dd></div>
                <div class="flex justify-between gap-4"><dt class="text-slate-500">{{ __('Adresse e-mail') }}</dt><dd class="font-medium">{{ $user->email }}</dd></div>
                <div class="flex justify-between gap-4"><dt class="text-slate-500">{{ __('Rôle attribué') }}</dt><dd><x-role-label :role="$user->role" /></dd></div>
                <div class="flex justify-between gap-4">
                    <dt class="text-slate-500">{{ __('Centre associé') }}</dt>
                    <dd class="font-medium">{{ $user->isSuperAdmin() ? __('Tous les centres') : ($user->center?->name ?? __('Aucun centre rattaché')) }}</dd>
                </div>
            </dl>
        </x-ui.card>
        <x-ui.card>
            <h2 class="mb-4 text-sm font-semibold uppercase tracking-wide text-slate-500">{{ __('Périmètre d\'accès') }}</h2>
            <p class="text-sm text-slate-600 dark:text-slate-300">
                @if($user->isSuperAdmin())
                    {{ __('Accès global à l\'ensemble des centres.') }}
                @else
                    {{ __('Accès restreint au centre') }} {{ $user->center?->name }}.
                @endif
            </p>
        </x-ui.card>
    </div>
@endsection
