@extends('layouts.app')
@section('title', $announcement->title)

@section('content')
<x-ui.page-header title="{{ $announcement->title }}">
    @can('update', $announcement)
        <a href="{{ route('announcements.edit', $announcement) }}" class="ec-btn-primary">Modifier</a>
    @endcan
</x-ui.page-header>
<x-ui.card>
    <div class="mb-4 flex gap-2">
        <x-status-badge type="announcement" :status="$announcement->status" />
        @if($announcement->is_public)<x-ui.badge tone="info">Public</x-ui.badge>@endif
    </div>
    <p class="text-sm text-slate-500">{{ $announcement->author?->name }} · {{ $announcement->created_at?->format('d/m/Y H:i') }}</p>
    <div class="mt-4 whitespace-pre-line text-sm">{{ $announcement->content }}</div>
</x-ui.card>
@endsection
