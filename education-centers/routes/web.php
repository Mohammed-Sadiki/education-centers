<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\CenterController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\SuperAdminDomainController;
use App\Http\Controllers\UserController;
use Illuminate\Support\Facades\Route;

// ──────────────────────────────────────────────────────────────────────────────
//  SITE PUBLIC
// ──────────────────────────────────────────────────────────────────────────────
Route::get('/', [\App\Http\Controllers\PublicController::class, 'home'])->name('home');
Route::get('/centres', [\App\Http\Controllers\PublicController::class, 'centers'])->name('public.centers');
Route::get('/centres/{center}', [\App\Http\Controllers\PublicController::class, 'centerShow'])->name('public.centers.show');
Route::get('/offres-formations', [\App\Http\Controllers\PublicController::class, 'formations'])->name('public.formations');
Route::get('/offres-formations/{formation}', [\App\Http\Controllers\PublicController::class, 'formationShow'])->name('public.formations.show');
Route::get('/actualites', [\App\Http\Controllers\PublicController::class, 'announcements'])->name('public.announcements');
Route::get('/actualites/{slug}', [\App\Http\Controllers\PublicController::class, 'announcementShow'])->name('public.announcements.show');
Route::get('/contact', [\App\Http\Controllers\PublicController::class, 'contactForm'])->name('public.contact');
Route::post('/contact', [\App\Http\Controllers\PublicController::class, 'contactStore'])->name('public.contact.store');
Route::get('/candidature', [\App\Http\Controllers\PublicController::class, 'applyForm'])->name('public.apply');
Route::post('/candidature', [\App\Http\Controllers\PublicController::class, 'applyStore'])->name('public.apply.store');
Route::get('/candidature/confirmation', [\App\Http\Controllers\PublicController::class, 'applySuccess'])->name('public.apply.success');
Route::get('/verifier-document', [\App\Http\Controllers\CertificateController::class, 'verify'])->name('public.verify-certificate');

// ──────────────────────────────────────────────────────────────────────────────
//  AUTHENTIFICATION (Guest uniquement)
// ──────────────────────────────────────────────────────────────────────────────
Route::middleware('guest')->group(function () {
    Route::get('/login', [AuthController::class, 'showLoginForm'])->name('login');
    Route::post('/login', [AuthController::class, 'login']);
});

// ──────────────────────────────────────────────────────────────────────────────
//  ESPACE AUTHENTIFIÉ — Tous les rôles
// ──────────────────────────────────────────────────────────────────────────────
Route::middleware('auth')->group(function () {
    Route::post('/logout', [AuthController::class, 'logout'])->name('logout');
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');

    // Notifications (tous les utilisateurs authentifiés)
    Route::get('/notifications', [\App\Http\Controllers\NotificationController::class, 'index'])->name('notifications.index');
    Route::patch('/notifications/{notification}/read', [\App\Http\Controllers\NotificationController::class, 'markRead'])->name('notifications.read');
    Route::patch('/notifications/read-all', [\App\Http\Controllers\NotificationController::class, 'markAllRead'])->name('notifications.read-all');
    Route::delete('/notifications/{notification}', [\App\Http\Controllers\NotificationController::class, 'destroy'])->name('notifications.destroy');

    // ──────────────────────────────────────────────────────────────────────────
    //  SUPER ADMIN uniquement
    // ──────────────────────────────────────────────────────────────────────────
    Route::middleware('role:super_admin')->group(function () {
        // ── Sélection des domaines (page d'entrée du Super Admin) ──
        Route::get('/super-admin/domaines', [SuperAdminDomainController::class, 'index'])
            ->name('super-admin.domaines');

        // ── Dashboard Santé (route nommée conservée pour compatibilité) ──
        Route::get('/super-admin/domaines/sante', [SuperAdminDomainController::class, 'santeDashboard'])
            ->name('super-admin.sante.dashboard');

        // ── Dashboards des 5 autres domaines (route paramétrée générique) ──
        // Slugs acceptés : informatique | hotellerie | maconnerie | design-interieur | design-graphique
        Route::get('/super-admin/domaines/{domain}', [SuperAdminDomainController::class, 'domainDashboard'])
            ->name('super-admin.domain.dashboard')
            ->where('domain', 'informatique|hotellerie|maconnerie|design-interieur|design-graphique');


        Route::get('/super-admin/stagiaires/dashboard', [\App\Http\Controllers\StagiaireController::class, 'dashboard'])
            ->name('stagiaires.dashboard');
        Route::get('/super-admin/stagiaires/documents', [\App\Http\Controllers\StagiaireController::class, 'documents'])
            ->name('stagiaires.documents');
        Route::get('/super-admin/stagiaires/{stagiaire}/cin/{side}', [\App\Http\Controllers\StagiaireController::class, 'downloadCin'])
            ->name('stagiaires.download-cin');
        Route::get('/super-admin/stagiaires/{stagiaire}/cin/{side}/get', [\App\Http\Controllers\StagiaireController::class, 'downloadCin'])
            ->name('stagiaires.cin-download');

        Route::get('/super-admin/stagiaires/{stagiaire}/diplomas/{diploma}/download', [\App\Http\Controllers\StagiaireController::class, 'downloadDiploma'])
            ->name('stagiaires.download-diploma');
        Route::get('/super-admin/stagiaires/{stagiaire}/diplomas/{diploma}/file', [\App\Http\Controllers\StagiaireController::class, 'downloadDiploma'])
            ->name('stagiaires.diploma-download');

        Route::get('/super-admin/stagiaires/{stagiaire}/documents/{document}/download', [\App\Http\Controllers\StagiaireController::class, 'downloadDocument'])
            ->name('stagiaires.download-document');
        Route::get('/super-admin/stagiaires/{stagiaire}/documents/{document}/file', [\App\Http\Controllers\StagiaireController::class, 'downloadDocument'])
            ->name('stagiaires.document-download');

        Route::delete('/super-admin/stagiaires/{stagiaire}/diplomas/{diploma}', [\App\Http\Controllers\StagiaireController::class, 'deleteDiploma'])
            ->name('stagiaires.delete-diploma');
        Route::delete('/super-admin/stagiaires/{stagiaire}/documents/{document}', [\App\Http\Controllers\StagiaireController::class, 'deleteDocument'])
            ->name('stagiaires.delete-document');
        Route::resource('super-admin/stagiaires', \App\Http\Controllers\StagiaireController::class)
            ->names('stagiaires');

        // ── Gestion financière globale (Contexte indépendant Super Admin & Multi-Centres Cad Syndic) ──
        Route::prefix('super-admin/finance')->name('super-admin.finance.')->group(function () {
            // 1. Tableau de bord
            Route::get('/', [\App\Http\Controllers\FinanceDashboardController::class, 'index'])->name('dashboard');
            // 2. Statistiques
            Route::get('/statistiques', [\App\Http\Controllers\FinanceDashboardController::class, 'statistics'])->name('statistics');
            // 3. Gestion financière : Paiements
            Route::get('/etudiants', [\App\Http\Controllers\FinanceDashboardController::class, 'students'])->name('students');
            Route::post('/paiements-etudiants', [\App\Http\Controllers\FinanceDashboardController::class, 'storeStudentPayment'])->name('students.payment.store');
            // 3. Gestion financière : Dépenses
            Route::get('/depenses', [\App\Http\Controllers\FinanceDashboardController::class, 'expenses'])->name('expenses');
            Route::post('/depenses', [\App\Http\Controllers\FinanceDashboardController::class, 'storeExpense'])->name('expenses.store');
            Route::patch('/depenses/{expense}/cancel', [\App\Http\Controllers\FinanceDashboardController::class, 'cancelExpense'])->name('expenses.cancel');
            Route::delete('/depenses/{expense}', [\App\Http\Controllers\FinanceDashboardController::class, 'destroyExpense'])->name('expenses.destroy');
            Route::get('/depenses/{expense}/justificatif', [\App\Http\Controllers\FinanceDashboardController::class, 'downloadExpenseReceipt'])->name('expenses.receipt');
            // 3. Gestion financière : Recouvrement
            Route::get('/recouvrement', [\App\Http\Controllers\FinanceDashboardController::class, 'recovery'])->name('recovery');
            // 4. Compte bancaire
            Route::get('/compte-bancaire', [\App\Http\Controllers\FinanceDashboardController::class, 'bank'])->name('bank');
            Route::post('/compte-bancaire', [\App\Http\Controllers\FinanceDashboardController::class, 'storeBankTransaction'])->name('bank.store');
            // 5. Comptabilité (Journal, CPC, Bilan, Grand Livre)
            Route::get('/comptabilite', [\App\Http\Controllers\FinanceDashboardController::class, 'accounting'])->name('accounting');
            // 6. Salaires des professeurs
            Route::get('/professeurs', [\App\Http\Controllers\FinanceDashboardController::class, 'teachers'])->name('teachers');
            Route::post('/salaires-professeurs', [\App\Http\Controllers\FinanceDashboardController::class, 'storeTeacherSalaryPayment'])->name('teachers.salary.store');
            // 7. Configuration des prix & budgets
            Route::get('/configuration', [\App\Http\Controllers\FinanceDashboardController::class, 'configuration'])->name('configuration');
            Route::put('/configuration', [\App\Http\Controllers\FinanceDashboardController::class, 'updateConfiguration'])->name('configuration.update');
            // 8. Rapports financiers & Historique
            Route::get('/historique', [\App\Http\Controllers\FinanceDashboardController::class, 'history'])->name('history');
            Route::get('/rapports', [\App\Http\Controllers\FinanceDashboardController::class, 'reports'])->name('reports');
        });

        Route::resource('centers', CenterController::class);
        Route::patch('centers/{center}/toggle-status', [CenterController::class, 'toggleStatus'])
            ->name('centers.toggle-status');
        // Audit logs (super admin)
        Route::get('/audit', [\App\Http\Controllers\AuditController::class, 'index'])->name('audit.index');
    });

    // ──────────────────────────────────────────────────────────────────────────
    //  SUPER ADMIN + CENTER ADMIN
    // ──────────────────────────────────────────────────────────────────────────
    Route::middleware('role:super_admin,center_admin')->group(function () {

        // Utilisateurs
        Route::resource('users', UserController::class);
        Route::patch('users/{user}/toggle-status', [UserController::class, 'toggleStatus'])
            ->name('users.toggle-status');

        // Étudiants
        Route::resource('students', \App\Http\Controllers\StudentController::class);
        Route::patch('students/{student}/toggle-status', [\App\Http\Controllers\StudentController::class, 'toggleStatus'])
            ->name('students.toggle-status');

        // Professeurs
        Route::resource('teachers', \App\Http\Controllers\TeacherController::class);
        Route::patch('teachers/{teacher}/toggle-status', [\App\Http\Controllers\TeacherController::class, 'toggleStatus'])
            ->name('teachers.toggle-status');

        // Formations
        Route::resource('formations', \App\Http\Controllers\FormationController::class);
        Route::patch('formations/{formation}/toggle-status', [\App\Http\Controllers\FormationController::class, 'toggleStatus'])
            ->name('formations.toggle-status');

        // Classes
        Route::resource('classes', \App\Http\Controllers\ClassController::class);
        Route::patch('classes/{class}/toggle-status', [\App\Http\Controllers\ClassController::class, 'toggleStatus'])
            ->name('classes.toggle-status');

        // Affectation professeurs ↔ classes
        Route::post('classes/{class}/teachers', [\App\Http\Controllers\ClassTeacherController::class, 'store'])
            ->name('classes.teachers.store');
        Route::delete('classes/{class}/teachers/{teacher}', [\App\Http\Controllers\ClassTeacherController::class, 'destroy'])
            ->name('classes.teachers.destroy');

        // Salles
        Route::resource('rooms', \App\Http\Controllers\RoomController::class);
        Route::patch('rooms/{room}/toggle-status', [\App\Http\Controllers\RoomController::class, 'toggleStatus'])
            ->name('rooms.toggle-status');

        // Emplois du temps
        Route::resource('schedules', \App\Http\Controllers\ScheduleController::class);
        Route::patch('schedules/{schedule}/toggle-status', [\App\Http\Controllers\ScheduleController::class, 'toggleStatus'])
            ->name('schedules.toggle-status');

        // 4I — Inscriptions
        Route::resource('enrollments', \App\Http\Controllers\EnrollmentController::class);
        Route::patch('enrollments/{enrollment}/status', [\App\Http\Controllers\EnrollmentController::class, 'changeStatus'])
            ->name('enrollments.change-status');

        // 4L — Paiements
        Route::resource('payments', \App\Http\Controllers\PaymentController::class);

        // 4L — Factures
        Route::resource('invoices', \App\Http\Controllers\InvoiceController::class);

        // 4M — Documents étudiants
        Route::resource('documents', \App\Http\Controllers\DocumentController::class, ['except' => ['edit','update']]);
        Route::get('documents/{document}/download', [\App\Http\Controllers\DocumentController::class, 'download'])
            ->name('documents.download');

        // 4N — Certificats
        Route::resource('certificates', \App\Http\Controllers\CertificateController::class, ['except' => ['edit','update']]);
        Route::get('certificates/{certificate}/download', [\App\Http\Controllers\CertificateController::class, 'download'])
            ->name('certificates.download');

        // 4O — Annonces
        Route::resource('announcements', \App\Http\Controllers\AnnouncementController::class);

        // Audit (admin centre)
        Route::get('/audit', [\App\Http\Controllers\AuditController::class, 'index'])->name('audit.index')->withoutMiddleware('role:super_admin');
    });

    // ──────────────────────────────────────────────────────────────────────────
    //  4J — PRÉSENCES (Admin + Teacher)
    // ──────────────────────────────────────────────────────────────────────────
    Route::middleware('role:super_admin,center_admin,teacher')->group(function () {
        Route::get('/attendances', [\App\Http\Controllers\AttendanceController::class, 'index'])->name('attendances.index');
        Route::get('/attendances/create', [\App\Http\Controllers\AttendanceController::class, 'create'])->name('attendances.create');
        Route::post('/attendances/session', [\App\Http\Controllers\AttendanceController::class, 'storeSession'])->name('attendances.store-session');
        Route::get('/attendances/{attendance}', [\App\Http\Controllers\AttendanceController::class, 'show'])->name('attendances.show');
        Route::get('/attendances/{attendance}/edit', [\App\Http\Controllers\AttendanceController::class, 'edit'])->name('attendances.edit');
        Route::patch('/attendances/{attendance}', [\App\Http\Controllers\AttendanceController::class, 'update'])->name('attendances.update');
        Route::delete('/attendances/{attendance}', [\App\Http\Controllers\AttendanceController::class, 'destroy'])->name('attendances.destroy');
        Route::get('/api/attendances/students-for-schedule', [\App\Http\Controllers\AttendanceController::class, 'getStudentsForSchedule'])->name('attendances.students-for-schedule');
    });

    // ──────────────────────────────────────────────────────────────────────────
    //  4K — ÉVALUATIONS / NOTES (Admin + Teacher)
    // ──────────────────────────────────────────────────────────────────────────
    Route::middleware('role:super_admin,center_admin,teacher')->group(function () {
        Route::resource('evaluations', \App\Http\Controllers\EvaluationController::class);
        Route::post('evaluations/{evaluation}/grades', [\App\Http\Controllers\EvaluationController::class, 'saveGrades'])
            ->name('evaluations.grades.save');
    });

    // ──────────────────────────────────────────────────────────────────────────
    //  ACCÈS ÉTUDIANT — Lecture seule
    // ──────────────────────────────────────────────────────────────────────────
    Route::middleware('role:student')->group(function () {
        Route::get('/mes-presences', [\App\Http\Controllers\AttendanceController::class, 'index'])->name('student.attendances');
        Route::get('/mes-notes', [\App\Http\Controllers\EvaluationController::class, 'index'])->name('student.evaluations');
        Route::get('/mes-documents', [\App\Http\Controllers\DocumentController::class, 'index'])->name('student.documents');
        Route::get('/documents/{document}/download', [\App\Http\Controllers\DocumentController::class, 'download'])
            ->name('student.documents.download')->withoutMiddleware('role:student');
        Route::get('/mes-certificats', [\App\Http\Controllers\CertificateController::class, 'index'])->name('student.certificates');
        Route::get('/mes-certificats/{certificate}/download', [\App\Http\Controllers\CertificateController::class, 'download'])
            ->name('student.certificates.download');
    });
});










