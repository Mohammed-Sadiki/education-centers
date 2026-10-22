@extends('layouts.guest')

@section('title', __('Connexion') . ' — ' . config('app.name'))

@section('content')
<div class="flex min-h-screen items-center justify-center px-4 py-12">
    <div class="w-full max-w-md">
        <div class="mb-8 text-center">
            <div class="mx-auto mb-4 flex h-12 w-12 items-center justify-center rounded-xl bg-brand-600 text-white">
                <x-icon name="academic" class="h-6 w-6" />
            </div>
            <p class="text-xs font-semibold uppercase tracking-[0.2em] text-brand-700">{{ __('Education Centers') }}</p>
            <h1 class="mt-2 text-2xl font-semibold text-slate-900 dark:text-white">{{ __('Connexion') }}</h1>
            <p class="mt-1 text-sm text-slate-500">{{ __('Bienvenue — accédez à votre espace sécurisé') }}</p>
        </div>

        <div class="ec-card p-6">
            @if ($errors->any())
                <div class="mb-4 rounded-lg border border-red-200 bg-red-50 px-3 py-2 text-sm text-red-700">
                    <ul class="list-disc ps-4">
                        @foreach ($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            <form method="POST" action="{{ route('login') }}" novalidate class="space-y-4">
                @csrf
                <div>
                    <label for="email" class="ec-label">{{ __('Adresse e-mail') }}</label>
                    <input type="email" id="email" name="email" value="{{ old('email') }}" required autocomplete="email" autofocus
                           placeholder="nom@education-centers.de"
                           class="ec-input @error('email') is-invalid @enderror">
                    @error('email')<p class="ec-error">{{ $message }}</p>@enderror
                </div>
                <div>
                    <label for="password" class="ec-label">{{ __('Mot de passe') }}</label>
                    <input type="password" id="password" name="password" required autocomplete="current-password"
                           placeholder="••••••••"
                           class="ec-input @error('password') is-invalid @enderror">
                    @error('password')<p class="ec-error">{{ $message }}</p>@enderror
                </div>
                <label class="flex items-center gap-2 text-sm text-slate-600">
                    <input type="checkbox" id="remember" name="remember" class="rounded border-slate-300" {{ old('remember') ? 'checked' : '' }}>
                    {{ __('Se souvenir de moi') }}
                </label>
                <button type="submit" class="ec-btn-primary w-full">{{ __('Se connecter') }}</button>
            </form>
        </div>
        <p class="mt-6 text-center text-sm text-slate-500">
            <a href="{{ route('home') }}" class="hover:text-brand-700">{{ __('Retour au site') }}</a>
        </p>
    </div>
</div>
@endsection
