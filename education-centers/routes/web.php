<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\CenterController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\UserController;
use Illuminate\Support\Facades\Route;

// Page d'accueil publique
Route::get('/', function () {
    return view('home');
})->name('home');

// ──────────────────────────────────────────────────────────────────────────────
//  Authentification (Guest uniquement)
// ──────────────────────────────────────────────────────────────────────────────
Route::middleware('guest')->group(function () {
    Route::get('/login', [AuthController::class, 'showLoginForm'])->name('login');
    Route::post('/login', [AuthController::class, 'login']);
});

// ──────────────────────────────────────────────────────────────────────────────
//  Espace Authentifié (Tout utilisateur connecté)
// ──────────────────────────────────────────────────────────────────────────────
Route::middleware('auth')->group(function () {
    Route::post('/logout', [AuthController::class, 'logout'])->name('logout');
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');

    // ──────────────────────────────────────────────────────────────────────────
    //  CRUD Centres : strictement réservé au Super Admin
    // ──────────────────────────────────────────────────────────────────────────
    Route::middleware('role:super_admin')->group(function () {
        Route::resource('centers', CenterController::class);
        Route::patch('centers/{center}/toggle-status', [CenterController::class, 'toggleStatus'])
            ->name('centers.toggle-status');
    });

    // ──────────────────────────────────────────────────────────────────────────
    //  CRUD Utilisateurs : Super Admin + Admin Centre
    //  (La granularité des permissions est gérée par UserPolicy)
    // ──────────────────────────────────────────────────────────────────────────
    Route::middleware('role:super_admin,center_admin')->group(function () {
        Route::resource('users', UserController::class);
        Route::patch('users/{user}/toggle-status', [UserController::class, 'toggleStatus'])
            ->name('users.toggle-status');

        // CRUD Dossiers Étudiants (StudentPolicy gère l'isolation multi-centres)
        Route::resource('students', \App\Http\Controllers\StudentController::class);
        Route::patch('students/{student}/toggle-status', [\App\Http\Controllers\StudentController::class, 'toggleStatus'])
            ->name('students.toggle-status');

        // CRUD Dossiers Professeurs (TeacherPolicy gère l'isolation multi-centres)
        Route::resource('teachers', \App\Http\Controllers\TeacherController::class);
        Route::patch('teachers/{teacher}/toggle-status', [\App\Http\Controllers\TeacherController::class, 'toggleStatus'])
            ->name('teachers.toggle-status');

        // CRUD Formations (FormationPolicy gère l'isolation multi-centres)
        Route::resource('formations', \App\Http\Controllers\FormationController::class);
        Route::patch('formations/{formation}/toggle-status', [\App\Http\Controllers\FormationController::class, 'toggleStatus'])
            ->name('formations.toggle-status');

        // CRUD Classes (ClassPolicy gère l'isolation multi-centres)
        Route::resource('classes', \App\Http\Controllers\ClassController::class);
        Route::patch('classes/{class}/toggle-status', [\App\Http\Controllers\ClassController::class, 'toggleStatus'])
            ->name('classes.toggle-status');

        // Affectation des professeurs aux classes
        Route::post('classes/{class}/teachers', [\App\Http\Controllers\ClassTeacherController::class, 'store'])
            ->name('classes.teachers.store');
        Route::delete('classes/{class}/teachers/{teacher}', [\App\Http\Controllers\ClassTeacherController::class, 'destroy'])
            ->name('classes.teachers.destroy');

        // CRUD Salles (RoomPolicy gère l'isolation multi-centres)
        Route::resource('rooms', \App\Http\Controllers\RoomController::class);
        Route::patch('rooms/{room}/toggle-status', [\App\Http\Controllers\RoomController::class, 'toggleStatus'])
            ->name('rooms.toggle-status');

        // CRUD Emplois du temps / Planning (SchedulePolicy gère l'isolation multi-centres)
        Route::resource('schedules', \App\Http\Controllers\ScheduleController::class);
        Route::patch('schedules/{schedule}/toggle-status', [\App\Http\Controllers\ScheduleController::class, 'toggleStatus'])
            ->name('schedules.toggle-status');
    });
});







