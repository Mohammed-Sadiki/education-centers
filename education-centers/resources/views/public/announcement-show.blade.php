@extends('layouts.public')
@section('title', $announcement->title)

@section('content')
<article class="mx-auto max-w-3xl px-4 py-12">
    <p class="text-sm text-slate-500"><a href="{{ route('public.announcements') }}" class="hover:text-brand-700">Actualités</a></p>
    <h1 class="mt-2 text-3xl font-semibold">{{ $announcement->title }}</h1>
    <p class="mt-2 text-sm text-slate-500">{{ optional($announcement->published_at)->format('d/m/Y') }}</p>
    <div class="mt-8 whitespace-pre-line text-slate-700 dark:text-slate-300">{{ $announcement->content }}</div>
</article>
@endsection
