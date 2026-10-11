@extends('layouts.app')
@section('title', 'Notifications')

@section('content')
<x-ui.page-header title="Notifications" description="Messages destinés à votre compte.">
    <form method="POST" action="{{ route('notifications.read-all') }}">
        @csrf
        @method('PATCH')
        <button class="ec-btn-secondary">Tout marquer comme lu</button>
    </form>
</x-ui.page-header>
<div class="space-y-3">
    @forelse($notifications as $n)
        <article class="ec-card flex items-start justify-between gap-4 p-4 {{ $n->isRead() ? '' : 'border-brand-200 bg-brand-50/40' }}">
            <div>
                <p class="font-medium">{{ $n->title }}</p>
                <p class="mt-1 text-sm text-slate-600">{{ $n->message }}</p>
                <p class="mt-2 text-xs text-slate-400">{{ $n->created_at?->format('d/m/Y H:i') }} · {{ $n->isRead() ? 'Lue' : 'Non lue' }}</p>
            </div>
            <div class="flex gap-2">
                @unless($n->isRead())
                    <form method="POST" action="{{ route('notifications.read', $n) }}">
                        @csrf @method('PATCH')
                        <button class="ec-btn-ghost ec-btn-sm">Marquer comme lu</button>
                    </form>
                @endunless
                <form method="POST" action="{{ route('notifications.destroy', $n) }}">
                    @csrf @method('DELETE')
                    <button class="ec-btn-danger ec-btn-sm">Supprimer</button>
                </form>
            </div>
        </article>
    @empty
        <x-ui.empty title="Aucune notification" />
    @endforelse
</div>
@if($notifications->hasPages())<div class="mt-4">{{ $notifications->links() }}</div>@endif
@endsection
