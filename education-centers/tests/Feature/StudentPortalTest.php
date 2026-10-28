<?php

namespace Tests\Feature;

use App\Models\AppNotification;
use App\Models\Attendance;
use App\Models\Certificate;
use App\Models\Center;
use App\Models\ClassRoom;
use App\Models\Document;
use App\Models\Enrollment;
use App\Models\Evaluation;
use App\Models\Formation;
use App\Models\Grade;
use App\Models\Room;
use App\Models\Schedule;
use App\Models\Student;
use App\Models\Teacher;
use App\Models\User;
use App\Services\CertificateService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

/**
 * Portail Élève : dashboard, formations, planning, présences, notes,
 * documents et certificats. Vérifie l'isolation personnelle et Berlin/Munich,
 * les filtres, les états vides et les PDF réels.
 */
class StudentPortalTest extends TestCase
{
    use RefreshDatabase;

    private Center $berlin;

    private Center $munich;

    private User $studentUser;

    private Student $student;

    private User $otherStudentUser;

    private Student $otherStudent;

    private User $munichUser;

    private Student $munichStudent;

    private Teacher $teacherBerlin;

    private Teacher $teacherMunich;

    private Room $roomBerlin;

    private Room $roomMunich;

    private Formation $formationSante;

    private Formation $formationInformatique;

    private Formation $formationPending;

    private Formation $formationMunich;

    private ClassRoom $classSante;

    private ClassRoom $classInformatique;

    private ClassRoom $classSansInscription;

    private ClassRoom $classMunich;

    private Schedule $scheduleSante;

    private Schedule $scheduleInformatique;

    private Schedule $scheduleSansInscription;

    private Schedule $scheduleMunich;

    private Evaluation $publishedEvaluation;

    private Evaluation $closedEvaluation;

    private Evaluation $draftEvaluation;

    private Evaluation $munichEvaluation;

    private Document $mainPdf;

    private Document $mainWord;

    private Document $otherPdf;

    private Document $archivedPdf;

    private Document $munichPdf;

    private Certificate $mainCertificate;

    private Certificate $draftCertificate;

    private Certificate $otherCertificate;

    private Certificate $munichCertificate;

    protected function setUp(): void
    {
        parent::setUp();

        Storage::fake('private');

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

        $this->teacherBerlin = $this->makeTeacher($this->berlin, 'BER-T-1', 'Johannes', 'Kohl');
        $this->teacherMunich = $this->makeTeacher($this->munich, 'MUN-T-1', 'Greta', 'Schneider');

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

        $this->formationSante = $this->makeFormation($this->berlin, 'sante', 'Formation Berlin Santé', 'BER-SAN');
        $this->formationInformatique = $this->makeFormation($this->berlin, 'informatique', 'Formation Berlin Informatique', 'BER-INFO');
        $this->formationPending = $this->makeFormation($this->berlin, 'hotellerie', 'Formation Berlin Hôtellerie', 'BER-HOT');
        $this->formationMunich = $this->makeFormation($this->munich, 'sante', 'Formation Munich Santé', 'MUN-SAN');

        $this->classSante = $this->makeClass($this->formationSante, 'Groupe Santé A', 'BER-SAN-A');
        $this->classInformatique = $this->makeClass($this->formationInformatique, 'Groupe Informatique A', 'BER-INFO-A');
        $this->classSansInscription = $this->makeClass($this->formationSante, 'Groupe Sans Inscription', 'BER-SAN-Z');
        $this->classMunich = $this->makeClass($this->formationMunich, 'Groupe Munich A', 'MUN-SAN-A');

        $this->classSante->teachers()->attach($this->teacherBerlin->id);
        $this->classInformatique->teachers()->attach($this->teacherBerlin->id);
        $this->classMunich->teachers()->attach($this->teacherMunich->id);

        $this->studentUser = User::factory()->student($this->berlin->id)->create([
            'name' => 'Élève Berlin',
            'email' => 'eleve.berlin@example.test',
            'status' => 'active',
        ]);
        $this->student = Student::create([
            'user_id' => $this->studentUser->id,
            'center_id' => $this->berlin->id,
            'student_number' => 'BER-STU-001',
            'first_name' => 'Élève',
            'last_name' => 'Berlin',
            'status' => 'active',
        ]);

        $this->otherStudentUser = User::factory()->student($this->berlin->id)->create([
            'name' => 'Autre Élève Berlin',
            'email' => 'autre.berlin@example.test',
            'status' => 'active',
        ]);
        $this->otherStudent = Student::create([
            'user_id' => $this->otherStudentUser->id,
            'center_id' => $this->berlin->id,
            'student_number' => 'BER-STU-002',
            'first_name' => 'Autre',
            'last_name' => 'Élève',
            'status' => 'active',
        ]);

        $this->munichUser = User::factory()->student($this->munich->id)->create([
            'name' => 'Élève Munich',
            'email' => 'eleve.munich@example.test',
            'status' => 'active',
        ]);
        $this->munichStudent = Student::create([
            'user_id' => $this->munichUser->id,
            'center_id' => $this->munich->id,
            'student_number' => 'MUN-STU-001',
            'first_name' => 'Élève',
            'last_name' => 'Munich',
            'status' => 'active',
        ]);

        $this->makeEnrollment($this->student, $this->formationSante, $this->classSante, 'BER-INS-001', 'accepted');
        $this->makeEnrollment($this->student, $this->formationInformatique, $this->classInformatique, 'BER-INS-002', 'accepted');
        $this->makeEnrollment($this->student, $this->formationPending, null, 'BER-INS-003', 'pending');
        $this->makeEnrollment($this->otherStudent, $this->formationSante, $this->classSante, 'BER-INS-004', 'accepted');
        $this->makeEnrollment($this->munichStudent, $this->formationMunich, $this->classMunich, 'MUN-INS-001', 'accepted');

        $this->scheduleSante = $this->makeSchedule(
            $this->classSante,
            $this->teacherBerlin,
            $this->roomBerlin,
            'Cours Berlin A',
            now()->addDays(2)->toDateString(),
            'course'
        );
        $this->scheduleInformatique = $this->makeSchedule(
            $this->classInformatique,
            $this->teacherBerlin,
            $this->roomBerlin,
            'Atelier Berlin B',
            now()->addDays(3)->toDateString(),
            'workshop'
        );
        $this->scheduleSansInscription = $this->makeSchedule(
            $this->classSansInscription,
            $this->teacherBerlin,
            $this->roomBerlin,
            'Séance autre classe secrète',
            now()->addDays(4)->toDateString(),
            'course'
        );
        $this->scheduleMunich = $this->makeSchedule(
            $this->classMunich,
            $this->teacherMunich,
            $this->roomMunich,
            'Cours Munich secret',
            now()->addDays(5)->toDateString(),
            'course'
        );

        $pastSchedules = [
            $this->makeSchedule($this->classSante, $this->teacherBerlin, $this->roomBerlin, 'Séance présence 1', now()->subDays(1)->toDateString(), 'course'),
            $this->makeSchedule($this->classSante, $this->teacherBerlin, $this->roomBerlin, 'Séance présence 2', now()->subDays(2)->toDateString(), 'course'),
            $this->makeSchedule($this->classSante, $this->teacherBerlin, $this->roomBerlin, 'Séance présence 3', now()->subDays(3)->toDateString(), 'course'),
            $this->makeSchedule($this->classSante, $this->teacherBerlin, $this->roomBerlin, 'Séance autre étudiant', now()->subDays(4)->toDateString(), 'course'),
            $this->makeSchedule($this->classMunich, $this->teacherMunich, $this->roomMunich, 'Séance Munich', now()->subDays(1)->toDateString(), 'course'),
        ];

        Attendance::create([
            'center_id' => $this->berlin->id,
            'student_id' => $this->student->id,
            'class_id' => $this->classSante->id,
            'schedule_id' => $pastSchedules[0]->id,
            'date' => $pastSchedules[0]->date,
            'status' => 'present',
            'comment' => 'COMMENT-PRESENT',
        ]);
        Attendance::create([
            'center_id' => $this->berlin->id,
            'student_id' => $this->student->id,
            'class_id' => $this->classSante->id,
            'schedule_id' => $pastSchedules[1]->id,
            'date' => $pastSchedules[1]->date,
            'status' => 'late',
            'late_minutes' => 15,
            'comment' => 'COMMENT-LATE',
        ]);
        Attendance::create([
            'center_id' => $this->berlin->id,
            'student_id' => $this->student->id,
            'class_id' => $this->classInformatique->id,
            'schedule_id' => $pastSchedules[2]->id,
            'date' => $pastSchedules[2]->date,
            'status' => 'absent',
            'comment' => 'COMMENT-ABSENT',
        ]);
        Attendance::create([
            'center_id' => $this->berlin->id,
            'student_id' => $this->otherStudent->id,
            'class_id' => $this->classSante->id,
            'schedule_id' => $pastSchedules[3]->id,
            'date' => $pastSchedules[3]->date,
            'status' => 'absent',
            'comment' => 'COMMENT-SECRET',
        ]);
        Attendance::create([
            'center_id' => $this->munich->id,
            'student_id' => $this->munichStudent->id,
            'class_id' => $this->classMunich->id,
            'schedule_id' => $pastSchedules[4]->id,
            'date' => $pastSchedules[4]->date,
            'status' => 'present',
            'comment' => 'COMMENT-MUNICH',
        ]);

        $this->publishedEvaluation = $this->makeEvaluation($this->classSante, $this->teacherBerlin, 'Examen Berlin', 'exam', 'published');
        $this->closedEvaluation = $this->makeEvaluation($this->classInformatique, $this->teacherBerlin, 'Projet Berlin', 'project', 'closed');
        $this->draftEvaluation = $this->makeEvaluation($this->classSante, $this->teacherBerlin, 'Brouillon secret', 'exam', 'draft');
        $this->munichEvaluation = $this->makeEvaluation($this->classMunich, $this->teacherMunich, 'Examen Munich secret', 'exam', 'published');

        $this->makeGrade($this->publishedEvaluation, $this->student, 15, 'COMMENT-NOTE-15');
        $this->makeGrade($this->publishedEvaluation, $this->otherStudent, 8, 'COMMENT-AUTRE-8');
        $this->makeGrade($this->closedEvaluation, $this->student, 12, 'COMMENT-NOTE-12');
        $this->makeGrade($this->draftEvaluation, $this->student, 19, 'COMMENT-BROUILLON-19');
        $this->makeGrade($this->munichEvaluation, $this->munichStudent, 18, 'COMMENT-MUNICH-18');

        $this->mainPdf = $this->makeDocument($this->student, 'Document Berlin', 'administrative', 'documents/main.pdf', 'main.pdf', 'application/pdf');
        $this->mainWord = $this->makeDocument($this->student, 'Document Berlin Word', 'diploma', 'documents/main.docx', 'main.docx', 'application/vnd.openxmlformats-officedocument.wordprocessingml.document');
        $this->otherPdf = $this->makeDocument($this->otherStudent, 'Document Secret', 'other', 'documents/secret.pdf', 'secret.pdf', 'application/pdf');
        $this->archivedPdf = $this->makeDocument($this->student, 'Document Archive', 'other', 'documents/archive.pdf', 'archive.pdf', 'application/pdf', 'archived');
        $this->munichPdf = $this->makeDocument($this->munichStudent, 'Document Munich', 'other', 'documents/munich.pdf', 'munich.pdf', 'application/pdf');

        Storage::disk('private')->put($this->mainPdf->file_path, '%PDF-1.4 DOCUMENT-BERLIN');
        Storage::disk('private')->put($this->mainWord->file_path, 'DOCX-BERLIN');
        Storage::disk('private')->put($this->otherPdf->file_path, '%PDF-1.4 DOCUMENT-SECRET');
        Storage::disk('private')->put($this->archivedPdf->file_path, '%PDF-1.4 DOCUMENT-ARCHIVE');
        Storage::disk('private')->put($this->munichPdf->file_path, '%PDF-1.4 DOCUMENT-MUNICH');

        $this->mainCertificate = $this->makeCertificate(
            $this->student,
            $this->formationSante,
            'CERT-BER-000001',
            'Certificat Berlin',
            'completion',
            'issued',
            'certificates/main.pdf'
        );
        $this->draftCertificate = $this->makeCertificate(
            $this->student,
            $this->formationSante,
            'CERT-BER-000002',
            'Certificat Brouillon',
            'other',
            'draft',
            null
        );
        $this->otherCertificate = $this->makeCertificate(
            $this->otherStudent,
            $this->formationSante,
            'CERT-BER-000003',
            'Certificat Secret',
            'enrollment',
            'issued',
            'certificates/secret.pdf'
        );
        $this->munichCertificate = $this->makeCertificate(
            $this->munichStudent,
            $this->formationMunich,
            'CERT-MUN-000001',
            'Certificat Munich',
            'completion',
            'issued',
            'certificates/munich.pdf'
        );

        Storage::disk('private')->put($this->mainCertificate->file_path, '%PDF-1.4 CERTIFICATE-BERLIN');
        Storage::disk('private')->put($this->otherCertificate->file_path, '%PDF-1.4 CERTIFICATE-SECRET');
        Storage::disk('private')->put($this->munichCertificate->file_path, '%PDF-1.4 CERTIFICATE-MUNICH');

        AppNotification::create([
            'user_id' => $this->studentUser->id,
            'center_id' => $this->berlin->id,
            'type' => 'grade',
            'title' => 'Notification Berlin',
            'message' => 'Votre note a été publiée.',
        ]);
        AppNotification::create([
            'user_id' => $this->otherStudentUser->id,
            'center_id' => $this->berlin->id,
            'type' => 'grade',
            'title' => 'Notification Secret',
            'message' => 'Notification d’un autre étudiant.',
        ]);
        AppNotification::create([
            'user_id' => $this->munichUser->id,
            'center_id' => $this->munich->id,
            'type' => 'grade',
            'title' => 'Notification Munich',
            'message' => 'Notification de Munich.',
        ]);
    }

    /**
     * MISSION — Gestion des utilisateurs :
     * Les élèves ne peuvent plus se connecter à l'application.
     * Ils restent des dossiers administratifs sans compte d'accès.
     */
    public function test_student_cannot_log_in_and_is_blocked_at_login(): void
    {
        $response = $this->post('/login', [
            'email' => $this->studentUser->email,
            'password' => 'password',
        ]);

        // La connexion doit être refusée : redirection vers la page d'accueil avec erreur
        $this->assertGuest();
        $response->assertSessionHasErrors(['email']);
    }

    public function test_dashboard_contains_only_the_authenticated_students_academic_data(): void
    {
        $this->actingAs($this->studentUser)
            ->get(route('dashboard'))
            ->assertOk()
            ->assertSee('Cours Berlin A')
            // Le tableau de bord n'affiche que la prochaine séance : 'Atelier
            // Berlin B' (+3 jours) est visible sur la page emploi du temps.
            ->assertSee('66,7 %')
            ->assertSee('13,50 / 20')
            ->assertSee('Examen Berlin')
            ->assertSee('Projet Berlin')
            ->assertSee('Notification Berlin')
            ->assertSee('Document Berlin')
            ->assertSee('Certificat Berlin')
            ->assertDontSee('Cours Munich secret')
            ->assertDontSee('Séance autre classe secrète')
            ->assertDontSee('Brouillon secret')
            ->assertDontSee('Notification Secret')
            ->assertDontSee('Document Secret')
            ->assertDontSee('Certificat Secret')
            ->assertDontSee('Certificat Munich');
    }

    public function test_student_without_profile_or_enrollment_sees_clean_empty_states(): void
    {
        $emptyUser = User::factory()->student($this->berlin->id)->create([
            'name' => 'Élève Sans Dossier',
            'email' => 'sans.dossier@example.test',
            'status' => 'active',
        ]);

        $this->actingAs($emptyUser);

        $this->get(route('dashboard'))
            ->assertOk()
            ->assertSee('Élève Sans Dossier')
            ->assertSee('Votre dossier étudiant');

        $this->get(route('student.formations'))
            ->assertOk()
            ->assertSee('Aucune formation');

        $this->get(route('student.schedule'))
            ->assertOk()
            ->assertSee('Aucune séance');

        $this->get(route('student.attendances'))
            ->assertOk()
            ->assertSee('Aucune présence enregistrée');

        $this->get(route('student.evaluations'))
            ->assertOk()
            ->assertSee('Aucune note publiée');

        $this->get(route('student.documents'))
            ->assertOk()
            ->assertSee('Aucun document');

        $this->get(route('student.certificates'))
            ->assertOk()
            ->assertSee('Aucun certificat');
    }

    public function test_formations_page_lists_all_personal_enrollments_and_multiple_domains(): void
    {
        $this->actingAs($this->studentUser)
            ->get(route('student.formations'))
            ->assertOk()
            ->assertSee('Formation Berlin Santé')
            ->assertSee('Formation Berlin Informatique')
            ->assertSee('Formation Berlin Hôtellerie')
            ->assertSee('Groupe Santé A')
            ->assertSee('Groupe Informatique A')
            ->assertSee('Johannes Kohl')
            ->assertSee('Acceptée')
            ->assertSee('En attente')
            ->assertSee('BER-INS-001')
            ->assertSee('BER-INS-003')
            ->assertDontSee('Formation Munich Santé')
            ->assertDontSee('Groupe Munich A');
    }

    public function test_schedule_contains_only_own_classes_and_validates_filters(): void
    {
        $this->actingAs($this->studentUser)
            ->get(route('student.schedule'))
            ->assertOk()
            ->assertSee('Cours Berlin A')
            ->assertSee('Atelier Berlin B')
            ->assertSee('Toutes mes classes')
            ->assertDontSee('Cours Munich secret')
            ->assertDontSee('Séance autre classe secrète');

        $this->get(route('student.schedule', ['class_id' => $this->classSante->id]))
            ->assertOk()
            ->assertSee('Cours Berlin A')
            ->assertDontSee('Atelier Berlin B');

        $this->get(route('student.schedule', ['formation_id' => $this->formationInformatique->id]))
            ->assertOk()
            ->assertSee('Atelier Berlin B')
            ->assertDontSee('Cours Berlin A');

        $this->get(route('student.schedule', ['class_id' => $this->classSansInscription->id]))
            ->assertForbidden();

        $this->get(route('student.schedule', ['formation_id' => $this->formationMunich->id]))
            ->assertForbidden();

        $this->get(route('student.schedule', ['student_id' => $this->otherStudent->id]))
            ->assertForbidden();
    }

    public function test_attendance_page_only_exposes_authenticated_student_records(): void
    {
        $this->actingAs($this->studentUser)
            ->get(route('student.attendances'))
            ->assertOk()
            ->assertSee('COMMENT-PRESENT')
            ->assertSee('COMMENT-LATE')
            ->assertSee('COMMENT-ABSENT')
            ->assertSee('66,7 %')
            ->assertDontSee('COMMENT-SECRET')
            ->assertDontSee('COMMENT-MUNICH');

        $this->get(route('student.attendances', ['status' => 'late']))
            ->assertOk()
            ->assertSee('COMMENT-LATE')
            ->assertDontSee('COMMENT-PRESENT')
            ->assertDontSee('COMMENT-ABSENT');

        $this->get(route('student.attendances', ['class_id' => $this->classSansInscription->id]))
            ->assertForbidden();
    }

    public function test_notes_only_show_published_evaluations_and_personal_grades(): void
    {
        $this->actingAs($this->studentUser)
            ->get(route('student.evaluations'))
            ->assertOk()
            ->assertSee('Examen Berlin')
            ->assertSee('Projet Berlin')
            ->assertSee('15,00')
            ->assertSee('12,00')
            ->assertSee('13,50 / 20')
            ->assertSee('11,50 / 20')
            ->assertSee('COMMENT-NOTE-15')
            ->assertSee('COMMENT-NOTE-12')
            ->assertDontSee('Brouillon secret')
            ->assertDontSee('COMMENT-BROUILLON-19')
            ->assertDontSee('Examen Munich secret')
            ->assertDontSee('COMMENT-AUTRE-8')
            ->assertDontSee('COMMENT-MUNICH-18');

        $this->get(route('student.evaluations', ['class_id' => $this->classSante->id]))
            ->assertOk()
            ->assertSee('Examen Berlin')
            ->assertDontSee('Projet Berlin');

        $this->get(route('student.evaluations', ['type' => 'project']))
            ->assertOk()
            ->assertSee('Projet Berlin')
            ->assertDontSee('Examen Berlin');

        $this->get(route('student.evaluations', ['class_id' => $this->classSansInscription->id]))
            ->assertForbidden();
    }

    public function test_notes_class_filter_is_hidden_when_student_has_a_single_class(): void
    {
        $this->actingAs($this->otherStudentUser)
            ->get(route('student.evaluations'))
            ->assertOk()
            ->assertSee('Examen Berlin')
            ->assertSee('8,00')
            ->assertDontSee('Toutes mes classes')
            ->assertDontSee('Brouillon secret')
            ->assertDontSee('Examen Munich secret');
    }

    public function test_foreign_student_id_parameter_is_rejected(): void
    {
        $this->actingAs($this->studentUser)
            ->get(route('student.evaluations', ['student_id' => $this->otherStudent->id]))
            ->assertForbidden();

        $this->get(route('student.evaluations', ['student_id' => $this->student->id]))
            ->assertOk()
            ->assertSee('Examen Berlin')
            ->assertDontSee('COMMENT-AUTRE-8');
    }

    public function test_documents_are_scoped_downloadable_and_preview_only_for_supported_formats(): void
    {
        $this->actingAs($this->studentUser);

        $this->get(route('student.documents'))
            ->assertOk()
            ->assertSee('Document Berlin')
            ->assertSee('Document Berlin Word')
            ->assertDontSee('Document Secret')
            ->assertDontSee('Document Archive')
            ->assertDontSee('Document Munich');

        $this->get(route('student.documents', ['type' => 'administrative']))
            ->assertOk()
            ->assertSee('Document Berlin')
            ->assertDontSee('Document Berlin Word');

        $this->get(route('student.documents.download', $this->mainPdf))
            ->assertOk()
            ->assertDownload('main.pdf');

        $preview = $this->get(route('student.documents.preview', $this->mainPdf));
        $preview->assertOk()
            ->assertHeader('Content-Type', 'application/pdf');
        $this->assertStringContainsString('DOCUMENT-BERLIN', $preview->getContent());

        $this->get(route('student.documents.preview', $this->mainWord))
            ->assertStatus(415);

        $this->get(route('student.documents.download', $this->otherPdf))
            ->assertForbidden();

        $this->get(route('student.documents.preview', $this->otherPdf))
            ->assertForbidden();

        $this->get(route('student.documents.download', $this->archivedPdf))
            ->assertForbidden();

        $this->get(route('student.documents', ['student_id' => $this->otherStudent->id]))
            ->assertForbidden();
    }

    public function test_certificates_are_scoped_and_use_the_students_own_pdf_routes(): void
    {
        $this->actingAs($this->studentUser);

        $this->get(route('student.certificates'))
            ->assertOk()
            ->assertSee('Certificat Berlin')
            ->assertDontSee('Certificat Brouillon')
            ->assertDontSee('Certificat Secret')
            ->assertDontSee('Certificat Munich');

        $this->get(route('student.certificates', ['type' => 'completion']))
            ->assertOk()
            ->assertSee('Certificat Berlin')
            ->assertDontSee('Certificat Brouillon');

        $this->get(route('student.certificates.download', $this->mainCertificate))
            ->assertOk()
            ->assertDownload($this->mainCertificate->certificate_number.'.pdf');

        $this->get(route('student.certificates.download', $this->otherCertificate))
            ->assertForbidden();

        $this->get(route('student.certificates.print', $this->otherCertificate))
            ->assertForbidden();

        $this->get(route('student.certificates.download', $this->draftCertificate))
            ->assertForbidden();

        $this->get(route('student.certificates', ['student_id' => $this->otherStudent->id]))
            ->assertForbidden();
    }

    public function test_certificate_print_generates_a_real_pdf_for_the_owner(): void
    {
        app(CertificateService::class)->generate($this->mainCertificate);
        $this->mainCertificate->refresh();

        $response = $this->actingAs($this->studentUser)
            ->get(route('student.certificates.print', $this->mainCertificate));

        $response->assertOk()
            ->assertHeader('Content-Type', 'application/pdf');

        $this->assertStringContainsString('inline;', (string) $response->headers->get('Content-Disposition'));
        $this->assertStringStartsWith('%PDF', $response->getContent());
    }

    public function test_berlin_and_munich_students_never_see_each_others_data(): void
    {
        $this->actingAs($this->studentUser);

        $this->get(route('student.schedule'))->assertOk()->assertDontSee('Cours Munich secret');
        $this->get(route('student.attendances'))->assertOk()->assertDontSee('COMMENT-MUNICH');
        $this->get(route('student.evaluations'))->assertOk()->assertDontSee('Examen Munich secret');
        $this->get(route('student.documents'))->assertOk()->assertDontSee('Document Munich');
        $this->get(route('student.certificates'))->assertOk()->assertDontSee('Certificat Munich');

        $this->actingAs($this->munichUser);

        $this->get(route('student.schedule'))
            ->assertOk()
            ->assertSee('Cours Munich secret')
            ->assertDontSee('Cours Berlin A');

        $this->get(route('student.attendances'))
            ->assertOk()
            ->assertSee('COMMENT-MUNICH')
            ->assertDontSee('COMMENT-PRESENT');

        $this->get(route('student.evaluations'))
            ->assertOk()
            ->assertSee('Examen Munich secret')
            ->assertDontSee('Examen Berlin');

        $this->get(route('student.documents'))
            ->assertOk()
            ->assertSee('Document Munich')
            ->assertDontSee('Document Berlin');

        $this->get(route('student.certificates'))
            ->assertOk()
            ->assertSee('Certificat Munich')
            ->assertDontSee('Certificat Berlin');
    }

    public function test_student_portal_is_forbidden_for_teachers_and_center_admins(): void
    {
        $teacher = User::factory()->teacher($this->berlin->id)->create(['status' => 'active']);
        $admin = User::factory()->centerAdmin($this->berlin->id)->create(['status' => 'active']);

        $routes = [
            route('student.formations'),
            route('student.schedule'),
            route('student.attendances'),
            route('student.evaluations'),
            route('student.documents'),
            route('student.certificates'),
        ];

        foreach ($routes as $url) {
            $this->actingAs($teacher)->get($url)->assertForbidden();
            $this->actingAs($admin)->get($url)->assertForbidden();
        }
    }

    public function test_student_cannot_reach_administrative_lists(): void
    {
        $this->actingAs($this->studentUser);

        $this->get(route('schedules.index'))->assertForbidden();
        $this->get(route('attendances.index'))->assertForbidden();
        $this->get(route('evaluations.index'))->assertForbidden();
        $this->get(route('documents.index'))->assertForbidden();
        $this->get(route('certificates.index'))->assertForbidden();
    }

    public function test_student_portal_renders_in_french_and_arabic(): void
    {
        $this->actingAs($this->studentUser)
            ->withSession(['locale' => 'fr'])
            ->get(route('dashboard'))
            ->assertOk()
            ->assertSee('dir="ltr"', false)
            ->assertSee('Mes formations');

        $this->get(route('dashboard', ['lang' => 'ar']))
            ->assertOk()
            ->assertSee('dir="rtl"', false)
            ->assertSee('تكويناتي')
            ->assertSee('جدول مواعيدي');

        $this->get(route('student.evaluations', ['lang' => 'ar']))
            ->assertOk()
            ->assertSee('نقطتي')
            ->assertSee('المعدل العام');
    }

    private function makeTeacher(Center $center, string $number, string $firstName, string $lastName): Teacher
    {
        $user = User::factory()->teacher($center->id)->create([
            'name' => $firstName.' '.$lastName,
            'status' => 'active',
        ]);

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

    private function makeEnrollment(
        Student $student,
        Formation $formation,
        ?ClassRoom $classRoom,
        string $registrationNumber,
        string $status
    ): Enrollment {
        return Enrollment::create([
            'center_id' => $student->center_id,
            'student_id' => $student->id,
            'formation_id' => $formation->id,
            'class_id' => $classRoom?->id,
            'registration_number' => $registrationNumber,
            'application_date' => now()->subWeeks(2)->toDateString(),
            'status' => $status,
            'accepted_at' => $status === 'accepted' ? now()->subWeek() : null,
        ]);
    }

    private function makeSchedule(
        ClassRoom $classRoom,
        Teacher $teacher,
        Room $room,
        string $title,
        string $date,
        string $type
    ): Schedule {
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

    private function makeEvaluation(
        ClassRoom $classRoom,
        Teacher $teacher,
        string $title,
        string $type,
        string $status
    ): Evaluation {
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

    private function makeDocument(
        Student $student,
        string $title,
        string $type,
        string $path,
        string $originalName,
        string $mimeType,
        string $status = 'active'
    ): Document {
        return Document::create([
            'center_id' => $student->center_id,
            'student_id' => $student->id,
            'type' => $type,
            'title' => $title,
            'file_path' => $path,
            'original_name' => $originalName,
            'mime_type' => $mimeType,
            'size' => 1024,
            'uploaded_by' => $student->user_id,
            'status' => $status,
        ]);
    }

    private function makeCertificate(
        Student $student,
        Formation $formation,
        string $number,
        string $title,
        string $type,
        string $status,
        ?string $path
    ): Certificate {
        $enrollment = Enrollment::query()
            ->where('student_id', $student->id)
            ->where('formation_id', $formation->id)
            ->first();

        return Certificate::create([
            'center_id' => $student->center_id,
            'student_id' => $student->id,
            'enrollment_id' => $enrollment?->id,
            'formation_id' => $formation->id,
            'certificate_number' => $number,
            'type' => $type,
            'title' => $title,
            'issued_date' => now()->subDays(10)->toDateString(),
            'file_path' => $path,
            'verify_token' => 'token-'.strtolower($number),
            'status' => $status,
            'issued_by' => $student->user_id,
        ]);
    }
}
