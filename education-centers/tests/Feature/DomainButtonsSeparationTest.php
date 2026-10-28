<?php

namespace Tests\Feature;

use App\Models\Center;
use App\Models\Formation;
use App\Models\Payment;
use App\Models\Student;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DomainButtonsSeparationTest extends TestCase
{
    use RefreshDatabase;

    protected Center $centerBerlin;
    protected Center $centerMunich;
    protected User $superAdmin;
    protected User $adminBerlin;

    protected function setUp(): void
    {
        parent::setUp();

        $this->centerBerlin = Center::factory()->create([
            'name' => 'Centre Berlin',
            'code' => 'BER',
            'city' => 'Berlin',
        ]);

        $this->centerMunich = Center::factory()->create([
            'name' => 'Centre Munich',
            'code' => 'MUN',
            'city' => 'Munich',
        ]);

        $this->superAdmin = User::factory()->superAdmin()->create();
        $this->adminBerlin = User::factory()->centerAdmin($this->centerBerlin->id)->create();
    }

    /**
     * TEST A : Dans la page Domaines de formation, le bouton « + Ajouter un élève »
     * ouvre le formulaire d'inscription élève (students.create) avec nom, CIN,
     * formation et paiement.
     */
    public function test_button_add_student_opens_student_form_with_personal_info_and_payment(): void
    {
        // 1. Super Admin
        $responseSA = $this->actingAs($this->superAdmin)->get(route('super-admin.domaines'));
        $responseSA->assertOk();
        $responseSA->assertSee('id="btn-add-student-domaines"', false);
        $responseSA->assertSee(route('students.create'), false);

        // Accès direct à students.create
        $formSA = $this->actingAs($this->superAdmin)->get(route('students.create'));
        $formSA->assertOk();
        $formSA->assertSee('Ajouter un élève');
        $formSA->assertSee('Informations personnelles');
        $formSA->assertSee('Pièces et fichiers');
        $formSA->assertSee('Paiement');

        // 2. Admin Centre
        $responseCA = $this->actingAs($this->adminBerlin)->get(route('center-admin.domaines'));
        $responseCA->assertOk();
        $responseCA->assertSee('id="btn-add-student-ca-domaines"', false);
        $responseCA->assertSee(route('students.create'), false);

        $formCA = $this->actingAs($this->adminBerlin)->get(route('students.create'));
        $formCA->assertOk();
        $formCA->assertSee('Ajouter un élève');
        $formCA->assertSee('Informations personnelles');
        $formCA->assertSee('Pièces et fichiers');
        $formCA->assertSee('Paiement');
    }

    /**
     * TEST B : Dans la page Domaines de formation, le bouton « + Ajouter une formation »
     * ouvre le formulaire de création de formation (formations.create) SANS informations d'élève ni paiement.
     */
    public function test_button_add_formation_opens_catalog_formation_form_without_student_fields(): void
    {
        // 1. Super Admin
        $responseSA = $this->actingAs($this->superAdmin)->get(route('super-admin.domaines'));
        $responseSA->assertOk();
        $responseSA->assertSee('id="btn-add-formation-domaines"', false);
        $responseSA->assertSee(route('formations.create'), false);

        // Vérification de la cible formations.create
        $formSA = $this->actingAs($this->superAdmin)->get(route('formations.create'));
        $formSA->assertOk();
        $formSA->assertSee('Ajouter une formation');
        $formSA->assertSee('Intitulé de la formation');
        $formSA->assertSee('Code formation');
        $formSA->assertSee('Domaine de formation');
        // Ne doit JAMAIS afficher de champs élève ni paiement
        $formSA->assertDontSee('Photos CIN');
        $formSA->assertDontSee('Numéro WhatsApp');
        $formSA->assertDontSee('Numéro d\'urgence');
        $formSA->assertDontSee('Informations personnelles');
        $formSA->assertDontSee('Paiement : Avance ou Payé');

        // 2. Admin Centre
        $responseCA = $this->actingAs($this->adminBerlin)->get(route('center-admin.domaines'));
        $responseCA->assertOk();
        $responseCA->assertSee('id="btn-add-formation-ca-domaines"', false);
        $responseCA->assertSee(route('formations.create'), false);

        $formCA = $this->actingAs($this->adminBerlin)->get(route('formations.create'));
        $formCA->assertOk();
        $formCA->assertSee('Ajouter une formation');
        $formCA->assertSee('Intitulé de la formation');
        $formCA->assertSee('Code formation');
        $formCA->assertDontSee('Photos CIN');
        $formCA->assertDontSee('Numéro WhatsApp');
        $formCA->assertDontSee('Numéro d\'urgence');
    }

    /**
     * TEST C : Enregistrement d'une formation dans le catalogue
     * Vérifie qu'elle apparaît dans le catalogue SANS créer d'élève, d'inscription ou de paiement.
     */
    public function test_formation_store_creates_catalog_entry_without_student_or_payment(): void
    {
        $initialStudents = Student::count();
        $initialPayments = Payment::count();

        $formationData = [
            'center_id' => $this->centerBerlin->id,
            'domain' => 'informatique',
            'name' => 'Développeur Full Stack Web',
            'code' => 'FSW-2026',
            'description' => 'Formation intensive en développement web moderne.',
            'duration_hours' => 300,
            'duration_weeks' => 16,
            'price' => 12000,
            'currency' => 'MAD',
            'status' => 'active',
        ];

        $response = $this->actingAs($this->superAdmin)
            ->post(route('formations.store'), $formationData);

        $response->assertRedirect();

        // 1. La formation existe bien
        $this->assertDatabaseHas('formations', [
            'name' => 'Développeur Full Stack Web',
            'code' => 'FSW-2026',
            'domain' => 'informatique',
            'center_id' => $this->centerBerlin->id,
        ]);

        // 2. Aucun élève ni paiement n'a été créé
        $this->assertEquals($initialStudents, Student::count(), 'Aucun dossier élève ne doit être créé par ce formulaire.');
        $this->assertEquals($initialPayments, Payment::count(), 'Aucun paiement ne doit être créé par ce formulaire.');
    }

    /**
     * TEST D : Non-régression — Le formulaire élève (students.create / students.store)
     * fonctionne toujours et crée l'élève + le paiement dans Gestion financière.
     */
    public function test_student_store_creates_student_and_payment(): void
    {
        $formation = Formation::factory()->create([
            'center_id' => $this->centerBerlin->id,
            'domain' => 'informatique',
            'annual_price' => 5000,
            'price' => 5000,
        ]);

        $studentData = [
            'first_name' => 'Karim',
            'last_name' => 'Alami',
            'center_id' => $this->centerBerlin->id,
            'domain' => 'informatique',
            'formation_id' => $formation->id,
            'payment_option' => 'paye',
            'date_of_birth' => '2000-01-15',
            'nationality' => 'Marocaine',
            'phone' => '+212600112233',
            'whatsapp' => '+212600112233',
            'emergency_contact_phone' => '+212600112244',
        ];

        $response = $this->actingAs($this->superAdmin)
            ->post(route('students.store'), $studentData);

        $response->assertRedirect();

        // Vérifier la création de l'élève
        $this->assertDatabaseHas('students', [
            'first_name' => 'Karim',
            'last_name' => 'Alami',
            'center_id' => $this->centerBerlin->id,
        ]);

        // Vérifier la création du paiement dans Gestion financière
        $this->assertDatabaseHas('payments', [
            'center_id' => $this->centerBerlin->id,
            'formation_id' => $formation->id,
            'amount' => 5000,
        ]);
    }
}
