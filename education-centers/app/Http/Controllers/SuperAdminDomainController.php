<?php

namespace App\Http\Controllers;

use App\Models\Attendance;
use App\Models\Center;
use App\Models\Certificate;
use App\Models\ClassRoom;
use App\Models\Enrollment;
use App\Models\Formation;
use App\Models\Payment;
use App\Models\Schedule;
use App\Models\Student;
use App\Models\Teacher;
use Illuminate\Http\Request;
use Illuminate\View\View;

class SuperAdminDomainController extends Controller
{
    /**
     * Affiche la page de sélection des domaines du Super Admin.
     * Accessible uniquement au rôle super_admin (garanti par le middleware de route).
     * Réinitialise le domaine actif en session pour s'assurer que le Super Admin
     * repart d'un état neutre sans menu métier.
     */
    public function index(): View
    {
        session()->forget('active_domain');

        return view('super-admin.domaines');
    }

    /**
     * Affiche le Dashboard du domaine Santé.
     * Enregistre le domaine Santé comme domaine actif en session.
     *
     * À ce stade, aucune colonne "domaine" n'existe dans les modèles,
     * donc les statistiques affichées reflètent l'ensemble des données
     * disponibles dans la plateforme (tous centres confondus).
     *
     * Pour filtrer réellement sur le domaine Santé, il faudra :
     * 1. Ajouter un champ `domain` (ou une relation `Domain`) sur les modèles
     *    Center, Formation, ClassRoom, Student, Teacher, etc.
     * 2. Créer une migration pour ajouter cette colonne.
     * 3. Adapter les seeders/factories.
     * 4. Mettre à jour ce contrôleur pour filtrer avec ->where('domain', 'sante').
     *
     * En attendant, les cartes statistiques affichent les totaux globaux
     * avec un avertissement clair dans la vue.
     */
    public function santeDashboard(): View
    {
        session(['active_domain' => 'sante']);

        $stats = [
            'students'             => Student::count(),
            'teachers'             => Teacher::count(),
            'formations'           => Formation::count(),
            'classes'              => ClassRoom::count(),
            'enrollments'          => Enrollment::count(),
            'pending_enrollments'  => Enrollment::where('status', 'pending')->count(),
            'attendances_today'    => Attendance::whereDate('date', now()->toDateString())->count(),
            'payments_total'       => Payment::where('status', 'paid')->sum('amount'),
            'certificates_issued'  => Certificate::where('status', 'issued')->count(),
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

        return view('super-admin.sante.dashboard', compact(
            'stats',
            'recentEnrollments',
            'upcomingSchedules'
        ));
    }
}
