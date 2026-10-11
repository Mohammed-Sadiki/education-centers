<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>@yield('title', config('app.name', 'Education Centers'))</title>
    <meta name="description" content="@yield('description', 'Plateforme de gestion des centres d\'éducation en Allemagne')">

    {{-- Bootstrap 5 CSS --}}
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css"
          rel="stylesheet"
          integrity="sha384-QWTKZyjpPEjISv5WaRU9OFeRpok6YctnYmDr5pNlyT2bRjXh0JMhjY6hW+ALEwIH"
          crossorigin="anonymous">

    {{-- Bootstrap Icons --}}
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">

    <style>
        body { display: flex; flex-direction: column; min-height: 100vh; background-color: #f8f9fa; }
        main { flex: 1; }
        .navbar-brand { font-weight: 700; letter-spacing: .5px; }
        .sidebar-link.active, .sidebar-link:hover { background: rgba(255,255,255,.15); border-radius: .375rem; }
        .badge-status-active   { background-color: #198754; }
        .badge-status-inactive { background-color: #6c757d; }
        .table th { font-size: .82rem; text-transform: uppercase; letter-spacing: .05em; color: #6c757d; }
        .avatar-center { width: 48px; height: 48px; object-fit: cover; border-radius: .5rem; }
        .card-hover { transition: transform .15s, box-shadow .15s; }
        .card-hover:hover { transform: translateY(-2px); box-shadow: 0 .5rem 1.5rem rgba(0,0,0,.1)!important; }
    </style>

    {{-- Styles supplémentaires des vues enfants --}}
    @stack('styles')
</head>
<body>

{{-- ───── Navbar ───── --}}
<nav class="navbar navbar-expand-lg navbar-dark bg-primary shadow-sm">
    <div class="container-fluid px-4">
        <a class="navbar-brand d-flex align-items-center gap-2" href="{{ route('home') }}">
            <i class="bi bi-building-fill-check fs-5"></i>
            <span>{{ config('app.name', 'Education Centers') }}</span>
        </a>

        <button class="navbar-toggler border-0" type="button"
                data-bs-toggle="collapse" data-bs-target="#navbarMain"
                aria-controls="navbarMain" aria-expanded="false">
            <span class="navbar-toggler-icon"></span>
        </button>

        <div class="collapse navbar-collapse" id="navbarMain">
            <ul class="navbar-nav me-auto mb-2 mb-lg-0">
                @auth
                    <li class="nav-item">
                        <a class="nav-link {{ request()->routeIs('dashboard') ? 'active fw-semibold' : '' }}"
                           href="{{ route('dashboard') }}">
                            <i class="bi bi-speedometer2 me-1"></i>Tableau de bord
                        </a>
                    </li>
                    @if(auth()->user()->isSuperAdmin())
                        <li class="nav-item">
                            <a class="nav-link {{ request()->routeIs('centers.*') ? 'active fw-semibold' : '' }}"
                               href="{{ route('centers.index') }}">
                                <i class="bi bi-buildings me-1"></i>Centres
                            </a>
                        </li>
                    @endif
                    @if(auth()->user()->isSuperAdmin() || auth()->user()->isCenterAdmin())
                        <li class="nav-item">
                            <a class="nav-link {{ request()->routeIs('users.*') ? 'active fw-semibold' : '' }}"
                               href="{{ route('users.index') }}">
                                <i class="bi bi-people me-1"></i>Utilisateurs
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link {{ request()->routeIs('students.*') ? 'active fw-semibold' : '' }}"
                               href="{{ route('students.index') }}">
                                <i class="bi bi-mortarboard me-1"></i>Élèves
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link {{ request()->routeIs('teachers.*') ? 'active fw-semibold' : '' }}"
                               href="{{ route('teachers.index') }}">
                                <i class="bi bi-person-workspace me-1"></i>Professeurs
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link {{ request()->routeIs('formations.*') ? 'active fw-semibold' : '' }}"
                               href="{{ route('formations.index') }}">
                                <i class="bi bi-journal-bookmark me-1"></i>Formations
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link {{ request()->routeIs('classes.*') ? 'active fw-semibold' : '' }}"
                               href="{{ route('classes.index') }}">
                                <i class="bi bi-diagram-3 me-1"></i>Classes
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link {{ request()->routeIs('rooms.*') ? 'active fw-semibold' : '' }}"
                               href="{{ route('rooms.index') }}">
                                <i class="bi bi-door-open me-1"></i>Salles
                            </a>
                        </li>
                    @endif
                @endauth
            </ul>


            <ul class="navbar-nav ms-auto align-items-center gap-2">
                @guest
                    <li class="nav-item">
                        <a class="btn btn-outline-light btn-sm px-3" href="{{ route('login') }}">
                            <i class="bi bi-box-arrow-in-right me-1"></i>Connexion
                        </a>
                    </li>
                @else
                    <li class="nav-item dropdown">
                        <a class="nav-link dropdown-toggle text-white d-flex align-items-center gap-2" href="#" role="button" data-bs-toggle="dropdown" aria-expanded="false">
                            <i class="bi bi-person-circle fs-5"></i>
                            <span class="fw-semibold">{{ auth()->user()->name }}</span>
                            <span class="badge bg-light text-primary text-uppercase" style="font-size: 0.7rem;">
                                {{ auth()->user()->role }}
                            </span>
                        </a>
                        <ul class="dropdown-menu dropdown-menu-end shadow border-0">
                            <li class="px-3 py-2 border-bottom">
                                <div class="fw-bold text-truncate" style="max-width: 200px;">{{ auth()->user()->name }}</div>
                                <div class="small text-muted text-truncate" style="max-width: 200px;">{{ auth()->user()->email }}</div>
                                <div class="mt-1 small">
                                    <span class="text-secondary">Centre :</span>
                                    <strong class="text-dark">
                                        {{ auth()->user()->isSuperAdmin() ? 'Tous les centres' : (auth()->user()->center?->name ?? 'N/A') }}
                                    </strong>
                                </div>
                            </li>
                            <li>
                                <a class="dropdown-item py-2" href="{{ route('dashboard') }}">
                                    <i class="bi bi-speedometer2 me-2 text-primary"></i>Tableau de bord
                                </a>
                            </li>
                            @if(auth()->user()->isSuperAdmin())
                                <li>
                                    <a class="dropdown-item py-2" href="{{ route('centers.index') }}">
                                        <i class="bi bi-buildings me-2 text-success"></i>Gestion des Centres
                                    </a>
                                </li>
                            @endif
                            <li><hr class="dropdown-divider"></li>
                            <li>
                                <form action="{{ route('logout') }}" method="POST" class="d-inline">
                                    @csrf
                                    <button type="submit" class="dropdown-item py-2 text-danger">
                                        <i class="bi bi-box-arrow-right me-2"></i>Déconnexion
                                    </button>
                                </form>
                            </li>
                        </ul>
                    </li>
                @endguest
            </ul>
        </div>
    </div>
</nav>

{{-- ───── Fil d'Ariane ───── --}}
@hasSection('breadcrumb')
<div class="bg-white border-bottom">
    <div class="container-fluid px-4 py-2">
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb mb-0 small">
                <li class="breadcrumb-item">
                    <a href="{{ route('home') }}" class="text-decoration-none">
                        <i class="bi bi-house-fill me-1"></i>Accueil
                    </a>
                </li>
                @yield('breadcrumb')
            </ol>
        </nav>
    </div>
</div>
@endif

{{-- ───── Messages Flash ───── --}}
@if(session('success'))
    <div class="alert alert-success alert-dismissible fade show m-3 mb-0 d-flex align-items-center gap-2 shadow-sm" role="alert">
        <i class="bi bi-check-circle-fill flex-shrink-0"></i>
        <div>{{ session('success') }}</div>
        <button type="button" class="btn-close ms-auto" data-bs-dismiss="alert" aria-label="Fermer"></button>
    </div>
@endif

@if(session('error'))
    <div class="alert alert-danger alert-dismissible fade show m-3 mb-0 d-flex align-items-center gap-2 shadow-sm" role="alert">
        <i class="bi bi-exclamation-triangle-fill flex-shrink-0"></i>
        <div>{{ session('error') }}</div>
        <button type="button" class="btn-close ms-auto" data-bs-dismiss="alert" aria-label="Fermer"></button>
    </div>
@endif

@if(session('warning'))
    <div class="alert alert-warning alert-dismissible fade show m-3 mb-0 d-flex align-items-center gap-2 shadow-sm" role="alert">
        <i class="bi bi-exclamation-circle-fill flex-shrink-0"></i>
        <div>{{ session('warning') }}</div>
        <button type="button" class="btn-close ms-auto" data-bs-dismiss="alert" aria-label="Fermer"></button>
    </div>
@endif

{{-- ───── Contenu Principal ───── --}}
<main class="py-4">
    @yield('content')
</main>

{{-- ───── Footer ───── --}}
<footer class="bg-white border-top py-3 mt-auto">
    <div class="container-fluid px-4 d-flex justify-content-between align-items-center">
        <span class="text-muted small">
            &copy; {{ date('Y') }} <strong>{{ config('app.name') }}</strong> &mdash; Allemagne
        </span>
        <span class="text-muted small">
            Laravel {{ app()->version() }} &bull; PHP {{ PHP_VERSION }}
        </span>
    </div>
</footer>

{{-- Bootstrap 5 JS --}}
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"
        integrity="sha384-YvpcrYf0tY3lHB60NNkmXc4s9bIOgUxi8T/jzmBPh77pU2t+bxdI/5bvEKjfyh+"
        crossorigin="anonymous"></script>

{{-- Scripts supplémentaires des vues enfants --}}
@stack('scripts')
</body>
</html>
