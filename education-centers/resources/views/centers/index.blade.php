@extends('layouts.app')
@section('title', 'Centres')

@section('content')
<x-ui.page-header title="Centres" description="Gestion des centres de formation.">
    @can('create', \App\Models\Center::class)
        <a href="{{ route('centers.create') }}" class="ec-btn-primary"><x-icon name="plus" class="h-4 w-4" /> Ajouter</a>
    @endcan
</x-ui.page-header>

<div class="ec-table-wrap">
    <table class="ec-table">
        <thead>
            <tr>
                <th>Centre</th>
                <th>Code</th>
                <th>Ville</th>
                <th>Statut</th>
                <th></th>
            </tr>
        </thead>
        <tbody>
            @forelse($centers as $center)
                <tr>
                    <td class="font-medium">
                        <a href="{{ route('centers.show', $center) }}" class="text-brand-700 hover:underline">{{ $center->name }}</a>
                    </td>
                    <td class="font-mono text-xs">{{ $center->code }}</td>
                    <td>{{ $center->city }}</td>
                    <td><x-status-badge :status="$center->status" /></td>
                    <td class="text-right">
                        <a href="{{ route('centers.show', $center) }}" class="ec-btn-ghost ec-btn-sm">Voir</a>
                    </td>
                </tr>
            @empty
                <tr><td colspan="5"><x-ui.empty title="Aucun centre" /></td></tr>
            @endforelse
        </tbody>
    </table>
</div>
@if(method_exists($centers, 'hasPages') && $centers->hasPages())
    <div class="mt-4">{{ $centers->links() }}</div>
@endif
@endsection
