<?php

namespace Tests\Feature;

use App\Models\AppNotification;
use App\Models\Attendance;
use App\Models\Center;
use App\Models\ClassRoom;
use App\Models\Enrollment;
use App\Models\Evaluation;
use App\Models\Formation;
use App\Models\Grade;
use App\Models\Room;
use App\Models\Schedule;
use App\Models\Student;
use App\Models\Teacher;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Espace Professeur : tableau de bord, mes classes, emploi du temps,
 * présences et évaluations filtrés par les affectations réelles
 * `class_teacher`, isolation par centre, sécurité serveur (403 sur les
 * identifiants manipulés dans l'URL) et traduction FR/AR avec RTL.
 */
class TeacherPortalTest extends TestCase
{
    use RefreshDatabase;

    private Center $berlin;

    private Center $munich;

    private Teacher $teacherSolo;

    private Teacher $teacherMulti;

    private Teacher $teacherFree;

    private Teacher $teacherMunich;

    private User $soloUser;

    private User $multiUser;

    private User $freeUser;

    private User $munichUser;

    private Room $roomBerlin;

    private Room $roomMunich;

    private Formation $formationSante;

    private Formation $formationInfo;

    private Formation $formationMunich;

    private ClassRoom $classSolo;

    private ClassRoom $classAlpha;

    private ClassRoom $classBravo;

    private ClassRoom $classOrpheline;

    private ClassRoom $classMunich;

    private Student $studentSolo1;

    private Student $studentSolo2;

    private Student $studentAlpha;

    private Student $studentBravo;

    private Student $studentOrpheline;

    private Student $studentMunich;

    private Schedule $scheduleSolo;

    private Schedule $scheduleAlpha;

    private Schedule $scheduleBravo;

    private Schedule $scheduleOrpheline;

    private Schedule $scheduleMunich;

    private Evaluation $evaluationSolo;

    private Evaluation $evaluationAlpha;

    private Evaluation $evaluationBravo;

    private Evaluation $evaluationOrpheline;

    private Evaluation $evaluationMunich;

    protected function setUp(): void
    {
        parent::setUp();

        $this->berlin = Center::create([
            'name' => 'Centre Berlin',
            'code' => 'BER',
            'address' => 'Adresse Berlin',
            'status' => 'active',
        ]);

        $this->munich = Center::create([
            'name' => 'Centre Munich',
            'code' => 'MUN',
            'address' => 'Adresse Munich',
            'status' => 'active',
        ]);

        // Quatre professeurs : une classe, multi-classes/multi-domaines,
        // sans classe, et un professeur d'un autre centre.
        $this->soloUser = $this->makeTeacherUser($this->berlin, 'Solo Prof');
        $this->multiUser = $this->makeTeacherUser($this->berlin, 'Multi Prof');
        $this->freeUser = $this->makeTeacherUser($this->berlin, 'Sans Classe Prof');
        $this->munichUser = $this->makeTeacherUser($this->munich, 'Munich Prof');

        $this->teacherSolo = $this->makeTeacher($this->soloUser, $this->berlin, 'BER-T-1', 'Solo', 'Profta');
        $this->teacherMulti = $this->makeTeacher($this->multiUser, $this->berlin, 'BER-T-2', 'Milena', 'Richter');
        $this->teacherFree = $this->makeTeacher($this->freeUser, $this->berlin, 'BER-T-3', 'Noel', 'Vidal');
        $this->teacherMunich = $this->makeTeacher($this->munichUser, $this->munich, 'MUN-T-1', 'Greta', 'Schneider');

        $this->roomBerlin = Room::create([
            'center_id' => $this->berlin->id,
            'name' => 'Salle Berlin 01',
            'code' => 'ROOM-BER-01',
            'capacity' => 20,
            'status' => 'active',
        ]);

        $this->roomMunich = Room::create([
            'center_id' => $this->munich->id,
            'name' => 'Salle Munich 01',
            'code' => 'ROOM-MUN-01',
            'capacity' => 20,
            'status' => 'active',
        ]);

        $this->formationSante = $this->makeFormation($this->berlin, 'sante', 'Formation Santé Berlin', 'BER-SAN');
        $this->formationInfo = $this->makeFormation($this->berlin, 'informatique', 'Formation Informatique Berlin', 'BER-INFO');
        $this->formationMunich = $this->makeFormation($this->munich, 'sante', 'Formation Santé Munich', 'MUN-SAN');

        $this->classSolo = $this->makeClass($this->formationSante, 'Groupe Solo', 'CLS-SOLO');
        $this->classAlpha = $this->makeClass($this->formationSante, 'Groupe Alpha', 'CLS-ALPHA');
        $this->classBravo = $this->makeClass($this->formationInfo, 'Groupe Bravo', 'CLS-BRAVO');
        $this->classOrpheline = $this->makeClass($this->formationSante, 'Groupe Orpheline', 'CLS-ORPH');
        $this->classMunich = $this->makeClass($this->formationMunich, 'Groupe Munich', 'CLS-MUN');

        // Affectations réelles via class_teacher.
        $this->classSolo->teachers()->attach($this->teacherSolo->id);
        $this->classAlpha->teachers()->attach($this->teacherMulti->id);
        $this->classBravo->teachers()->attach($this->teacherMulti->id);
        $this->classMunich->teachers()->attach($this->teacherMunich->id);
        // classOrpheline n'est affectée à personne ; teacherFree n'a aucune classe.

        $this->studentSolo1 = $this->makeStudent($this->berlin, 'BER-STU-001', 'Alice', 'Solo');
        $this->studentSolo2 = $this->makeStudent($this->berlin, 'BER-STU-002', 'Bruno', 'Solo');
        $this->studentAlpha = $this->makeStudent($this->berlin, 'BER-STU-003', 'Chloé', 'Alpha');
        $this->studentBravo = $this->makeStudent($this->berlin, 'BER-STU-004', 'David', 'Bravo');
        $this->studentOrpheline = $this->makeStudent($this->berlin, 'BER-STU-005', 'Elsa', 'Orpheline');
        $this->studentMunich = $this->makeStudent($this->munich, 'MUN-STU-001', 'Fritz', 'Munich');

        $this->makeEnrollment($this->studentSolo1, $this->formationSante, $this->classSolo, 'INS-001');
        $this->makeEnrollment($this->studentSolo2, $this->formationSante, $this->classSolo, 'INS-002');
        $this->makeEnrollment($this->studentAlpha, $this->formationSante, $this->classAlpha, 'INS-003');
        $this->makeEnrollment($this->studentBravo, $this->formationInfo, $this->classBravo, 'INS-004');
        $this->makeEnrollment($this->studentOrpheline, $this->formationSante, $this->classOrpheline, 'INS-005');
        $this->makeEnrollment($this->studentMunich, $this->formationMunich, $this->classMunich, 'INS-006');

        $this->scheduleSolo = $this->makeSchedule($this->classSolo, $this->teacherSolo, $this->roomBerlin, 'Séance Solo', now()->toDateString(), 'course');
        $this->scheduleAlpha = $this->makeSchedule($this->classAlpha, $this->teacherMulti, $this->roomBerlin, 'Séance Alpha', now()->addDays(2)->toDateString(), 'course');
        $this->scheduleBravo = $this->makeSchedule($this->classBravo, $this->teacherMulti, $this->roomBerlin, 'Séance Bravo', now()->addDays(3)->toDateString(), 'workshop');
        $this->scheduleOrpheline = $this->makeSchedule($this->classOrpheline, $this->teacherFree, $this->roomBerlin, 'Séance Orpheline', now()->addDays(4)->toDateString(), 'course');
        $this->scheduleMunich = $this->makeSchedule($this->classMunich, $this->teacherMunich, $this->roomMunich, 'Séance Munich', now()->addDays(5)->toDateString(), 'course');

        $this->makeAttendance($this->scheduleSolo, $this->studentSolo1, 'present');
        $this->makeAttendance($this->scheduleAlpha, $this->studentAlpha, 'absent');
        $this->makeAttendance($this->scheduleOrpheline, $this->studentOrpheline, 'late');
        $this->makeAttendance($this->scheduleMunich, $this->studentMunich, 'present');

        $this->evaluationSolo = $this->makeEvaluation($this->classSolo, $this->teacherSolo, 'Eval Solo', 'exam', 'published');
        $this->evaluationAlpha = $this->makeEvaluation($this->classAlpha, $this->teacherMulti, 'Eval Alpha', 'control', 'draft');
        $this->evaluationBravo = $this->makeEvaluation($this->classBravo, $this->teacherMulti, 'Eval Bravo', 'exam', 'published');
        $this->evaluationOrpheline = $this->makeEvaluation($this->classOrpheline, $this->teacherFree, 'Eval Orpheline', 'exam', 'draft');
        $this->evaluationMunich = $this->makeEvaluation($this->classMunich, $this->teacherMunich, 'Eval Munich', 'exam', 'published');

        $this->makeGrade($this->evaluationAlpha, $this->studentAlpha, 12.5, 'NOTE-ALPHA');

        AppNotification::create([
            'user_id' => $this->multiUser->id,
            'center_id' => $this->berlin->id,
            'type' => 'info',
            'title' => 'Notification Multi',
            'message' => 'Message pour le professeur multi-domaines.',
        ]);
    }

    // ── 1. Tableau de bord ───────────────────────────────────────────────────

    public function test_dashboard_shows_only_real_assignment_data(): void
    {
        $response = $this->actingAs($this->multiUser)->get(route('dashboard'));

        $response->assertOk()
            ->assertSee('Multi Prof')
            ->assertSee('Centre Berlin')
            ->assertSee('Groupe Alpha')
            ->assertSee('Groupe Bravo')
            ->assertSee('Notification Multi')
            ->assertSee('Eval Alpha') // évaluation à corriger (brouillon sans notes)
            ->assertDontSee('Groupe Orpheline')
            ->assertDontSee('Groupe Munich')
            ->assertDontSee('Séance Munich')
            ->assertDontSee('Eval Munich');
    }

    public function test_dashboard_single_class_teacher_counts_students_from_assignments(): void
    {
        $response = $this->actingAs($this->soloUser)->get(route('dashboard'));

        $response->assertOk()
            ->assertSee('Groupe Solo')
            ->assertSee('Séance Solo')
            ->assertDontSee('Groupe Alpha')
            ->assertDontSee('Groupe Bravo');
    }

    // ── 2 & 7. Mes classes — scénarios mono-classe, multi-classes,
    //    multi-domaines et sans classe ────────────────────────────────────────

    public function test_classes_list_only_assigned_for_single_class_teacher(): void
    {
        $response = $this->actingAs($this->soloUser)->get(route('teacher.classes'));

        $response->assertOk()
            ->assertSee('Groupe Solo')
            ->assertSee('Formation Santé Berlin')
            ->assertDontSee('Groupe Alpha')
            ->assertDontSee('Groupe Bravo')
            ->assertDontSee('Groupe Orpheline')
            ->assertDontSee('Groupe Munich');
    }

    public function test_multi_domain_teacher_sees_exactly_his_two_classes(): void
    {
        $response = $this->actingAs($this->multiUser)->get(route('teacher.classes'));

        $response->assertOk()
            ->assertSee('Groupe Alpha')
            ->assertSee('Groupe Bravo')
            ->assertSee('Formation Informatique Berlin')
            ->assertDontSee('Groupe Solo')
            ->assertDontSee('Groupe Orpheline')
            ->assertDontSee('Groupe Munich');
    }

    public function test_teacher_without_class_gets_empty_space(): void
    {
        $this->actingAs($this->freeUser)->get(route('teacher.classes'))
            ->assertOk()
            ->assertSee('Aucune classe affectée');

        $this->actingAs($this->freeUser)->get(route('teacher.schedule'))
            ->assertOk()
            ->assertSee('Aucune séance');

        $this->actingAs($this->freeUser)->get(route('dashboard'))->assertOk();
    }

    public function test_class_detail_denied_for_unassigned_or_foreign_center_class(): void
    {
        $this->actingAs($this->multiUser)->get(route('teacher.classes.show', $this->classAlpha))
            ->assertOk()
            ->assertSee('Chloé');

        // Classe non affectée du même centre → 403, même via l'URL directe.
        $this->actingAs($this->multiUser)->get(route('teacher.classes.show', $this->classOrpheline))
            ->assertForbidden();

        // Classe d'un autre centre → 403.
        $this->actingAs($this->multiUser)->get(route('teacher.classes.show', $this->classMunich))
            ->assertForbidden();

        // Une classe d'un autre professeur du même centre → 403.
        $this->actingAs($this->multiUser)->get(route('teacher.classes.show', $this->classSolo))
            ->assertForbidden();
    }

    public function test_class_detail_hides_students_of_other_classes(): void
    {
        $response = $this->actingAs($this->soloUser)->get(route('teacher.classes.show', $this->classSolo));

        $response->assertOk()
            ->assertSee('Alice')
            ->assertSee('Bruno')
            ->assertDontSee('Chloé')
            ->assertDontSee('Elsa')
            ->assertDontSee('Fritz');
    }

    // ── 3 & 11. Mon emploi du temps ─────────────────────────────────────────

    public function test_schedule_shows_only_own_sessions(): void
    {
        $response = $this->actingAs($this->multiUser)->get(route('teacher.schedule'));

        $response->assertOk()
            ->assertSee('Séance Alpha')
            ->assertSee('Séance Bravo')
            ->assertSee('Groupe Alpha')
            ->assertDontSee('Séance Solo')
            ->assertDontSee('Séance Orpheline')
            ->assertDontSee('Séance Munich');
    }

    public function test_schedule_class_filter_hidden_for_single_class_teacher(): void
    {
        $this->actingAs($this->soloUser)->get(route('teacher.schedule'))
            ->assertOk()
            ->assertDontSee('Toutes mes classes');

        $this->actingAs($this->multiUser)->get(route('teacher.schedule'))
            ->assertOk()
            ->assertSee('Toutes mes classes');
    }

    public function test_schedule_class_filter_rejects_foreign_class_id(): void
    {
        $this->actingAs($this->multiUser)->get(route('teacher.schedule', ['class_id' => $this->classOrpheline->id]))
            ->assertForbidden();

        $this->actingAs($this->multiUser)->get(route('teacher.schedule', ['class_id' => $this->classMunich->id]))
            ->assertForbidden();
    }

    // ── 4. Présences ────────────────────────────────────────────────────────

    public function test_attendance_index_limited_to_assigned_classes(): void
    {
        $response = $this->actingAs($this->multiUser)->get(route('attendances.index'));

        $response->assertOk()
            ->assertSee('Groupe Alpha')
            ->assertSee('Groupe Bravo')
            ->assertDontSee('Groupe Solo')
            ->assertDontSee('Groupe Orpheline')
            ->assertDontSee('Groupe Munich');
    }

    public function test_attendance_class_filter_hidden_for_single_class_teacher(): void
    {
        $this->actingAs($this->soloUser)->get(route('attendances.index'))
            ->assertOk()
            ->assertSee('Groupe Solo')
            ->assertDontSee('Toutes les classes')
            ->assertDontSee('Toutes mes classes');

        $this->actingAs($this->multiUser)->get(route('attendances.index'))
            ->assertOk()
            ->assertSee('Toutes mes classes');
    }

    public function test_attendance_create_only_offers_own_scheduled_sessions(): void
    {
        $response = $this->actingAs($this->multiUser)->get(route('attendances.create'));

        // Le formulaire affiche `date · horaires · nom de la classe`.
        $response->assertOk()
            ->assertSee('Groupe Alpha')
            ->assertSee('Groupe Bravo')
            ->assertDontSee('Groupe Solo')
            ->assertDontSee('Groupe Orpheline')
            ->assertDontSee('Groupe Munich');
    }

    public function test_attendance_entry_denied_for_foreign_schedule_via_url(): void
    {
        // Séance d'un autre professeur du même centre.
        $this->actingAs($this->multiUser)->get(route('attendances.create', ['schedule_id' => $this->scheduleSolo->id]))
            ->assertForbidden();

        // Séance d'un autre centre.
        $this->actingAs($this->multiUser)->get(route('attendances.create', ['schedule_id' => $this->scheduleMunich->id]))
            ->assertForbidden();

        // JSON des étudiants de la séance : refusé hors périmètre.
        $this->actingAs($this->multiUser)->getJson(route('attendances.students-for-schedule', ['schedule_id' => $this->scheduleMunich->id]))
            ->assertForbidden();

        $this->actingAs($this->multiUser)->getJson(route('attendances.students-for-schedule', ['schedule_id' => $this->scheduleAlpha->id]))
            ->assertOk()
            ->assertJsonPath('students.0.last_name', 'Alpha');
    }

    public function test_teacher_can_save_and_update_attendance_for_own_session(): void
    {
        $response = $this->actingAs($this->multiUser)->post(route('attendances.store-session'), [
            'schedule_id' => $this->scheduleAlpha->id,
            'attendances' => [
                ['student_id' => $this->studentAlpha->id, 'status' => 'present'],
            ],
        ]);

        $response->assertRedirect(route('attendances.index'));
        $this->assertDatabaseHas('attendances', [
            'schedule_id' => $this->scheduleAlpha->id,
            'student_id' => $this->studentAlpha->id,
            'status' => 'present',
        ]);
    }

    public function test_attendance_save_denied_for_unassigned_session(): void
    {
        $this->actingAs($this->multiUser)->post(route('attendances.store-session'), [
            'schedule_id' => $this->scheduleOrpheline->id,
            'attendances' => [
                ['student_id' => $this->studentOrpheline->id, 'status' => 'present'],
            ],
        ])->assertForbidden();

        $this->assertDatabaseMissing('attendances', [
            'schedule_id' => $this->scheduleOrpheline->id,
            'student_id' => $this->studentOrpheline->id,
            'status' => 'present',
        ]);

        $this->actingAs($this->multiUser)->post(route('attendances.store-session'), [
            'schedule_id' => $this->scheduleMunich->id,
            'attendances' => [
                ['student_id' => $this->studentMunich->id, 'status' => 'present'],
            ],
        ])->assertForbidden();
    }

    public function test_attendance_show_denied_for_foreign_attendance(): void
    {
        $foreign = Attendance::where('class_id', $this->classMunich->id)->first();

        $this->actingAs($this->multiUser)->get(route('attendances.show', $foreign))
            ->assertForbidden();
    }

    // ── 5. Évaluations & notes ──────────────────────────────────────────────

    public function test_evaluations_index_limited_to_own_evaluations(): void
    {
        $response = $this->actingAs($this->multiUser)->get(route('evaluations.index'));

        $response->assertOk()
            ->assertSee('Eval Alpha')
            ->assertSee('Eval Bravo')
            ->assertDontSee('Eval Solo')
            ->assertDontSee('Eval Orpheline')
            ->assertDontSee('Eval Munich');
    }

    public function test_evaluation_create_form_only_lists_assigned_classes(): void
    {
        $response = $this->actingAs($this->multiUser)->get(route('evaluations.create'));

        $response->assertOk()
            ->assertSee('Groupe Alpha')
            ->assertSee('Groupe Bravo')
            ->assertDontSee('Groupe Solo')
            ->assertDontSee('Groupe Orpheline')
            ->assertDontSee('Groupe Munich');
    }

    public function test_evaluation_creation_allowed_only_for_assigned_class(): void
    {
        $payload = [
            'class_id' => $this->classAlpha->id,
            'title' => 'Contrôle Surprise',
            'type' => 'control',
            'evaluation_date' => now()->toDateString(),
            'max_grade' => 20,
            'coefficient' => 1,
            'status' => 'draft',
            // tentative d'usurpation d'identité professeur
            'teacher_id' => $this->teacherFree->id,
        ];

        $response = $this->actingAs($this->multiUser)->post(route('evaluations.store'), $payload);
        $response->assertRedirect();

        $evaluation = Evaluation::where('title', 'Contrôle Surprise')->first();
        $this->assertNotNull($evaluation);
        // teacher_id est dérivé de l'utilisateur authentifié, jamais du navigateur.
        $this->assertSame($this->teacherMulti->id, (int) $evaluation->teacher_id);
        $this->assertSame($this->formationSante->id, (int) $evaluation->formation_id);

        // Classe non attribuée du même centre → 403.
        $this->actingAs($this->multiUser)->post(route('evaluations.store'), array_merge($payload, [
            'class_id' => $this->classOrpheline->id,
            'title' => 'Eval Interdite',
        ]))->assertForbidden();

        // Classe d'un autre centre → 403.
        $this->actingAs($this->multiUser)->post(route('evaluations.store'), array_merge($payload, [
            'class_id' => $this->classMunich->id,
            'title' => 'Eval Interdite Munich',
        ]))->assertForbidden();
    }

    public function test_grades_entry_authorized_for_own_evaluation(): void
    {
        $response = $this->actingAs($this->multiUser)->post(route('evaluations.grades.save', $this->evaluationAlpha), [
            'grades' => [
                ['student_id' => $this->studentAlpha->id, 'grade' => 14, 'comment' => 'Bon travail'],
            ],
        ]);

        $response->assertRedirect(route('evaluations.show', $this->evaluationAlpha));
        $this->assertDatabaseHas('grades', [
            'evaluation_id' => $this->evaluationAlpha->id,
            'student_id' => $this->studentAlpha->id,
            'grade' => 14,
        ]);
    }

    public function test_grades_and_edit_denied_for_foreign_evaluations(): void
    {
        // Évaluation d'un autre professeur du même centre.
        $this->actingAs($this->multiUser)->get(route('evaluations.edit', $this->evaluationSolo))
            ->assertForbidden();

        $this->actingAs($this->multiUser)->post(route('evaluations.grades.save', $this->evaluationSolo), [
            'grades' => [['student_id' => $this->studentSolo1->id, 'grade' => 20]],
        ])->assertForbidden();

        // Évaluation rattachée à une classe non attribuée (URL manipulée).
        $this->actingAs($this->multiUser)->get(route('evaluations.show', $this->evaluationOrpheline))
            ->assertForbidden();

        // Évaluation d'un autre centre.
        $this->actingAs($this->multiUser)->get(route('evaluations.show', $this->evaluationMunich))
            ->assertForbidden();

        $this->actingAs($this->multiUser)->put(route('evaluations.update', $this->evaluationMunich), [
            'title' => 'Eval Munich',
            'type' => 'exam',
            'evaluation_date' => now()->toDateString(),
            'max_grade' => 20,
            'coefficient' => 1,
            'status' => 'published',
        ])->assertForbidden();
    }

    public function test_teacher_can_publish_own_evaluation(): void
    {
        $this->actingAs($this->multiUser)->put(route('evaluations.update', $this->evaluationAlpha), [
            'title' => 'Eval Alpha',
            'type' => 'control',
            'evaluation_date' => now()->toDateString(),
            'max_grade' => 20,
            'coefficient' => 1,
            'status' => 'published',
        ])->assertRedirect();

        $this->assertSame('published', $this->evaluationAlpha->fresh()->status);
    }

    // ── 6 & 12. Sidebar et traduction FR/AR ─────────────────────────────────

    public function test_sidebar_shows_organization_requested_for_teacher(): void
    {
        $response = $this->actingAs($this->multiUser)->get(route('dashboard'));

        $response->assertOk()
            ->assertSee('Tableau de bord')
            ->assertSee('Mes classes')
            ->assertSee('Mon emploi du temps')
            ->assertSee('Présences')
            ->assertSee('Évaluations & notes')
            ->assertSee('Notifications')
            // Aucun module administratif dans la navigation professeur.
            ->assertDontSee('Paiements')
            ->assertDontSee('Factures')
            ->assertDontSee('Audit Logs');
    }

    public function test_arabic_rtl_rendering_of_teacher_pages(): void
    {
        // Le français reste LTR (vérifié avant tout bascule ?lang=ar, car
        // SetLocale mémorise la préférence sur l'utilisateur).
        $this->actingAs($this->multiUser)->get(route('teacher.classes'))
            ->assertOk()
            ->assertSee('dir="ltr"', false)
            ->assertSee('Mes classes');

        $this->actingAs($this->multiUser)->get(route('teacher.classes', ['lang' => 'ar']))
            ->assertOk()
            ->assertSee('dir="rtl"', false)
            ->assertSee('فصولي');

        $this->actingAs($this->multiUser)->get(route('teacher.schedule', ['lang' => 'ar']))
            ->assertOk()
            ->assertSee('dir="rtl"', false)
            ->assertSee('جدول مواعيدي');
    }

    // ── 13. Non-régression des autres rôles ─────────────────────────────────

    public function test_center_admin_still_sees_all_center_data(): void
    {
        $adminUser = User::factory()->centerAdmin($this->berlin->id)->create(['name' => 'Admin Berlin']);

        $this->actingAs($adminUser)->get(route('attendances.index'))
            ->assertOk()
            ->assertSee('Groupe Alpha')
            ->assertSee('Groupe Orpheline')
            ->assertSee('Toutes les classes')
            ->assertDontSee('Groupe Munich');

        $this->actingAs($adminUser)->get(route('evaluations.index'))
            ->assertOk()
            ->assertSee('Eval Alpha')
            ->assertSee('Eval Orpheline')
            ->assertDontSee('Eval Munich');
    }

    public function test_teacher_cannot_access_admin_or_portal_of_other_roles(): void
    {
        // Modules administratifs inaccessibles au professeur.
        $this->actingAs($this->multiUser)->get(route('classes.index'))->assertForbidden();
        $this->actingAs($this->multiUser)->get(route('teachers.index'))->assertForbidden();

        // Le portail élève n'est pas pour les professeurs.
        $this->actingAs($this->multiUser)->get(route('student.formations'))->assertForbidden();
    }

    public function test_center_admin_cannot_access_teacher_portal_routes(): void
    {
        $adminUser = User::factory()->centerAdmin($this->berlin->id)->create(['name' => 'Admin Berlin']);

        $this->actingAs($adminUser)->get(route('teacher.classes'))->assertForbidden();
        $this->actingAs($adminUser)->get(route('teacher.schedule'))->assertForbidden();
    }

    public function test_super_admin_dashboard_redirection_unchanged(): void
    {
        $superAdmin = User::factory()->superAdmin()->create();

        // L'accueil commun (4 cartes) est la destination depuis la refonte du dashboard
        $this->actingAs($superAdmin)->get(route('dashboard'))
            ->assertRedirect(route('admin.home'));
    }

    public function test_student_portal_data_still_scoped_to_student(): void
    {
        $studentUser = $this->studentSolo1->user;

        $this->actingAs($studentUser)->get(route('student.evaluations'))
            ->assertOk()
            ->assertSee('Eval Solo')
            ->assertDontSee('Eval Alpha')
            ->assertDontSee('Eval Munich');
    }

    // ── Helpers (mêmes conventions que StudentPortalTest) ───────────────────

    private function makeTeacherUser(Center $center, string $name): User
    {
        return User::factory()->teacher($center->id)->create([
            'name' => $name,
            'status' => 'active',
        ]);
    }

    private function makeTeacher(User $user, Center $center, string $number, string $firstName, string $lastName): Teacher
    {
        return Teacher::create([
            'user_id' => $user->id,
            'center_id' => $center->id,
            'teacher_number' => $number,
            'first_name' => $firstName,
            'last_name' => $lastName,
            'status' => 'active',
        ]);
    }

    private function makeFormation(Center $center, string $domain, string $name, string $code): Formation
    {
        return Formation::create([
            'center_id' => $center->id,
            'domain' => $domain,
            'name' => $name,
            'code' => $code,
            'duration_weeks' => 24,
            'duration_hours' => 600,
            'price' => 12000,
            'currency' => 'MAD',
            'status' => 'active',
        ]);
    }

    private function makeClass(Formation $formation, string $name, string $code): ClassRoom
    {
        return ClassRoom::create([
            'center_id' => $formation->center_id,
            'formation_id' => $formation->id,
            'name' => $name,
            'code' => $code,
            'capacity' => 20,
            'start_date' => now()->subMonth()->toDateString(),
            'end_date' => now()->addMonths(6)->toDateString(),
            'status' => 'active',
        ]);
    }

    private function makeStudent(Center $center, string $number, string $firstName, string $lastName): Student
    {
        $user = User::factory()->student($center->id)->create([
            'name' => $firstName.' '.$lastName,
            'status' => 'active',
        ]);

        return Student::create([
            'user_id' => $user->id,
            'center_id' => $center->id,
            'student_number' => $number,
            'first_name' => $firstName,
            'last_name' => $lastName,
            'status' => 'active',
        ]);
    }

    private function makeEnrollment(Student $student, Formation $formation, ClassRoom $classRoom, string $registrationNumber): Enrollment
    {
        return Enrollment::create([
            'center_id' => $student->center_id,
            'student_id' => $student->id,
            'formation_id' => $formation->id,
            'class_id' => $classRoom->id,
            'registration_number' => $registrationNumber,
            'application_date' => now()->subWeeks(2)->toDateString(),
            'status' => 'accepted',
            'accepted_at' => now()->subWeek(),
        ]);
    }

    private function makeSchedule(ClassRoom $classRoom, Teacher $teacher, Room $room, string $title, string $date, string $type): Schedule
    {
        return Schedule::create([
            'center_id' => $classRoom->center_id,
            'class_id' => $classRoom->id,
            'teacher_id' => $teacher->id,
            'room_id' => $room->id,
            'date' => $date,
            'start_time' => '09:00',
            'end_time' => '11:00',
            'title' => $title,
            'type' => $type,
            'status' => 'scheduled',
        ]);
    }

    private function makeAttendance(Schedule $schedule, Student $student, string $status): Attendance
    {
        return Attendance::create([
            'center_id' => $schedule->center_id,
            'student_id' => $student->id,
            'class_id' => $schedule->class_id,
            'schedule_id' => $schedule->id,
            'date' => $schedule->date,
            'status' => $status,
        ]);
    }

    private function makeEvaluation(ClassRoom $classRoom, Teacher $teacher, string $title, string $type, string $status): Evaluation
    {
        return Evaluation::create([
            'center_id' => $classRoom->center_id,
            'class_id' => $classRoom->id,
            'formation_id' => $classRoom->formation_id,
            'teacher_id' => $teacher->id,
            'title' => $title,
            'type' => $type,
            'evaluation_date' => now()->subDays(2)->toDateString(),
            'max_grade' => 20,
            'coefficient' => 1,
            'description' => 'Description '.$title,
            'status' => $status,
        ]);
    }

    private function makeGrade(Evaluation $evaluation, Student $student, float $grade, string $comment): Grade
    {
        return Grade::create([
            'center_id' => $evaluation->center_id,
            'evaluation_id' => $evaluation->id,
            'student_id' => $student->id,
            'grade' => $grade,
            'comment' => $comment,
            'graded_at' => now()->subDay()->toDateString(),
            'is_absent' => false,
        ]);
    }
}
