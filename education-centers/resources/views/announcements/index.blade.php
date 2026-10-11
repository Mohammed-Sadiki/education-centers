@extends('layouts.app')
@section('title', 'Annonces')

@section('content')
<x-ui.page-header title="Annonces" description="Communications internes et publiques.">
    @can('create', \App\Models\Announcement::class)
        <a href="{{ route('announcements.create') }}" class="ec-btn-primary"><x-icon name="plus" class="h-4 w-4" /> Créer</a>
    @endcan
</x-ui.page-header>
<x-ui.card class="mb-4">
    <form method="GET" class="flex max-w-sm gap-2">
        <select name="status" class="ec-select">
            <option value="">Tous</option>
            <option value="draft" @selected(request('status')==='draft')>Brouillon</option>
            <option value="published" @selected(request('status')==='published')>Publiée</option>
            <option value="archived" @selected(request('status')==='archived')>Archivée</option>
        </select>
        <button class="ec-btn-secondary">Filtrer</button>
    </form>
</x-ui.card>
<div class="ec-table-wrap">
    <table class="ec-table">
        <thead><tr><th>Titre</th><th>Auteur</th><th>Date</th><th>Statut</th><th></th></tr></thead>
        <tbody>
            @forelse($announcements as $a)
                <tr>
                    <td class="font-medium">{{ $a->title }}</td>
                    <td>{{ $a->author?->name }}</td>
                    <td>{{ $a->created_at?->format('d/m/Y') }}</td>
                    <td><x-status-badge type="announcement" :status="$a->status" /></td>
                    <td class="text-right"><a href="{{ route('announcements.show', $a) }}" class="ec-btn-ghost ec-btn-sm">Voir</a></td>
                </tr>
            @empty
                <tr><td colspan="5"><x-ui.empty title="Aucune annonce" /></td></tr>
            @endforelse
        </tbody>
    </table>
</div>
@if($announcements->hasPages())<div class="mt-4">{{ $announcements->links() }}</div>@endif
@endsection
