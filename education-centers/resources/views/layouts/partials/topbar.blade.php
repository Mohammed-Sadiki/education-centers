@php
    $user = auth()->user();
    $unreadCount = \App\Models\AppNotification::where('user_id', $user->id)->whereNull('read_at')->count();
    $unreadList = \App\Models\AppNotification::where('user_id', $user->id)->whereNull('read_at')->latest()->take(6)->get();
    $roleLabel = match ($user->role) {
        'super_admin' => 'Super Admin',
        'center_admin' => 'Admin Centre',
        'teacher' => 'Professeur',
        'student' => 'Élève',
        default => $user->role,
    };
    $centerLabel = $user->isSuperAdmin() ? 'Tous les centres' : ($user->center?->name ?? 'N/A');
    $isStagiaires = request()->routeIs('stagiaires.*');
    $isFinance = request()->routeIs('super-admin.finance.*');

    // Détection du domaine actif (session ou route)
    $activeDomain = session('active_domain');
    if (!$activeDomain && request()->routeIs('super-admin.domain.dashboard')) {
        $activeDomain = request()->route('domain');
    }
    if (!$activeDomain && request()->routeIs('super-admin.sante.dashboard')) {
        $activeDomain = 'sante';
    }

    // Labels et couleurs par domaine pour la topbar
    $domainTopbarConfig = [
        'sante'            => ['label' => 'Santé',            'classes' => 'bg-emerald-100 text-emerald-800 dark:bg-emerald-900/40 dark:text-emerald-300', 'icon' => 'heart'],
        'informatique'     => ['label' => 'Informatique',     'classes' => 'bg-blue-100 text-blue-800 dark:bg-blue-900/40 dark:text-blue-300',             'icon' => 'monitor'],
        'hotellerie'       => ['label' => 'Hôtellerie',       'classes' => 'bg-amber-100 text-amber-800 dark:bg-amber-900/40 dark:text-amber-300',          'icon' => 'building'],
        'maconnerie'       => ['label' => 'Maçonnerie',       'classes' => 'bg-orange-100 text-orange-800 dark:bg-orange-900/40 dark:text-orange-300',      'icon' => 'wrench'],
        'design-interieur' => ['label' => 'Design intérieur', 'classes' => 'bg-violet-100 text-violet-800 dark:bg-violet-900/40 dark:text-violet-300',      'icon' => 'home'],
        'design-graphique' => ['label' => 'Design graphique', 'classes' => 'bg-pink-100 text-pink-800 dark:bg-pink-900/40 dark:text-pink-300',              'icon' => 'brush'],
    ];
    $topbarDomain = $domainTopbarConfig[$activeDomain] ?? null;

    $homeUrl = $user->isSuperAdmin()
        ? ($isFinance
            ? route('super-admin.finance.dashboard')
            : ($isStagiaires
                ? route('stagiaires.dashboard')
                : ($activeDomain && $activeDomain !== 'sante'
                    ? route('super-admin.domain.dashboard', $activeDomain)
                    : route('super-admin.sante.dashboard'))))
        : route('dashboard');
@endphp

<header class="sticky top-0 z-30 flex h-16 items-center justify-between gap-3 border-b border-slate-200 bg-white/90 px-4 backdrop-blur dark:border-slate-800 dark:bg-slate-900/90">
    <div class="flex min-w-0 items-center gap-3">
        <button type="button" class="rounded-lg p-2 text-slate-600 hover:bg-slate-100 lg:hidden dark:text-slate-300 dark:hover:bg-slate-800" x-on:click="$store.ui.toggleSidebar()" aria-label="Ouvrir le menu">
            <x-icon name="menu" class="h-5 w-5" />
        </button>
        @hasSection('breadcrumb')
            <nav class="hidden min-w-0 truncate text-sm text-slate-500 md:block" aria-label="Fil d'Ariane">
                <ol class="flex items-center gap-2">
                    <li><a href="{{ $homeUrl }}" class="hover:text-brand-700">Accueil</a></li>
                    @yield('breadcrumb')
                </ol>
            </nav>
        @else
            <div class="hidden items-center gap-2 sm:flex">
                <p class="truncate text-sm text-slate-500">{{ $centerLabel }}</p>
                @if($user->isSuperAdmin())
                    @if($isFinance)
                        <span class="inline-flex items-center gap-1.5 rounded-full bg-[#ECFDF5] px-2.5 py-0.5 text-xs font-semibold text-[#0DAD98] border border-[#0DAD98]/25 dark:bg-[#0DAD98]/20 dark:text-[#0DAD98]">
                            <span>📊</span>
                            <span>Gestion financière · Vue globale</span>
                        </span>
                    @elseif($isStagiaires)
                        <span class="inline-flex items-center gap-1.5 rounded-full bg-purple-100 px-2.5 py-0.5 text-xs font-semibold text-purple-800 dark:bg-purple-900/40 dark:text-purple-300">
                            <span class="text-xs">🧑‍💼</span>
                            <span>Stagiaires · Espace administratif</span>
                        </span>
                    @else
                        @if($topbarDomain)
                            <span class="inline-flex items-center gap-1 rounded-full {{ $topbarDomain['classes'] }} px-2 py-0.5 text-xs font-semibold">
                                <x-icon name="{{ $topbarDomain['icon'] }}" class="h-3.5 w-3.5" />
                                {{ $topbarDomain['label'] }}
                            </span>
                        @endif
                    @endif
                @endif
            </div>
        @endif
    </div>

    <div class="flex items-center gap-1 sm:gap-2">
        @if($user->isSuperAdmin())
            <a href="{{ route('super-admin.domaines') }}"
               id="topbar-changer-domaine"
               class="hidden sm:inline-flex items-center gap-1.5 rounded-xl border border-slate-200 bg-white px-2.5 py-1.5 text-xs font-semibold text-slate-700 shadow-2xs hover:bg-slate-50 hover:border-slate-300 dark:border-slate-700 dark:bg-slate-800 dark:text-slate-200 dark:hover:bg-slate-700 transition-all"
               title="{{ ($isFinance || $isStagiaires) ? 'Changer de domaine / contexte' : 'Retour à la sélection des domaines' }}">
                <x-icon name="arrow-uturn-left" class="h-3.5 w-3.5 text-slate-500 dark:text-slate-400" />
                <span>{{ ($isFinance || $isStagiaires) ? 'Changer de contexte' : 'Changer de domaine' }}</span>
            </a>
        @endif

        <button type="button" class="rounded-lg p-2 text-slate-500 hover:bg-slate-100 dark:hover:bg-slate-800" x-on:click="$store.ui.toggleTheme()" aria-label="Basculer le thème">
            <x-icon name="moon" class="h-5 w-5" x-show="!$store.ui.dark" />
            <x-icon name="sun" class="h-5 w-5" x-show="$store.ui.dark" x-cloak />
        </button>

        <div class="relative" x-data="{ open: false }" @click.outside="open = false">
            <button type="button" class="relative rounded-lg p-2 text-slate-500 hover:bg-slate-100 dark:hover:bg-slate-800" x-on:click="open = !open" aria-label="Notifications">
                <x-icon name="bell" class="h-5 w-5" />
                @if($unreadCount > 0)
                    <span class="absolute right-1 top-1 inline-flex min-w-4 items-center justify-center rounded-full bg-red-600 px-1 text-[10px] font-semibold text-white">{{ $unreadCount > 9 ? '9+' : $unreadCount }}</span>
                @endif
            </button>
            <div x-show="open" x-cloak x-transition class="absolute right-0 mt-2 w-80 overflow-hidden rounded-xl border border-slate-200 bg-white shadow-lg dark:border-slate-700 dark:bg-slate-900">
                <div class="flex items-center justify-between border-b border-slate-100 px-4 py-2.5 dark:border-slate-800">
                    <p class="text-sm font-semibold">Notifications</p>
                    @if($unreadCount > 0)
                        <form method="POST" action="{{ route('notifications.read-all') }}">
                            @csrf
                            @method('PATCH')
                            <button type="submit" class="text-xs font-medium text-brand-700 hover:underline">Tout marquer comme lu</button>
                        </form>
                    @endif
                </div>
                <ul class="max-h-80 overflow-y-auto">
                    @forelse($unreadList as $n)
                        <li class="border-b border-slate-50 px-4 py-3 text-sm last:border-0 dark:border-slate-800">
                            <p class="font-medium text-slate-800 dark:text-slate-100">{{ $n->title }}</p>
                            <p class="mt-0.5 line-clamp-2 text-xs text-slate-500">{{ $n->message }}</p>
                        </li>
                    @empty
                        <li class="px-4 py-6 text-center text-sm text-slate-500">Aucune notification non lue</li>
                    @endforelse
                </ul>
                <a href="{{ route('notifications.index') }}" class="block bg-slate-50 px-4 py-2 text-center text-xs font-medium text-brand-700 dark:bg-slate-800">Voir tout</a>
            </div>
        </div>

        <div class="relative" x-data="{ open: false }" @click.outside="open = false">
            <button type="button" class="flex items-center gap-2 rounded-lg p-1.5 hover:bg-slate-100 dark:hover:bg-slate-800" x-on:click="open = !open" aria-haspopup="true" :aria-expanded="open">
                <x-ui.avatar :name="$user->name" size="sm" />
                <span class="hidden text-left sm:block">
                    <span class="block max-w-[140px] truncate text-sm font-medium text-slate-800 dark:text-slate-100">{{ $user->name }}</span>
                    <span class="block max-w-[140px] truncate text-xs text-slate-500">{{ $roleLabel }} · {{ $centerLabel }}</span>
                </span>
                <x-icon name="chevron-down" class="hidden h-4 w-4 text-slate-400 sm:block" />
            </button>
            <div x-show="open" x-cloak x-transition class="absolute right-0 mt-2 w-64 overflow-hidden rounded-xl border border-slate-200 bg-white py-1 shadow-lg dark:border-slate-700 dark:bg-slate-900">
                <div class="border-b border-slate-100 px-4 py-3 dark:border-slate-800">
                    <p class="truncate font-semibold">{{ $user->name }}</p>
                    <p class="truncate text-xs text-slate-500">{{ $user->email }}</p>
                    <p class="mt-1 text-xs text-slate-500">Centre : <strong class="text-slate-700 dark:text-slate-200">{{ $centerLabel }}</strong></p>
                </div>
                <a href="{{ route('dashboard') }}" class="flex items-center gap-2 px-4 py-2 text-sm hover:bg-slate-50 dark:hover:bg-slate-800">
                    <x-icon name="home" class="h-4 w-4" /> Dashboard
                </a>
                <form action="{{ route('logout') }}" method="POST">
                    @csrf
                    <button type="submit" class="flex w-full items-center gap-2 px-4 py-2 text-sm text-red-600 hover:bg-red-50 dark:hover:bg-red-950/40">
                        <x-icon name="logout" class="h-4 w-4" /> Déconnexion
                    </button>
                </form>
            </div>
        </div>

        <form action="{{ route('logout') }}" method="POST" class="hidden md:block">
            @csrf
            <button type="submit" id="btn-logout-navbar" class="ec-btn-secondary ec-btn-sm" title="Se déconnecter">
                <x-icon name="logout" class="h-4 w-4" />
                Déconnexion
            </button>
        </form>
    </div>
</header>
