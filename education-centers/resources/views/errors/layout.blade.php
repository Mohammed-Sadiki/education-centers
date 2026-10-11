<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $title }} — {{ config('app.name') }}</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="min-h-screen bg-slate-50 text-slate-800 dark:bg-slate-950 dark:text-slate-100">
    <div class="mx-auto flex min-h-screen max-w-lg flex-col items-center justify-center px-4 text-center">
        <p class="text-sm font-semibold uppercase tracking-widest text-brand-700">{{ $code }}</p>
        <h1 class="mt-2 text-3xl font-semibold">{{ $title }}</h1>
        <p class="mt-3 text-slate-500">{{ $message }}</p>
        <a href="{{ url('/') }}" class="ec-btn-primary mt-8">Retour à l’accueil</a>
    </div>
</body>
</html>
