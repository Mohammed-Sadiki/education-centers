@php
    $user = auth()->user();
    $collapsed = false;
    $link = function (string $label, string $routeName, string $icon, string|array $pattern, ?string $fallbackUrl = null) {
        $url = \Illuminate\Support\Facades\Route::has($routeName) ? route($routeName) : $fallbackUrl;
        if (!$url) return null;
        $active = request()->routeIs($pattern);
        return compact('label', 'url', 'icon', 'active');
    };

    $groups = [];

    $dashboardLabel = $user->isSuperAdmin() ? 'Dashboard Santé' : 'Dashboard';
    $dashboardRoute = $user->isSuperAdmin() ? 'super-admin.sante.dashboard' : 'dashboard';
    $dashboardIcon  = $user->isSuperAdmin() ? 'heart' : 'home';
    $dashboardPattern = $user->isSuperAdmin() ? ['super-admin.sante.*', 'dashboard'] : 'dashboard';

    $groups[] = ['title' => null, 'items' => array_filter([
        $link($dashboardLabel, $dashboardRoute, $dashboardIcon, $dashboardPattern),
    ])];

    if ($user->isSuperAdmin() || $user->isCenterAdmin()) {
        $adminItems = array_filter([
            $user->isSuperAdmin() ? $link('Centres', 'centers.index', 'building', 'centers.*') : null,
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
        <a href="{{ $user->isSuperAdmin() ? route('super-admin.sante.dashboard') : route('dashboard') }}" class="flex min-w-0 items-center gap-2 font-semibold text-slate-900 dark:text-white">
            <span class="flex h-8 w-8 shrink-0 items-center justify-center rounded-lg bg-brand-600 text-white">
                <x-icon name="academic" class="h-5 w-5" />
            </span>
            <span class="truncate" x-show="!$store.ui.sidebarCollapsed">Education Centers</span>
        </a>
    </div>

    @if($user->isSuperAdmin())
        <div class="border-b border-slate-200 bg-emerald-50/80 p-3 dark:border-slate-800 dark:bg-emerald-950/30">
            <div class="flex items-center justify-between gap-2" x-show="!$store.ui.sidebarCollapsed">
                <div class="flex items-center gap-2 min-w-0">
                    <span class="flex h-7 w-7 shrink-0 items-center justify-center rounded-lg bg-emerald-600 text-white shadow-2xs">
                        <x-icon name="heart" class="h-4 w-4" />
                    </span>
                    <div class="min-w-0">
                        <span class="block text-[10px] uppercase font-bold tracking-wider text-emerald-800 dark:text-emerald-300">Domaine</span>
                        <span class="block truncate text-xs font-bold text-slate-900 dark:text-white">Santé</span>
                    </div>
                </div>
                <a href="{{ route('super-admin.domaines') }}"
                   class="inline-flex items-center gap-1 rounded-lg border border-emerald-300/80 bg-white px-2 py-1 text-[11px] font-semibold text-emerald-700 hover:bg-emerald-100 hover:text-emerald-800 dark:border-emerald-700/80 dark:bg-slate-900 dark:text-emerald-300 dark:hover:bg-emerald-900/40 transition-colors shadow-2xs shrink-0"
                   title="Changer de domaine">
                    <x-icon name="arrow-uturn-left" class="h-3 w-3" />
                    <span>Changer</span>
                </a>
            </div>
            <div class="flex justify-center" x-show="$store.ui.sidebarCollapsed">
                <a href="{{ route('super-admin.domaines') }}"
                   class="flex h-8 w-8 items-center justify-center rounded-lg bg-emerald-600 text-white hover:bg-emerald-700 transition-colors shadow-2xs"
                   title="Domaine Santé — Cliquer pour changer de domaine">
                    <x-icon name="heart" class="h-4.5 w-4.5" />
                </a>
            </div>
        </div>
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
