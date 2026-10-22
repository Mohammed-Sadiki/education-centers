@extends('layouts.app')
@section('title', __('Nouvelle annonce'))

@section('content')
<x-ui.page-header :title="__('Nouvelle annonce')" />
<form method="POST" action="{{ route('announcements.store') }}" class="ec-card max-w-2xl space-y-4 p-5">
    @csrf
    @if(auth()->user()->isSuperAdmin())
        <div>
            <label class="ec-label">{{ __('Centre (vide = global)') }}</label>
            <select name="center_id" class="ec-select">
                <option value="">{{ __('Tous les centres') }}</option>
                @foreach($centers as $c)
                    <option value="{{ $c->id }}">{{ $c->translated('name') }}</option>
                @endforeach
            </select>
        </div>
    @endif
    <div>
        <label class="ec-label">{{ __('Titre') }}</label>
        <input name="title" class="ec-input" required>
    </div>
    <div>
        <label class="ec-label">{{ __('Contenu') }}</label>
        <textarea name="content" class="ec-textarea" rows="8" required></textarea>
    </div>
    <input type="hidden" name="is_public" value="0">
    <label class="flex items-center gap-2 text-sm"><input type="checkbox" name="is_public" value="1"> {{ __('Visible sur le site public') }}</label>
    <select name="status" class="ec-select">
        <option value="draft">{{ __('Brouillon') }}</option>
        <option value="published">{{ __('Publiée') }}</option>
        <option value="archived">{{ __('Archivée') }}</option>
    </select>
    <button class="ec-btn-primary">{{ __('Créer') }}</button>
</form>
@endsection
