<?php

namespace App\Http\Controllers;

use App\Models\Attendance;
use App\Models\Certificate;
use App\Models\ClassRoom;
use App\Models\Enrollment;
use App\Models\Formation;
use App\Models\Payment;
use App\Models\Schedule;
use App\Models\Student;
use App\Models\Teacher;
use Illuminate\View\View;

class SuperAdminDomainController extends Controller
{
    /**
     * Domaines de formation disponibles.
     * Clé = slug de route/session, Valeur = libellé affiché.
     */
    public const DOMAINS = [
        'sante'            => 'Santé',
        'informatique'     => 'Informatique',
        'hotellerie'       => 'Hôtellerie',
        'maconnerie'       => 'Maçonnerie',
        'design-interieur' => 'Design intérieur',
        'design-graphique' => 'Design graphique',
    ];

    /**
     * Affiche la page de sélection des domaines du Super Admin.
     * Réinitialise le domaine actif en session pour partir d'un état neutre.
     */
    public function index(): View
    {
        session()->forget('active_domain');

        return view('super-admin.domaines');
    }

    /**
     * Dashboard générique d'un domaine de formation.
     *
     * Route paramétrée : GET /super-admin/domaines/{domain}
     * Le slug {domain} est validé contre la liste DOMAINS (404 si inconnu).
     *
     * Les statistiques reflètent l'ensemble de la plateforme (tous centres)
     * car aucune colonne `domain` n'existe encore dans le schéma.
     * Un bandeau d'avertissement est affiché dans la vue.
     */
    public function domainDashboard(string $domain): View
    {
        abort_unless(array_key_exists($domain, self::DOMAINS), 404);

        session(['active_domain' => $domain]);

        $stats = [
            'students'            => Student::count(),
            'teachers'            => Teacher::count(),
            'formations'          => Formation::count(),
            'classes'             => ClassRoom::count(),
            'enrollments'         => Enrollment::count(),
            'pending_enrollments' => Enrollment::where('status', 'pending')->count(),
            'attendances_today'   => Attendance::whereDate('date', now()->toDateString())->count(),
            'payments_total'      => Payment::where('status', 'paid')->sum('amount'),
            'certificates_issued' => Certificate::where('status', 'issued')->count(),
        ];

        $recentEnrollments = Enrollment::with(['student', 'formation', 'center'])
            ->latest()
            ->take(8)
            ->get();

        $upcomingSchedules = Schedule::where('date', '>=', now()->toDateString())
            ->where('status', 'scheduled')
            ->with(['classRoom', 'teacher.user', 'room'])
            ->orderBy('date')
            ->orderBy('start_time')
            ->take(5)
            ->get();

        return view('super-admin.domain.dashboard', compact(
            'domain',
            'stats',
            'recentEnrollments',
            'upcomingSchedules'
        ));
    }

    /**
     * Alias conservé pour compatibilité : redirige vers le dashboard Santé générique.
     * Les anciens liens et tests utilisant super-admin.sante.dashboard continuent de fonctionner.
     */
    public function santeDashboard(): View
    {
        return $this->domainDashboard('sante');
    }
}
