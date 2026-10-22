@extends('layouts.app')
@section('title', __('Centres'))

@section('content')
<x-ui.page-header :title="__('Centres')" :description="__('Gestion des centres de formation.')">
    @can('create', \App\Models\Center::class)
        <a href="{{ route('centers.create') }}" class="ec-btn-primary"><x-icon name="plus" class="h-4 w-4" /> {{ __('Ajouter') }}</a>
    @endcan
</x-ui.page-header>

<div class="ec-table-wrap">
    <table class="ec-table">
        <thead>
            <tr>
                <th>{{ __('Centre') }}</th>
                <th>{{ __('Code') }}</th>
                <th>{{ __('Ville') }}</th>
                <th>{{ __('Statut') }}</th>
                <th></th>
            </tr>
        </thead>
        <tbody>
            @forelse($centers as $center)
                <tr>
                    <td class="font-medium">
                        <a href="{{ route('centers.show', $center) }}" class="text-brand-700 hover:underline">{{ $center->translated('name') }}</a>
                    </td>
                    <td class="font-mono text-xs">{{ $center->code }}</td>
                    <td>{{ $center->city }}</td>
                    <td><x-status-badge :status="$center->status" /></td>
                    <td class="text-right">
                        <a href="{{ route('centers.show', $center) }}" class="ec-btn-ghost ec-btn-sm">{{ __('Voir') }}</a>
                    </td>
                </tr>
            @empty
                <tr><td colspan="5"><x-ui.empty :title="__('Aucun centre')" /></td></tr>
            @endforelse
        </tbody>
    </table>
</div>
@if(method_exists($centers, 'hasPages') && $centers->hasPages())
    <div class="mt-4">{{ $centers->links() }}</div>
@endif
@endsection
