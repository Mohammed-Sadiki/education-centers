@extends('layouts.app')
@section('title', __('Annonces'))

@section('content')
<x-ui.page-header :title="__('Annonces')" :description="__('Communications internes et publiques.')">
    @can('create', \App\Models\Announcement::class)
        <a href="{{ route('announcements.create') }}" class="ec-btn-primary"><x-icon name="plus" class="h-4 w-4" /> {{ __('Créer') }}</a>
    @endcan
</x-ui.page-header>
<x-ui.card class="mb-4">
    <form method="GET" class="flex max-w-sm gap-2">
        <select name="status" class="ec-select">
            <option value="">{{ __('Tous') }}</option>
            <option value="draft" @selected(request('status')==='draft')>{{ __('Brouillon') }}</option>
            <option value="published" @selected(request('status')==='published')>{{ __('Publiée') }}</option>
            <option value="archived" @selected(request('status')==='archived')>{{ __('Archivée') }}</option>
        </select>
        <button class="ec-btn-secondary">{{ __('Filtrer') }}</button>
    </form>
</x-ui.card>
<div class="ec-table-wrap">
    <table class="ec-table">
        <thead><tr><th>{{ __('Titre') }}</th><th>{{ __('Auteur') }}</th><th>{{ __('Date') }}</th><th>{{ __('Statut') }}</th><th></th></tr></thead>
        <tbody>
            @forelse($announcements as $a)
                <tr>
                    <td class="font-medium">{{ $a->title }}</td>
                    <td>{{ $a->author?->name }}</td>
                    <td>{{ format_date($a->created_at, 'd/m/Y') }}</td>
                    <td><x-status-badge type="announcement" :status="$a->status" /></td>
                    <td class="text-right"><a href="{{ route('announcements.show', $a) }}" class="ec-btn-ghost ec-btn-sm">{{ __('Voir') }}</a></td>
                </tr>
            @empty
                <tr><td colspan="5"><x-ui.empty :title="__('Aucune annonce')" /></td></tr>
            @endforelse
        </tbody>
    </table>
</div>
@if($announcements->hasPages())<div class="mt-4">{{ $announcements->links() }}</div>@endif
@endsection
