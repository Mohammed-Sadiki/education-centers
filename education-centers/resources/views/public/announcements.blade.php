@extends('layouts.public')
@section('title', __('Actualités'))

@section('content')
<div class="mx-auto max-w-4xl px-4 py-12">
    <h1 class="text-3xl font-semibold">{{ __('Actualités') }}</h1>
    <div class="mt-8 space-y-4">
        @forelse($announcements as $a)
            <a href="{{ route('public.announcements.show', $a->slug) }}" class="ec-card block p-5 hover:border-brand-200">
                <p class="text-xs text-slate-500">{{ format_date($a->published_at, 'd/m/Y') }} @if($a->center) · {{ $a->center->name }} @endif</p>
                <h2 class="mt-1 font-semibold">{{ $a->title }}</h2>
                <p class="mt-2 line-clamp-3 text-sm text-slate-600">{{ \Illuminate\Support\Str::limit(strip_tags($a->content), 180) }}</p>
            </a>
        @empty
            <x-ui.empty :title="__('Aucune actualité')" />
        @endforelse
    </div>
    <div class="mt-8">{{ $announcements->links() }}</div>
</div>
@endsection
