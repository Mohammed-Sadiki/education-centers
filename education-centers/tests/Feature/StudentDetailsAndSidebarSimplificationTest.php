<?php

namespace Tests\Feature;

use App\Models\Center;
use App\Models\ClassRoom;
use App\Models\Document;
use App\Models\Enrollment;
use App\Models\Formation;
use App\Models\Payment;
use App\Models\Student;
use App\Models\Teacher;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class StudentDetailsAndSidebarSimplificationTest extends TestCase
{
    use RefreshDatabase;

    protected Center $centerTanger;
    protected Center $centerAgadir;
    protected User $superAdmin;
    protected User $adminTanger;
    protected User $adminAgadir;
    protected Teacher $teacherTanger;
    protected Formation $formationTanger;
    protected ClassRoom $classTanger;
    protected Student $studentTanger;

    protected function setUp(): void
    {
        parent::setUp();

        $this->centerTanger = Center::factory()->create([
            'name' => 'Centre Tanger',
            'code' => 'TANGER',
            'city' => 'Tanger',
        ]);

        $this->centerAgadir = Center::factory()->create([
            'name' => 'Centre Agadir',
            'code' => 'AGADIR',
            'city' => 'Agadir',
        ]);

        $this->superAdmin = User::factory()->superAdmin()->create(['email' => 'superadmin@ec.ma']);
        $this->adminTanger = User::factory()->centerAdmin($this->centerTanger->id)->create(['email' => 'admin.tanger@ec.ma']);
        $this->adminAgadir = User::factory()->centerAdmin($this->centerAgadir->id)->create(['email' => 'admin.agadir@ec.ma']);

        $teacherUser = User::factory()->teacher($this->centerTanger->id)->create(['name' => 'Wagner Tobias']);
        $this->teacherTanger = Teacher::create([
            'user_id' => $teacherUser->id,
            'center_id' => $this->centerTanger->id,
            'teacher_number' => 'TNG-T-000001',
            'first_name' => 'Tobias',
            'last_name' => 'Wagner',
            'status' => 'active',
        ]);

        $this->formationTanger = Formation::create([
            'center_id' => $this->centerTanger->id,
            'domain' => 'informatique',
            'name' => 'UI/UX Design & Illustration',
            'code' => 'UIUX-TNG',
            'duration_hours' => 120,
            'price' => 12000,
            'annual_price' => 12000,
            'status' => 'active',
        ]);

        $this->classTanger = ClassRoom::create([
            'center_id' => $this->centerTanger->id,
            'formation_id' => $this->formationTanger->id,
            'name' => 'Groupe A',
            'code' => 'GRP-A',
            'capacity' => 20,
            'status' => 'active',
        ]);
        $this->classTanger->teachers()->attach($this->teacherTanger->id);

        $this->studentTanger = Student::create([
            'center_id' => $this->centerTanger->id,
            'student_number' => 'TNG-E-000001',
            'first_name' => 'Sami',
            'last_name' => 'El Alami',
            'date_of_birth' => '2002-05-14',
            'nationality' => 'Marocaine',
            'phone' => '+212600112233',
            'whatsapp' => '+212600112233',
            'emergency_contact_name' => 'Karim El Alami',
            'emergency_contact_phone' => '+212699887766',
            'city' => 'Tanger',
            'status' => 'active',
        ]);

        Enrollment::create([
            'center_id' => $this->centerTanger->id,
            'student_id' => $this->studentTanger->id,
            'formation_id' => $this->formationTanger->id,
            'class_id' => $this->classTanger->id,
            'registration_number' => 'REG-001',
            'status' => Enrollment::STATUS_ACCEPTED,
            'application_date' => now()->toDateString(),
        ]);

        Payment::create([
            'center_id' => $this->centerTanger->id,
            'student_id' => $this->studentTanger->id,
            'formation_id' => $this->formationTanger->id,
            'amount' => 4000,
            'currency' => 'MAD',
            'payment_date' => now()->toDateString(),
            'method' => 'cash',
            'reference' => 'PAY-TNG-001',
            'status' => 'paid',
            'recorded_by' => $this->adminTanger->id,
        ]);
    }

    /**
     * 1. Vérification que les liens Étudiants et Professeurs sont absents de la section Pédagogie de la sidebar
     */
    public function test_students_and_teachers_links_are_absent_from_sidebar(): void
    {
        // Pour le Super Admin
        $responseSuper = $this->actingAs($this->superAdmin)->get(route('admin.home'));
        $responseSuper->assertStatus(200);
        $responseSuper->assertDontSee('href="' . route('students.index') . '"', false);
        $responseSuper->assertDontSee('href="' . route('teachers.index') . '"', false);

        // Pour l'Admin Centre
        $responseAdmin = $this->actingAs($this->adminTanger)->get(route('admin.home'));
        $responseAdmin->assertStatus(200);
        $responseAdmin->assertDontSee('href="' . route('students.index') . '"', false);
        $responseAdmin->assertDontSee('href="' . route('teachers.index') . '"', false);
    }

    /**
     * 2. Le Plan de classe est accessible et conserve sa disposition graphique
     */
    public function test_class_plan_page_renders_with_graphical_classroom(): void
    {
        $response = $this->actingAs($this->adminTanger)->get(route('class-plan.index', [
            'teacher_id' => $this->teacherTanger->id,
            'class_id' => $this->classTanger->id,
        ]));

        $response->assertStatus(200);
        $response->assertSee('Wagner Tobias');
        $response->assertSee('Groupe A');
        $response->assertSee('classroom-container');
        $response->assertSee('students-grid');
    }

    /**
     * 3. La page Détails de l'étudiant est accessible et affiche les données réelles
     */
    public function test_student_details_page_shows_real_data(): void
    {
        $response = $this->actingAs($this->adminTanger)->get(route('students.show', [
            'student' => $this->studentTanger,
            'return_teacher_id' => $this->teacherTanger->id,
            'return_class_id' => $this->classTanger->id,
        ]));

        $response->assertStatus(200);
        $response->assertSee('Sami El Alami');
        $response->assertSee('Centre Tanger');
        $response->assertSee('UI/UX Design & Illustration');
        $response->assertSee('Wagner Tobias');
        $response->assertSee('+212600112233');
        $response->assertSee('Karim El Alami');
        $response->assertSee('Situation financière');
        $response->assertSee('12 000,00'); // Montant total attendu
        $response->assertSee('4 000,00');  // Montant payé
        $response->assertSee('8 000,00');  // Reste à payer
        $response->assertSee('PAY-TNG-001');

        // Bouton retour au plan de classe avec les filtres
        $response->assertSee('Retour au Plan de classe');
        $response->assertSee('plan-de-classe?teacher_id=' . $this->teacherTanger->id . '&amp;class_id=' . $this->classTanger->id, false);

        // Vérification du layout indépendant : pas de sidebar ni header administratif pour l'Admin Centre
        $response->assertSee('student-dossier-layout', false);
        $response->assertDontSee('ec-sidebar-panel', false);
    }

    /**
     * 4. Pour le Super Admin, conservation du layout normal
     */
    public function test_super_admin_views_student_in_standard_layout(): void
    {
        $response = $this->actingAs($this->superAdmin)->get(route('students.show', $this->studentTanger));
        $response->assertStatus(200);
        $response->assertDontSee('student-dossier-layout', false);
        $response->assertSee('ec-sidebar-panel', false);
    }

    /**
     * 5. Isolation multi-centres : l'Admin Agadir ne peut pas voir l'étudiant de Tanger
     */
    public function test_center_admin_cannot_view_student_from_another_center(): void
    {
        $response = $this->actingAs($this->adminAgadir)->get(route('students.show', $this->studentTanger));
        $response->assertStatus(403);
    }
}
