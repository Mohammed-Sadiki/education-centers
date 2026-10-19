@php
    $user = auth()->user();
    $collapsed = false;
    $isStagiaires = request()->routeIs('stagiaires.*');

    // ─── Détection du contexte domaine de formation ───────────────────────────
    // On utilise la session active_domain pour identifier le contexte courant.
    // Si aucun domaine n'est en session mais que la route correspond à un domaine,
    // on le récupère depuis l'URL (robustesse en cas de navigation directe).
    $activeDomain = session('active_domain');
    if (!$activeDomain && request()->routeIs('super-admin.domain.dashboard')) {
        $activeDomain = request()->route('domain');
    }
    if (!$activeDomain && request()->routeIs('super-admin.sante.dashboard')) {
        $activeDomain = 'sante';
    }
    $isFormationDomain = $user->isSuperAdmin() && !$isStagiaires && !empty($activeDomain);

    // Configuration visuelle par domaine
    $domainMeta = [
        'sante'            => ['label' => 'Santé',            'color' => 'emerald', 'icon' => 'heart'],
        'informatique'     => ['label' => 'Informatique',     'color' => 'blue',    'icon' => 'monitor'],
        'hotellerie'       => ['label' => 'Hôtellerie',       'color' => 'amber',   'icon' => 'building'],
        'maconnerie'       => ['label' => 'Maçonnerie',       'color' => 'orange',  'icon' => 'wrench'],
        'design-interieur' => ['label' => 'Design intérieur', 'color' => 'violet',  'icon' => 'home'],
        'design-graphique' => ['label' => 'Design graphique', 'color' => 'pink',    'icon' => 'brush'],
    ];
    $currentDomainMeta = $domainMeta[$activeDomain] ?? $domainMeta['sante'];
    $domainLabel       = $currentDomainMeta['label'];
    $domainColor       = $currentDomainMeta['color'];
    $domainIcon        = $currentDomainMeta['icon'];

    // Palettes de couleur par domaine
    $colorPalette = [
        'emerald' => [
            'banner_bg'    => 'bg-emerald-50/80 dark:bg-emerald-950/30',
            'icon_bg'      => 'bg-emerald-600',
            'label_text'   => 'text-emerald-800 dark:text-emerald-300',
            'btn_border'   => 'border-emerald-300/80 dark:border-emerald-700/80',
            'btn_text'     => 'text-emerald-700 dark:text-emerald-300',
            'btn_hover'    => 'hover:bg-emerald-100 dark:hover:bg-emerald-900/40',
            'icon_hover'   => 'hover:bg-emerald-700',
        ],
        'blue' => [
            'banner_bg'    => 'bg-blue-50/80 dark:bg-blue-950/30',
            'icon_bg'      => 'bg-blue-600',
            'label_text'   => 'text-blue-800 dark:text-blue-300',
            'btn_border'   => 'border-blue-300/80 dark:border-blue-700/80',
            'btn_text'     => 'text-blue-700 dark:text-blue-300',
            'btn_hover'    => 'hover:bg-blue-100 dark:hover:bg-blue-900/40',
            'icon_hover'   => 'hover:bg-blue-700',
        ],
        'amber' => [
            'banner_bg'    => 'bg-amber-50/80 dark:bg-amber-950/30',
            'icon_bg'      => 'bg-amber-600',
            'label_text'   => 'text-amber-800 dark:text-amber-300',
            'btn_border'   => 'border-amber-300/80 dark:border-amber-700/80',
            'btn_text'     => 'text-amber-700 dark:text-amber-300',
            'btn_hover'    => 'hover:bg-amber-100 dark:hover:bg-amber-900/40',
            'icon_hover'   => 'hover:bg-amber-700',
        ],
        'orange' => [
            'banner_bg'    => 'bg-orange-50/80 dark:bg-orange-950/30',
            'icon_bg'      => 'bg-orange-600',
            'label_text'   => 'text-orange-800 dark:text-orange-300',
            'btn_border'   => 'border-orange-300/80 dark:border-orange-700/80',
            'btn_text'     => 'text-orange-700 dark:text-orange-300',
            'btn_hover'    => 'hover:bg-orange-100 dark:hover:bg-orange-900/40',
            'icon_hover'   => 'hover:bg-orange-700',
        ],
        'violet' => [
            'banner_bg'    => 'bg-violet-50/80 dark:bg-violet-950/30',
            'icon_bg'      => 'bg-violet-600',
            'label_text'   => 'text-violet-800 dark:text-violet-300',
            'btn_border'   => 'border-violet-300/80 dark:border-violet-700/80',
            'btn_text'     => 'text-violet-700 dark:text-violet-300',
            'btn_hover'    => 'hover:bg-violet-100 dark:hover:bg-violet-900/40',
            'icon_hover'   => 'hover:bg-violet-700',
        ],
        'pink' => [
            'banner_bg'    => 'bg-pink-50/80 dark:bg-pink-950/30',
            'icon_bg'      => 'bg-pink-600',
            'label_text'   => 'text-pink-800 dark:text-pink-300',
            'btn_border'   => 'border-pink-300/80 dark:border-pink-700/80',
            'btn_text'     => 'text-pink-700 dark:text-pink-300',
            'btn_hover'    => 'hover:bg-pink-100 dark:hover:bg-pink-900/40',
            'icon_hover'   => 'hover:bg-pink-700',
        ],
    ];
    $pal = $colorPalette[$domainColor] ?? $colorPalette['emerald'];

    // ─── Construction des liens de navigation ─────────────────────────────────
    $link = function (string $label, string $routeName, string $icon, string|array $pattern, ?string $fallbackUrl = null, array $params = []) {
        $url = \Illuminate\Support\Facades\Route::has($routeName) ? route($routeName, $params) : $fallbackUrl;
        if (!$url) return null;
        $active = request()->routeIs($pattern);
        if ($active && !empty($params['filter'])) {
            $active = request()->query('filter') === $params['filter'];
        } elseif ($active && empty($params['filter']) && is_string($pattern) && $pattern === 'stagiaires.index' && request()->filled('filter')) {
            $active = false;
        }
        return compact('label', 'url', 'icon', 'active');
    };

    $groups = [];

    if ($user->isSuperAdmin() && $isStagiaires) {
        // ────────────────────────────────────────────────────────────
        // CONTEXTE DÉDIÉ : GESTION ADMINISTRATIVE DES STAGIAIRES
        // ────────────────────────────────────────────────────────────
        $groups[] = [
            'title' => 'Dashboard',
            'items' => array_filter([
                $link('Tableau de bord', 'stagiaires.dashboard', 'chart', 'stagiaires.dashboard'),
            ])
        ];

        $groups[] = [
            'title' => 'Gestion des stagiaires',
            'items' => array_filter([
                $link('Tous les stagiaires', 'stagiaires.index', 'users', 'stagiaires.index'),
                $link('Ajouter un stagiaire', 'stagiaires.create', 'plus', 'stagiaires.create'),
            ])
        ];

        $groups[] = [
            'title' => 'Dossiers',
            'items' => array_filter([
                $link('Dossiers complets', 'stagiaires.index', 'check', 'stagiaires.index', null, ['filter' => 'complets']),
                $link('Dossiers incomplets', 'stagiaires.index', 'warning', 'stagiaires.index', null, ['filter' => 'incomplets']),
                $link('Documents', 'stagiaires.documents', 'document', 'stagiaires.documents*'),
            ])
        ];

    } elseif ($user->isSuperAdmin()) {
        // ────────────────────────────────────────────────────────────
        // CONTEXTE : DOMAINE DE FORMATION (Santé, Informatique, etc.)
        // Le dashboard du domaine actif est l'entrée principale.
        // ────────────────────────────────────────────────────────────

        // Lien Dashboard dynamique selon le domaine actif
        $dashboardRoute = ($activeDomain === 'sante' || !$activeDomain)
            ? route('super-admin.sante.dashboard')
            : route('super-admin.domain.dashboard', $activeDomain ?? 'sante');

        $groups[] = ['title' => null, 'items' => array_filter([
            ['label' => 'Dashboard ' . $domainLabel, 'url' => $dashboardRoute, 'icon' => $domainIcon,
             'active' => request()->routeIs(['super-admin.sante.dashboard', 'super-admin.domain.dashboard'])],
        ])];

        $adminItems = array_filter([
            $link('Centres', 'centers.index', 'building', 'centers.*'),
            $link('Utilisateurs', 'users.index', 'users', 'users.*'),
            $link('Étudiants', 'students.index', 'academic', 'students.*'),
            $link('Professeurs', 'teachers.index', 'user', 'teachers.*'),
            $link('Formations', 'formations.index', 'book', 'formations.*'),
            $link('Classes', 'classes.index', 'classes', 'classes.*'),
            $link('Salles', 'rooms.index', 'door', 'rooms.*'),
            $link('Emploi du temps', 'schedules.index', 'calendar', 'schedules.*'),
            $link('Inscriptions', 'enrollments.index', 'clipboard', 'enrollments.*'),
        ]);
        $groups[] = ['title' => 'Pédagogie', 'items' => $adminItems];

        $ops = array_filter([
            $link('Présences', 'attendances.index', 'clock', 'attendances.*'),
            $link('Évaluations', 'evaluations.index', 'chart', 'evaluations.*'),
            $link('Paiements', 'payments.index', 'banknotes', 'payments.*'),
            $link('Factures', 'invoices.index', 'document', 'invoices.*'),
            $link('Documents', 'documents.index', 'document', 'documents.*'),
            $link('Certificats', 'certificates.index', 'certificate', 'certificates.*'),
        ]);
        $groups[] = ['title' => 'Gestion', 'items' => $ops];

        $com = array_filter([
            $link('Notifications', 'notifications.index', 'bell', 'notifications.*'),
            $link('Annonces', 'announcements.index', 'megaphone', 'announcements.*'),
            $link('Audit Logs', 'audit.index', 'shield', 'audit.*'),
        ]);
        $groups[] = ['title' => 'Communication', 'items' => $com];

    } elseif ($user->isCenterAdmin()) {
        $groups[] = ['title' => null, 'items' => array_filter([
            $link('Dashboard', 'dashboard', 'home', 'dashboard'),
        ])];

        $adminItems = array_filter([
            $link('Utilisateurs', 'users.index', 'users', 'users.*'),
            $link('Étudiants', 'students.index', 'academic', 'students.*'),
            $link('Professeurs', 'teachers.index', 'user', 'teachers.*'),
            $link('Formations', 'formations.index', 'book', 'formations.*'),
            $link('Classes', 'classes.index', 'classes', 'classes.*'),
            $link('Salles', 'rooms.index', 'door', 'rooms.*'),
            $link('Emploi du temps', 'schedules.index', 'calendar', 'schedules.*'),
            $link('Inscriptions', 'enrollments.index', 'clipboard', 'enrollments.*'),
        ]);
        $groups[] = ['title' => 'Pédagogie', 'items' => $adminItems];

        $ops = array_filter([
            $link('Présences', 'attendances.index', 'clock', 'attendances.*'),
            $link('Évaluations', 'evaluations.index', 'chart', 'evaluations.*'),
            $link('Paiements', 'payments.index', 'banknotes', 'payments.*'),
            $link('Factures', 'invoices.index', 'document', 'invoices.*'),
            $link('Documents', 'documents.index', 'document', 'documents.*'),
            $link('Certificats', 'certificates.index', 'certificate', 'certificates.*'),
        ]);
        $groups[] = ['title' => 'Gestion', 'items' => $ops];

        $com = array_filter([
            $link('Notifications', 'notifications.index', 'bell', 'notifications.*'),
            $link('Annonces', 'announcements.index', 'megaphone', 'announcements.*'),
            $link('Audit Logs', 'audit.index', 'shield', 'audit.*'),
        ]);
        $groups[] = ['title' => 'Communication', 'items' => $com];

    } elseif ($user->isTeacher()) {
        $groups[] = ['title' => 'Enseignement', 'items' => array_filter([
            $link('Présences', 'attendances.index', 'clock', 'attendances.*'),
            $link('Évaluations & notes', 'evaluations.index', 'chart', 'evaluations.*'),
            $link('Notifications', 'notifications.index', 'bell', 'notifications.*'),
        ])];
    } elseif ($user->isStudent()) {
        $groups[] = ['title' => 'Mon espace', 'items' => array_filter([
            $link('Mes présences', 'student.attendances', 'clock', 'student.attendances'),
            $link('Mes notes', 'student.evaluations', 'chart', 'student.evaluations'),
            $link('Mes documents', 'student.documents', 'document', 'student.documents*'),
            $link('Mes certificats', 'student.certificates', 'certificate', 'student.certificates*'),
            $link('Notifications', 'notifications.index', 'bell', 'notifications.*'),
        ])];
    }
@endphp

<aside
    class="fixed inset-y-0 left-0 z-40 flex flex-col border-r border-slate-200 bg-white transition-all duration-200 dark:border-slate-800 dark:bg-slate-900
        -translate-x-full lg:static lg:translate-x-0"
    :class="{
        '-translate-x-full': !$store.ui.sidebarOpen,
        'translate-x-0': $store.ui.sidebarOpen,
        'ec-sidebar': !$store.ui.sidebarCollapsed,
        'ec-sidebar-collapsed': $store.ui.sidebarCollapsed
    }"
    aria-label="Navigation principale"
>
    <div class="flex h-16 items-center gap-2 border-b border-slate-200 px-4 dark:border-slate-800">
        <a href="{{ $user->isSuperAdmin() ? ($isStagiaires ? route('stagiaires.dashboard') : ($activeDomain && $activeDomain !== 'sante' ? route('super-admin.domain.dashboard', $activeDomain) : route('super-admin.sante.dashboard'))) : route('dashboard') }}" class="flex min-w-0 items-center gap-2 font-semibold text-slate-900 dark:text-white">
            <span class="flex h-8 w-8 shrink-0 items-center justify-center rounded-lg bg-brand-600 text-white">
                <x-icon name="academic" class="h-5 w-5" />
            </span>
            <span class="truncate" x-show="!$store.ui.sidebarCollapsed">Education Centers</span>
        </a>
    </div>

    @if($user->isSuperAdmin())
        @if($isStagiaires)
            {{-- BANNER DU CONTEXTE : GESTION ADMINISTRATIVE (STAGIAIRES) --}}
            <div class="border-b border-slate-200 bg-purple-50/80 p-3 dark:border-slate-800 dark:bg-purple-950/30">
                <div class="flex items-center justify-between gap-2" x-show="!$store.ui.sidebarCollapsed">
                    <div class="flex items-center gap-2 min-w-0">
                        <span class="flex h-7 w-7 shrink-0 items-center justify-center rounded-lg bg-purple-600 text-white shadow-2xs text-sm">
                            🧑‍💼
                        </span>
                        <div class="min-w-0">
                            <span class="block text-[10px] uppercase font-bold tracking-wider text-purple-800 dark:text-purple-300">Espace administratif</span>
                            <span class="block truncate text-xs font-bold text-slate-900 dark:text-white">Stagiaires</span>
                        </div>
                    </div>
                    <a href="{{ route('super-admin.domaines') }}"
                       class="inline-flex items-center gap-1 rounded-lg border border-purple-300/80 bg-white px-2 py-1 text-[11px] font-semibold text-purple-700 hover:bg-purple-100 hover:text-purple-800 dark:border-purple-700/80 dark:bg-slate-900 dark:text-purple-300 dark:hover:bg-purple-900/40 transition-colors shadow-2xs shrink-0"
                       title="Changer de domaine / contexte">
                        <x-icon name="arrow-uturn-left" class="h-3 w-3" />
                        <span>Changer</span>
                    </a>
                </div>
                <div class="flex justify-center" x-show="$store.ui.sidebarCollapsed">
                    <a href="{{ route('super-admin.domaines') }}"
                       class="flex h-8 w-8 items-center justify-center rounded-lg bg-purple-600 text-white hover:bg-purple-700 transition-colors shadow-2xs"
                       title="Espace Stagiaires — Cliquer pour changer de domaine / contexte">
                        <x-icon name="briefcase" class="h-4.5 w-4.5" />
                    </a>
                </div>
            </div>
        @else
            {{-- BANNER DU CONTEXTE : DOMAINE DE FORMATION (dynamique) --}}
            <div class="border-b border-slate-200 {{ $pal['banner_bg'] }} p-3 dark:border-slate-800"
                 data-domain="{{ $activeDomain }}"
                 id="sidebar-domain-banner">
                <div class="flex items-center justify-between gap-2" x-show="!$store.ui.sidebarCollapsed">
                    <div class="flex items-center gap-2 min-w-0">
                        <span class="flex h-7 w-7 shrink-0 items-center justify-center rounded-lg {{ $pal['icon_bg'] }} text-white shadow-2xs">
                            <x-icon name="{{ $domainIcon }}" class="h-4 w-4" />
                        </span>
                        <div class="min-w-0">
                            <span class="block text-[10px] uppercase font-bold tracking-wider {{ $pal['label_text'] }}">Domaine</span>
                            <span class="block truncate text-xs font-bold text-slate-900 dark:text-white">{{ $domainLabel }}</span>
                        </div>
                    </div>
                    <a href="{{ route('super-admin.domaines') }}"
                       class="inline-flex items-center gap-1 rounded-lg border {{ $pal['btn_border'] }} bg-white px-2 py-1 text-[11px] font-semibold {{ $pal['btn_text'] }} {{ $pal['btn_hover'] }} dark:bg-slate-900 transition-colors shadow-2xs shrink-0"
                       title="Changer de domaine"
                       id="sidebar-changer-domaine">
                        <x-icon name="arrow-uturn-left" class="h-3 w-3" />
                        <span>Changer</span>
                    </a>
                </div>
                <div class="flex justify-center" x-show="$store.ui.sidebarCollapsed">
                    <a href="{{ route('super-admin.domaines') }}"
                       class="flex h-8 w-8 items-center justify-center rounded-lg {{ $pal['icon_bg'] }} text-white {{ $pal['icon_hover'] }} transition-colors shadow-2xs"
                       title="{{ $domainLabel }} — Cliquer pour changer de domaine">
                        <x-icon name="{{ $domainIcon }}" class="h-4.5 w-4.5" />
                    </a>
                </div>
            </div>
        @endif
    @endif

    <nav class="flex-1 space-y-6 overflow-y-auto p-3">
        @foreach($groups as $group)
            @if(!empty($group['items']))
                <div>
                    @if($group['title'])
                        <p class="mb-1 px-3 text-[11px] font-semibold uppercase tracking-wider text-slate-400" x-show="!$store.ui.sidebarCollapsed">{{ $group['title'] }}</p>
                    @endif
                    <ul class="space-y-0.5">
                        @foreach($group['items'] as $item)
                            <li>
                                <a href="{{ $item['url'] }}"
                                   class="ec-nav-link {{ $item['active'] ? 'ec-nav-link-active' : '' }}"
                                   @if($item['active']) aria-current="page" @endif
                                   title="{{ $item['label'] }}">
                                    <x-icon :name="$item['icon']" class="h-5 w-5 shrink-0" />
                                    <span class="truncate" x-show="!$store.ui.sidebarCollapsed">{{ $item['label'] }}</span>
                                </a>
                            </li>
                        @endforeach
                    </ul>
                </div>
            @endif
        @endforeach
    </nav>

    <div class="hidden border-t border-slate-200 p-3 lg:block dark:border-slate-800">
        <button type="button" class="ec-nav-link w-full" x-on:click="$store.ui.toggleCollapsed()" :aria-expanded="!$store.ui.sidebarCollapsed">
            <x-icon name="chevron-left" class="h-5 w-5" x-bind:class="$store.ui.sidebarCollapsed ? 'rotate-180' : ''" />
            <span x-show="!$store.ui.sidebarCollapsed">Réduire</span>
        </button>
    </div>
</aside>
