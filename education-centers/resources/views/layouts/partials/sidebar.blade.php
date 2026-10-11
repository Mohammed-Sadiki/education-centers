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

    $groups[] = ['title' => null, 'items' => array_filter([
        $link('Dashboard', 'dashboard', 'home', 'dashboard'),
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
        <a href="{{ route('dashboard') }}" class="flex min-w-0 items-center gap-2 font-semibold text-slate-900 dark:text-white">
            <span class="flex h-8 w-8 shrink-0 items-center justify-center rounded-lg bg-brand-600 text-white">
                <x-icon name="academic" class="h-5 w-5" />
            </span>
            <span class="truncate" x-show="!$store.ui.sidebarCollapsed">Education Centers</span>
        </a>
    </div>

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
