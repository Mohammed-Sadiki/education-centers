@php
    use App\Http\Controllers\SuperAdminDomainController;

    /**
     * Configuration visuelle par domaine.
     * Chaque domaine a : libellé, description, classes Tailwind de couleur, icône SVG inline.
     */
    $domainConfig = [
        'sante' => [
            'label'       => __('Santé'),
            'description' => __('Formations paramédicales, soins infirmiers, aides-soignants, secrétariat médical et professions de santé.'),
            'color'       => 'emerald',
            'gradient'    => 'from-emerald-500 to-emerald-700',
            'badge_bg'    => 'bg-emerald-100 dark:bg-emerald-900/50',
            'badge_text'  => 'text-emerald-800 dark:text-emerald-300',
            'dot'         => 'bg-emerald-500',
            'icon'        => '<path stroke-linecap="round" stroke-linejoin="round" d="M21 8.25c0-2.485-2.099-4.5-4.688-4.5-1.935 0-3.597 1.126-4.312 2.733-.715-1.607-2.377-2.733-4.313-2.733C5.1 3.75 3 5.765 3 8.25c0 7.22 9 12 9 12s9-4.78 9-12Z"/>',
        ],
        'informatique' => [
            'label'       => __('Informatique'),
            'description' => __('Développement web, cybersécurité, réseaux, administration systèmes et intelligence artificielle.'),
            'color'       => 'blue',
            'gradient'    => 'from-blue-500 to-blue-700',
            'badge_bg'    => 'bg-blue-100 dark:bg-blue-900/50',
            'badge_text'  => 'text-blue-800 dark:text-blue-300',
            'dot'         => 'bg-blue-500',
            'icon'        => '<path stroke-linecap="round" stroke-linejoin="round" d="M9 17.25v1.007a3 3 0 0 1-.879 2.122L7.5 21h9l-.621-.621A3 3 0 0 1 15 18.257V17.25m6-12V15a2.25 2.25 0 0 1-2.25 2.25H5.25A2.25 2.25 0 0 1 3 15V5.25m18 0A2.25 2.25 0 0 0 18.75 3H5.25A2.25 2.25 0 0 0 3 5.25m18 0H3"/>',
        ],
        'hotellerie' => [
            'label'       => __('Hôtellerie'),
            'description' => __('Arts culinaires, service en salle, gestion hôtelière, hébergement et tourisme.'),
            'color'       => 'amber',
            'gradient'    => 'from-amber-500 to-amber-700',
            'badge_bg'    => 'bg-amber-100 dark:bg-amber-900/50',
            'badge_text'  => 'text-amber-800 dark:text-amber-300',
            'dot'         => 'bg-amber-500',
            'icon'        => '<path stroke-linecap="round" stroke-linejoin="round" d="M2.25 21h19.5m-18-18v18m10.5-18v18m6-13.5V21M6.75 6.75h.75m-.75 3h.75m-.75 3h.75m3-6h.75m-.75 3h.75m-.75 3h.75M6.75 21v-3.375c0-.621.504-1.125 1.125-1.125h2.25c.621 0 1.125.504 1.125 1.125V21M3 3h12m-.75 4.5H21m-3.75 3.75h.008v.008h-.008v-.008Zm0 3h.008v.008h-.008v-.008Zm0 3h.008v.008h-.008v-.008Z"/>',
        ],
        'maconnerie' => [
            'label'       => __('Maçonnerie'),
            'description' => __('Gros œuvre, maçonnerie générale, rénovation, béton armé et conduite de chantiers BTP.'),
            'color'       => 'orange',
            'gradient'    => 'from-orange-500 to-orange-700',
            'badge_bg'    => 'bg-orange-100 dark:bg-orange-900/50',
            'badge_text'  => 'text-orange-800 dark:text-orange-300',
            'dot'         => 'bg-orange-500',
            'icon'        => '<path stroke-linecap="round" stroke-linejoin="round" d="M11.42 15.17 17.25 21A2.652 2.652 0 0 0 21 17.25l-5.877-5.877M11.42 15.17l2.496-3.03c.317-.384.74-.626 1.208-.766M11.42 15.17l-4.655 5.653a2.548 2.548 0 1 1-3.586-3.586l6.837-5.63m5.108-.233c.55-.164 1.163-.188 1.743-.14a4.5 4.5 0 0 0 4.486-6.336l-3.276 3.277a3.004 3.004 0 0 1-2.25-2.25l3.276-3.276a4.5 4.5 0 0 0-6.336 4.486c.091 1.076-.071 2.264-.904 2.95l-.102.085m-1.745 1.437L5.909 7.5H4.5L2.25 3.75l1.5-1.5L7.5 4.5v1.409l4.26 4.26m-1.745 1.437 1.745-1.437m6.615 8.206L15.75 15.75M4.867 19.125h.008v.008h-.008v-.008Z"/>',
        ],
        'design-interieur' => [
            'label'       => __('Design intérieur'),
            'description' => __('Aménagement d\'espace, décoration, modélisation 3D architecturale et scénographie.'),
            'color'       => 'violet',
            'gradient'    => 'from-violet-500 to-violet-700',
            'badge_bg'    => 'bg-violet-100 dark:bg-violet-900/50',
            'badge_text'  => 'text-violet-800 dark:text-violet-300',
            'dot'         => 'bg-violet-500',
            'icon'        => '<path stroke-linecap="round" stroke-linejoin="round" d="m2.25 12 8.954-8.955c.44-.439 1.152-.439 1.591 0L21.75 12M4.5 9.75v10.125c0 .621.504 1.125 1.125 1.125H9.75v-4.875c0-.621.504-1.125 1.125-1.125h2.25c.621 0 1.125.504 1.125 1.125V21h4.125c.621 0 1.125-.504 1.125-1.125V9.75M8.25 21h8.25"/>',
        ],
        'design-graphique' => [
            'label'       => __('Design graphique'),
            'description' => __('Identité visuelle, typographie, illustration vectorielle, UI/UX design et packaging.'),
            'color'       => 'pink',
            'gradient'    => 'from-pink-500 to-pink-700',
            'badge_bg'    => 'bg-pink-100 dark:bg-pink-900/50',
            'badge_text'  => 'text-pink-800 dark:text-pink-300',
            'dot'         => 'bg-pink-500',
            'icon'        => '<path stroke-linecap="round" stroke-linejoin="round" d="M9.53 16.122a3 3 0 0 0-5.78 1.128 2.25 2.25 0 0 1-2.4 2.245 4.5 4.5 0 0 0 8.4-2.245c0-.399-.078-.78-.22-1.128Zm0 0a15.998 15.998 0 0 0 3.388-1.62m-5.043-.025a15.994 15.994 0 0 1 1.622-3.395m3.42 3.42a15.995 15.995 0 0 0 4.764-4.648l3.876-5.814a1.151 1.151 0 0 0-1.597-1.597L14.146 6.32a15.996 15.996 0 0 0-4.649 4.763m3.42 3.42a6.776 6.776 0 0 0-3.42-3.42"/>',
        ],
    ];

    $cfg = $domainConfig[$domain] ?? $domainConfig['sante'];
    $domainLabel = $cfg['label'];
@endphp

@extends('layouts.app')

@section('title', __('Dashboard') . ' ' . $domainLabel . ' — ' . __('Super Admin'))
@section('description', __('Tableau de bord du domaine') . ' ' . $domainLabel . ' — ' . __('vue d’ensemble des données.'))

@section('breadcrumb')
    <li class="flex items-center gap-2">
        <svg class="h-3 w-3 text-slate-400" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" aria-hidden="true">
            <path stroke-linecap="round" stroke-linejoin="round" d="M8.25 4.5l7.5 7.5-7.5 7.5"/>
        </svg>
        <a href="{{ route('super-admin.domaines') }}" class="hover:text-brand-700 dark:hover:text-brand-400 transition-colors">{{ __('Domaines') }}</a>
    </li>
    <li class="flex items-center gap-2">
        <svg class="h-3 w-3 text-slate-400" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" aria-hidden="true">
            <path stroke-linecap="round" stroke-linejoin="round" d="M8.25 4.5l7.5 7.5-7.5 7.5"/>
        </svg>
        <span class="font-medium text-slate-700 dark:text-slate-200">{{ $domainLabel }}</span>
    </li>
    <li class="flex items-center gap-2">
        <svg class="h-3 w-3 text-slate-400" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" aria-hidden="true">
            <path stroke-linecap="round" stroke-linejoin="round" d="M8.25 4.5l7.5 7.5-7.5 7.5"/>
        </svg>
        <span class="font-medium text-slate-700 dark:text-slate-200">{{ __('Dashboard') }}</span>
    </li>
@endsection

@section('content')

    {{-- ────────────────────────────────────────────────────────
         EN-TÊTE DU DASHBOARD
    ──────────────────────────────────────────────────────── --}}
    <div class="mb-8">
        <div class="flex flex-col gap-4 sm:flex-row sm:items-start sm:justify-between">
            <div class="flex items-start gap-4">
                {{-- Icône domaine (couleur dynamique via gradient inline) --}}
                <div class="flex h-14 w-14 shrink-0 items-center justify-center rounded-2xl bg-gradient-to-br {{ $cfg['gradient'] }} text-white shadow-md">
                    <svg class="h-7 w-7" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor" aria-hidden="true">
                        {!! $cfg['icon'] !!}
                    </svg>
                </div>
                <div>
                    {{-- Badge "Domaine actif" --}}
                    <span class="inline-flex items-center gap-1.5 rounded-full {{ $cfg['badge_bg'] }} px-2.5 py-0.5 text-xs font-semibold {{ $cfg['badge_text'] }} mb-1">
                        <span class="h-1.5 w-1.5 rounded-full {{ $cfg['dot'] }}"></span>
                        {{ __('Domaine actif') }}
                    </span>
                    <h1 class="text-2xl font-bold tracking-tight text-slate-900 dark:text-white">
                        {{ __('Espace') }} {{ $domainLabel }}
                    </h1>
                    <p class="mt-0.5 text-sm text-slate-500 dark:text-slate-400">
                        {{ __('Tableau de bord et gestion administrative de la filière') }} {{ $domainLabel }}
                    </p>
                </div>
            </div>

            {{-- Bouton Changer de domaine --}}
            <a href="{{ route('super-admin.domaines') }}"
               id="btn-retour-domaines"
               class="inline-flex items-center gap-2 rounded-xl border border-slate-200 bg-white px-4 py-2.5 text-sm font-semibold text-slate-700 shadow-xs
                      hover:bg-slate-50 hover:border-slate-300 hover:text-slate-900 hover:-translate-y-px
                      dark:border-slate-700 dark:bg-slate-800 dark:text-slate-200 dark:hover:bg-slate-700
                      transition-all duration-200 shrink-0"
               title="{{ __('Retourner au portail de sélection des domaines') }}">
                <x-icon name="arrow-uturn-left" class="h-4 w-4 text-slate-500 dark:text-slate-400" />
                <span>{{ __('Changer de domaine') }}</span>
            </a>
        </div>
    </div>

    {{-- ────────────────────────────────────────────────────────
         BANDEAU D'INFORMATION — DONNÉES NON FILTRÉES
    ──────────────────────────────────────────────────────── --}}
    <div class="mb-8 rounded-xl border border-amber-200 bg-amber-50 px-5 py-4 dark:border-amber-700/40 dark:bg-amber-900/20">
        <div class="flex items-start gap-3">
            <svg class="mt-0.5 h-5 w-5 shrink-0 text-amber-600 dark:text-amber-400" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" aria-hidden="true">
                <path stroke-linecap="round" stroke-linejoin="round" d="M11.25 11.25l.041-.02a.75.75 0 0 1 1.063.852l-.708 2.836a.75.75 0 0 0 1.063.853l.041-.021M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Zm-9-3.75h.008v.008H12V8.25Z"/>
            </svg>
            <div>
                <p class="text-sm font-semibold text-amber-800 dark:text-amber-300">{{ __('Données globales — filtrage par domaine à venir') }}</p>
                <p class="mt-0.5 text-xs text-amber-700 dark:text-amber-400">
                    {{ __('Les statistiques ci-dessous reflètent') }} <strong>{{ __('l\'ensemble des données') }}</strong> {{ __('de la plateforme (tous domaines confondus). Pour afficher uniquement les données du domaine') }} <strong>{{ $domainLabel }}</strong>{{ __(', il faudra ajouter un champ') }}
                    <code class="rounded bg-amber-200/70 px-1 py-0.5 font-mono dark:bg-amber-800/40">{{ __('domain') }}</code>
                    {{ __('sur les modèles (formations, centres, étudiants…) et appliquer le filtre correspondant.') }}
                </p>
            </div>
        </div>
    </div>

    {{-- ────────────────────────────────────────────────────────
         CARTES STATISTIQUES
    ──────────────────────────────────────────────────────── --}}
    <div class="mb-8 grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
        <x-ui.stat :label="__('Étudiants')"    :value="$stats['students']"            icon="academic"   tone="info"    />
        <x-ui.stat :label="__('Professeurs')"  :value="$stats['teachers']"            icon="user"        tone="success" />
        <x-ui.stat :label="__('Formations')"   :value="$stats['formations']"          icon="book"        tone="primary" />
        <x-ui.stat :label="__('Classes')"      :value="$stats['classes']"             icon="classes"     tone="info"    />
        <x-ui.stat :label="__('Inscriptions')" :value="$stats['enrollments']"         icon="clipboard"   tone="primary" />
        <x-ui.stat :label="__('En attente')"   :value="$stats['pending_enrollments']" icon="clock"       tone="warning" />
        <x-ui.stat :label="__('Présences aujourd\'hui')" :value="$stats['attendances_today']" icon="chart" tone="neutral" />
        <x-ui.stat :label="__('Revenus (payés)')"
                   :value="format_number($stats['payments_total'], 2) . ' MAD'"
                   icon="banknotes"
                   tone="success" />
    </div>

    {{-- ────────────────────────────────────────────────────────
         TABLEAUX : Inscriptions récentes + Prochains cours
    ──────────────────────────────────────────────────────── --}}
    <div class="grid gap-6 xl:grid-cols-2">

        {{-- Dernières inscriptions --}}
        <x-ui.card :padding="false">
            <div class="flex items-center gap-3 border-b border-slate-100 px-5 py-4 dark:border-slate-800">
                <div class="flex h-8 w-8 items-center justify-center rounded-lg bg-brand-100 dark:bg-brand-900/40">
                    <x-icon name="clipboard" class="h-4 w-4 text-brand-600 dark:text-brand-400" />
                </div>
                <h2 class="font-semibold text-slate-800 dark:text-white">{{ __('Dernières inscriptions') }}</h2>
            </div>
            <div class="ec-table-wrap border-0">
                <table class="ec-table">
                    <thead>
                        <tr>
                            <th>{{ __('Étudiant') }}</th>
                            <th>{{ __('Formation') }}</th>
                            <th>{{ __('Centre') }}</th>
                            <th>{{ __('Statut') }}</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($recentEnrollments as $e)
                            <tr>
                                <td>{{ $e->student?->full_name ?? '—' }}</td>
                                <td>{{ $e->formation?->name ?? '—' }}</td>
                                <td>{{ $e->center?->name ?? '—' }}</td>
                                <td><x-status-badge type="enrollment" :status="$e->status" /></td>
                            </tr>
                        @empty
                            <tr><td colspan="4" class="py-8 text-center text-slate-500">{{ __('Aucune inscription récente.') }}</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </x-ui.card>

        {{-- Prochains cours planifiés --}}
        <x-ui.card :padding="false">
            <div class="flex items-center gap-3 border-b border-slate-100 px-5 py-4 dark:border-slate-800">
                <div class="flex h-8 w-8 items-center justify-center rounded-lg bg-emerald-100 dark:bg-emerald-900/40">
                    <x-icon name="calendar" class="h-4 w-4 text-emerald-600 dark:text-emerald-400" />
                </div>
                <h2 class="font-semibold text-slate-800 dark:text-white">{{ __('Prochains cours planifiés') }}</h2>
            </div>
            <div class="divide-y divide-slate-100 dark:divide-slate-800">
                @forelse($upcomingSchedules as $schedule)
                    <div class="flex items-center justify-between px-5 py-3.5 text-sm">
                        <div class="min-w-0">
                            <p class="truncate font-medium text-slate-800 dark:text-white">
                                {{ $schedule->classRoom?->name ?? '—' }}
                            </p>
                            <p class="text-xs text-slate-500 dark:text-slate-400">
                                {{ $schedule->teacher?->user?->name ?? '—' }}
                                ·
                                {{ $schedule->room?->name ?? '—' }}
                            </p>
                        </div>
                        <div class="shrink-0 text-right text-xs text-slate-500 dark:text-slate-400">
                            <p class="font-medium text-slate-700 dark:text-slate-200">
                                {{ \Carbon\Carbon::parse($schedule->date)->translatedFormat('d M Y') }}
                            </p>
                            <p>{{ substr($schedule->start_time, 0, 5) }} – {{ substr($schedule->end_time, 0, 5) }}</p>
                        </div>
                    </div>
                @empty
                    <p class="px-5 py-8 text-center text-sm text-slate-500 dark:text-slate-400">
                        {{ __('Aucun cours planifié à venir.') }}
                    </p>
                @endforelse
            </div>
        </x-ui.card>

    </div>{{-- /grid --}}

@endsection
