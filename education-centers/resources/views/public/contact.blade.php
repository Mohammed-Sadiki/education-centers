@extends('layouts.public')
@section('title', __('Contact'))

@section('content')
<div class="mx-auto max-w-xl px-4 py-12">
    <h1 class="text-3xl font-semibold">{{ __('Contact') }}</h1>
    <p class="mt-2 text-slate-500">{{ __('Écrivez-nous, nous vous répondrons dans les meilleurs délais.') }}</p>
    <form method="POST" action="{{ route('public.contact.store') }}" class="ec-card mt-8 space-y-4 p-6">
        @csrf
        <div>
            <label class="ec-label" for="name">{{ __('Nom') }} <span class="text-red-600">*</span></label>
            <input class="ec-input @error('name') is-invalid @enderror" id="name" name="name" value="{{ old('name') }}" required>
            @error('name')<p class="ec-error">{{ $message }}</p>@enderror
        </div>
        <div>
            <label class="ec-label" for="email">{{ __('Email') }} <span class="text-red-600">*</span></label>
            <input type="email" class="ec-input @error('email') is-invalid @enderror" id="email" name="email" value="{{ old('email') }}" required>
            @error('email')<p class="ec-error">{{ $message }}</p>@enderror
        </div>
        <div>
            <label class="ec-label" for="phone">{{ __('Téléphone') }}</label>
            <input class="ec-input" id="phone" name="phone" value="{{ old('phone') }}">
        </div>
        <div>
            <label class="ec-label" for="center_id">{{ __('Centre (optionnel)') }}</label>
            <select class="ec-select" id="center_id" name="center_id">
                <option value="">—</option>
                @foreach($centers as $center)
                    <option value="{{ $center->id }}" @selected(old('center_id') == $center->id)>{{ $center->translated('name') }}</option>
                @endforeach
            </select>
        </div>
        <div>
            <label class="ec-label" for="subject">{{ __('Sujet') }} <span class="text-red-600">*</span></label>
            <input class="ec-input @error('subject') is-invalid @enderror" id="subject" name="subject" value="{{ old('subject') }}" required>
            @error('subject')<p class="ec-error">{{ $message }}</p>@enderror
        </div>
        <div>
            <label class="ec-label" for="message">{{ __('Message') }} <span class="text-red-600">*</span></label>
            <textarea class="ec-textarea @error('message') is-invalid @enderror" id="message" name="message" rows="5" required>{{ old('message') }}</textarea>
            @error('message')<p class="ec-error">{{ $message }}</p>@enderror
        </div>
        <button class="ec-btn-primary w-full" type="submit">{{ __('Envoyer') }}</button>
    </form>
</div>
@endsection
