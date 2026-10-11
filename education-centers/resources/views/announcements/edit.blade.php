@extends('layouts.app')
@section('title', 'Modifier l’annonce')

@section('content')
<x-ui.page-header title="Modifier l’annonce" />
<form method="POST" action="{{ route('announcements.update', $announcement) }}" class="ec-card max-w-2xl space-y-4 p-5">
    @csrf
    @method('PUT')
    <input name="title" class="ec-input" value="{{ $announcement->title }}" required>
    <textarea name="content" class="ec-textarea" rows="8" required>{{ $announcement->content }}</textarea>
    <input type="hidden" name="is_public" value="0">
    <label class="flex items-center gap-2 text-sm"><input type="checkbox" name="is_public" value="1" @checked($announcement->is_public)> Visible sur le site public</label>
    <select name="status" class="ec-select">
        @foreach(['draft'=>'Brouillon','published'=>'Publiée','archived'=>'Archivée'] as $k=>$v)
            <option value="{{ $k }}" @selected($announcement->status===$k)>{{ $v }}</option>
        @endforeach
    </select>
    <button class="ec-btn-primary">Enregistrer</button>
</form>
@endsection
