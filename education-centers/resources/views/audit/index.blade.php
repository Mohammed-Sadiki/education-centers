@extends('layouts.app')
@section('title', 'Audit Logs')

@section('content')
<x-ui.page-header title="Journaux d’audit" description="Traçabilité des actions sensibles." />
<x-ui.card class="mb-4">
    <form method="GET" class="grid gap-3 md:grid-cols-3">
        <select name="action" class="ec-select">
            <option value="">Toutes les actions</option>
            @foreach($actions as $action)
                <option value="{{ $action }}" @selected(request('action')===$action)>{{ $action }}</option>
            @endforeach
        </select>
        <input type="date" name="date" value="{{ request('date') }}" class="ec-input">
        <button class="ec-btn-secondary">Filtrer</button>
    </form>
</x-ui.card>
<div class="ec-table-wrap">
    <table class="ec-table">
        <thead><tr><th>Date</th><th>Utilisateur</th><th>Action</th><th>Ressource</th><th></th></tr></thead>
        <tbody>
            @forelse($logs as $log)
                <tr>
                    <td>{{ optional($log->created_at)->format('d/m/Y H:i') }}</td>
                    <td>{{ $log->user?->name ?? 'Système' }}</td>
                    <td class="font-mono text-xs">{{ $log->action }}</td>
                    <td>{{ class_basename((string) $log->auditable_type) }} #{{ $log->auditable_id }}</td>
                    <td class="text-right">
                        <div x-data="{ open: false }">
                            <button type="button" class="ec-btn-ghost ec-btn-sm" x-on:click="open = true">Détails</button>
                            <div x-show="open" x-cloak class="fixed inset-0 z-50 flex items-center justify-center bg-slate-900/50 p-4">
                                <div class="w-full max-w-lg rounded-xl bg-white p-5 dark:bg-slate-900">
                                    <h3 class="font-semibold">{{ $log->action }}</h3>
                                    <p class="mt-2 text-sm text-slate-500">{{ $log->description }}</p>
                                    <pre class="mt-3 max-h-64 overflow-auto rounded-lg bg-slate-50 p-3 text-xs dark:bg-slate-800">{{ json_encode(['old' => $log->old_values, 'new' => $log->new_values], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) }}</pre>
                                    <button type="button" class="ec-btn-secondary mt-4" x-on:click="open = false">Fermer</button>
                                </div>
                            </div>
                        </div>
                    </td>
                </tr>
            @empty
                <tr><td colspan="5"><x-ui.empty title="Aucun journal" /></td></tr>
            @endforelse
        </tbody>
    </table>
</div>
@if($logs->hasPages())<div class="mt-4">{{ $logs->links() }}</div>@endif
@endsection
