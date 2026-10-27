<?php

namespace Tests\Feature;

use App\Models\Center;
use App\Models\Stagiaire;
use App\Models\StagiaireDiploma;
use App\Models\StagiaireDocument;
use App\Models\Student;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\Concerns\HasStagiaireFormData;
use Tests\TestCase;

class StagiaireManagementTest extends TestCase
{
    use HasStagiaireFormData;
    use RefreshDatabase;

    protected Center $centerParis;
    protected Center $centerLyon;
    protected User $superAdmin;
    protected User $adminParis;
    protected User $teacher;
    protected User $studentUser;

    protected function setUp(): void
    {
        parent::setUp();

        Storage::fake('local');

        $this->centerParis = Center::factory()->create(['name' => 'Centre Paris', 'code' => 'PAR']);
        $this->centerLyon = Center::factory()->create(['name' => 'Centre Lyon', 'code' => 'LYO']);

        $this->superAdmin = User::factory()->superAdmin()->create([
            'email' => 'superadmin.stagiaire@test.com',
            'status' => 'active',
        ]);

        $this->adminParis = User::factory()->centerAdmin($this->centerParis->id)->create([
            'email' => 'admin.paris@test.com',
            'status' => 'active',
        ]);

        $this->teacher = User::factory()->teacher($this->centerParis->id)->create([
            'email' => 'prof.paris@test.com',
            'status' => 'active',
        ]);

        $this->studentUser = User::factory()->student($this->centerParis->id)->create([
            'email' => 'student.paris@test.com',
            'status' => 'active',
        ]);
    }

    /**
     * 1. Super Admin peut accéder au module Stagiaires (dashboard redirigé, index, create).
     */
    public function test_super_admin_can_access_stagiaires_module(): void
    {
        // L'ancienne route du tableau de bord redirige proprement vers la liste.
        $this->actingAs($this->superAdmin)
            ->get(route('stagiaires.dashboard'))
            ->assertRedirect(route('stagiaires.index'));

        $this->actingAs($this->superAdmin)
            ->get(route('stagiaires.index'))
            ->assertOk()
            ->assertSee('Tous les stagiaires');

        $this->actingAs($this->superAdmin)
            ->get(route('stagiaires.create'))
            ->assertOk()
            ->assertSee('Informations du stage')
            ->assertSee('Prix du stage');
    }

    /**
     * 2. Les autres rôles (non Super Admin) sont refusés avec 403.
     */
    public function test_other_roles_are_forbidden_from_stagiaires_module(): void
    {
        // Teacher
        $this->actingAs($this->teacher)
            ->get(route('stagiaires.dashboard'))
            ->assertForbidden();

        $this->actingAs($this->teacher)
            ->get(route('stagiaires.index'))
            ->assertForbidden();

        // Student
        $this->actingAs($this->studentUser)
            ->get(route('stagiaires.dashboard'))
            ->assertForbidden();

        // Center Admin (non super-admin pour l'instant)
        $this->actingAs($this->adminParis)
            ->get(route('stagiaires.dashboard'))
            ->assertForbidden();
    }

    /**
     * 3 & 6. Création d'un stagiaire avec stage_price positif et devise EUR.
     * Le jeu de données utilise les alias du formulaire (birth_date, cin_delivered_at…)
     * et reste volontairement proche de ce que poste le navigateur.
     */
    public function test_super_admin_can_create_stagiaire_with_stage_price(): void
    {
        $payload = [
            'first_name' => 'Karim',
            'last_name' => 'Benani',
            'gender' => 'homme',
            'birth_date' => '1998-05-14',
            'birth_place' => 'Casablanca',
            'nationality' => 'Marocaine',
            'phone' => '+33612345678',
            'email' => 'karim.benani@example.com',
            'address' => '12 Rue de Rivoli, Paris',
            'cin_number' => 'AB123456',
            'cin_delivered_at' => '2020-01-15',
            'cin_delivered_place' => 'Préfecture Paris',
            'stage_start_date' => '2026-11-01',
            'stage_end_date' => '2027-04-30',
            'stage_type' => 'Stage de fin d\'études',
            'stage_price' => 1500.00,
            'currency' => 'EUR',
            'status' => 'nouveau',
            'center_id' => $this->centerParis->id,
            'notes' => 'Candidature spontanée acceptée.',
        ] + $this->cinFiles();

        $response = $this->actingAs($this->superAdmin)
            ->post(route('stagiaires.store'), $payload);

        $response->assertRedirect();

        $this->assertDatabaseHas('stagiaires', [
            'first_name' => 'Karim',
            'last_name' => 'Benani',
            'cin_number' => 'AB123456',
            'stage_price' => 1500.00,
            'currency' => 'EUR',
            'center_id' => $this->centerParis->id,
        ]);

        $stagiaire = Stagiaire::where('email', 'karim.benani@example.com')->first();
        $this->assertNotNull($stagiaire);
        $this->assertEquals(1500.00, (float)$stagiaire->stage_price);
    }

    /**
     * 7. Prix négatif refusé par la validation.
     */
    public function test_negative_stage_price_is_rejected(): void
    {
        $payload = [
            'first_name' => 'Yassine',
            'last_name' => 'Alaoui',
            'email' => 'yassine@example.com',
            'stage_price' => -250.00,
        ];

        $response = $this->actingAs($this->superAdmin)
            ->from(route('stagiaires.create'))
            ->post(route('stagiaires.store'), $payload);

        $response->assertSessionHasErrors('stage_price');
        $this->assertDatabaseMissing('stagiaires', [
            'email' => 'yassine@example.com',
        ]);
    }

    /**
     * 4. Modification d'un stagiaire.
     */
    public function test_super_admin_can_update_stagiaire(): void
    {
        $stagiaire = Stagiaire::create([
            'first_name' => 'Sara',
            'last_name' => 'Idrissi',
            'stage_price' => 800.00,
            'currency' => 'EUR',
            'status' => 'nouveau',
            'center_id' => $this->centerParis->id,
        ]);

        $updatePayload = $this->stagiairePayloadWithCin([
            'first_name' => 'Sara',
            'last_name' => 'Idrissi-Nouvelle',
            'email' => 'sara.idrissi@example.com',
            'stage_price' => 1200.00,
            'currency' => 'EUR',
            'status' => 'en_cours',
            'center_id' => $this->centerLyon->id,
        ]);

        $response = $this->actingAs($this->superAdmin)
            ->put(route('stagiaires.update', $stagiaire), $updatePayload);

        $response->assertRedirect(route('stagiaires.show', $stagiaire));

        $this->assertDatabaseHas('stagiaires', [
            'id' => $stagiaire->id,
            'last_name' => 'Idrissi-Nouvelle',
            'stage_price' => 1200.00,
            'status' => 'en_cours',
            'center_id' => $this->centerLyon->id,
        ]);
    }

    /**
     * 5. Affichage du détail d'un stagiaire.
     */
    public function test_super_admin_can_view_stagiaire_detail(): void
    {
        $stagiaire = Stagiaire::create([
            'first_name' => 'Amine',
            'last_name' => 'Tazi',
            'cin_number' => 'C987654',
            'stage_price' => 2000.00,
            'currency' => 'EUR',
            'status' => 'en_cours',
            'center_id' => $this->centerParis->id,
        ]);

        $response = $this->actingAs($this->superAdmin)
            ->get(route('stagiaires.show', $stagiaire));

        $response->assertOk()
            ->assertSee('Amine')
            ->assertSee('Tazi')
            ->assertSee('C987654')
            ->assertSee('2 000,00')
            ->assertSee('EUR');
    }

    /**
     * 8, 9 & 10. Documents et diplômes multiples enregistrés dans un stockage privé et protégés.
     */
    public function test_stagiaire_cin_and_multiple_diplomas_and_documents_are_stored_securely(): void
    {
        $cinRecto = UploadedFile::fake()->create('cin_recto.pdf', 300, 'application/pdf');
        $cinVerso = UploadedFile::fake()->create('cin_verso.pdf', 300, 'application/pdf');
        $diploma1File = UploadedFile::fake()->create('licence.pdf', 500, 'application/pdf');
        $diploma2File = UploadedFile::fake()->create('master.pdf', 600, 'application/pdf');
        $otherDocFile = UploadedFile::fake()->create('attestation.pdf', 200, 'application/pdf');

        $payload = $this->stagiairePayload([
            'first_name' => 'Mehdi',
            'last_name' => 'El Fassi',
            'email' => 'mehdi.elfassi@example.com',
            'cin_number' => 'EF998877',
            'stage_price' => 1800.00,
            'currency' => 'EUR',
            'center_id' => $this->centerParis->id,
        ]) + [
            'cin_recto' => $cinRecto,
            'cin_verso' => $cinVerso,
            'diplomas' => [
                [
                    'title' => 'Licence Fondamentale',
                    'institution' => 'Université Hassan II',
                    'year' => 2022,
                    'file' => $diploma1File,
                ],
                [
                    'title' => 'Master Spécialisé',
                    'institution' => 'Université Paris-Saclay',
                    'year' => 2024,
                    'file' => $diploma2File,
                ],
            ],
            'other_documents' => [
                [
                    'title' => 'Attestation de réussite',
                    'type' => 'attestation',
                    'file' => $otherDocFile,
                ],
            ],
        ];

        $response = $this->actingAs($this->superAdmin)
            ->post(route('stagiaires.store'), $payload);

        $response->assertSessionHasNoErrors();
        $response->assertRedirect();

        $stagiaire = Stagiaire::where('first_name', 'Mehdi')->first();
        $this->assertNotNull($stagiaire);

        // Vérification CIN
        $this->assertNotNull($stagiaire->cin_recto_path);
        $this->assertNotNull($stagiaire->cin_verso_path);
        Storage::disk('local')->assertExists($stagiaire->cin_recto_path);
        Storage::disk('local')->assertExists($stagiaire->cin_verso_path);

        // Vérification Diplômes multiples (9)
        $this->assertCount(2, $stagiaire->diplomas);
        $dip1 = $stagiaire->diplomas()->where('title', 'Licence Fondamentale')->first();
        $this->assertNotNull($dip1);
        $this->assertNotNull($dip1->file_path);
        Storage::disk('local')->assertExists($dip1->file_path);

        // Vérification Autres documents (8)
        $this->assertCount(1, $stagiaire->documents);
        $doc = $stagiaire->documents()->first();
        $this->assertEquals('Attestation de réussite', $doc->title);
        Storage::disk('local')->assertExists($doc->file_path);

        // 10. Téléchargements autorisés pour Super Admin, refusés aux non-autorisés
        $this->actingAs($this->superAdmin)
            ->get(route('stagiaires.cin-download', [$stagiaire, 'recto']))
            ->assertOk();

        $this->actingAs($this->superAdmin)
            ->get(route('stagiaires.diploma-download', [$stagiaire, $dip1]))
            ->assertOk();

        // Refusé pour invité / non authentifié
        $this->app['auth']->forgetGuards();
        $this->get(route('stagiaires.cin-download', [$stagiaire, 'recto']))
            ->assertRedirect(route('login'));

        // Refusé pour Teacher
        $this->actingAs($this->teacher)
            ->get(route('stagiaires.diploma-download', [$stagiaire, $dip1]))
            ->assertForbidden();
    }

    /**
     * 11. Multi-centres : le filtrage et l'isolation par centre sont préparés.
     */
    public function test_stagiaires_center_isolation_and_filter(): void
    {
        $stagiaireParis = Stagiaire::create([
            'first_name' => 'Stagiaire',
            'last_name' => 'Parisien',
            'stage_price' => 1000.00,
            'center_id' => $this->centerParis->id,
        ]);

        $stagiaireLyon = Stagiaire::create([
            'first_name' => 'Stagiaire',
            'last_name' => 'Lyonnais',
            'stage_price' => 1100.00,
            'center_id' => $this->centerLyon->id,
        ]);

        // Super Admin filtre par centre Paris
        $response = $this->actingAs($this->superAdmin)
            ->get(route('stagiaires.index', ['center_id' => $this->centerParis->id]));

        $response->assertOk()
            ->assertSee('Parisien')
            ->assertDontSee('Lyonnais');
    }

    /**
     * 12. Les étudiants existants continuent de fonctionner normalement.
     */
    public function test_existing_students_are_unaffected_and_independent(): void
    {
        $studentUser = User::factory()->student($this->centerParis->id)->create([
            'email' => 'lucas.dupont@test.com',
            'status' => 'active',
        ]);

        $student = Student::create([
            'user_id' => $studentUser->id,
            'center_id' => $this->centerParis->id,
            'student_number' => 'PAR-999001',
            'first_name' => 'Lucas',
            'last_name' => 'Dupont',
            'date_of_birth' => '2001-04-10',
            'status' => 'active',
        ]);

        $this->actingAs($this->superAdmin)
            ->get(route('students.show', $student))
            ->assertOk()
            ->assertSee('Lucas')
            ->assertSee('Dupont');

        // La table students ne contient pas la colonne stage_price
        $this->assertFalse(\Illuminate\Support\Facades\Schema::hasColumn('students', 'stage_price'));
    }

    /**
     * 13. Contexte Stagiaires : sidebar simplifiée (2 liens uniquement), absence des
     * anciens groupes Dashboard/Dossiers et des liens Santé/Pédagogie.
     *
     * Les assertions portent sur la sidebar (<aside>) afin de rester indépendantes
     * du contenu principal de la page.
     */
    public function test_stagiaires_context_displays_dedicated_sidebar_and_navigation(): void
    {
        $response = $this->actingAs($this->superAdmin)
            ->get(route('stagiaires.index'));

        $response->assertOk()
            ->assertSee('Total stagiaires');

        $html = $response->getContent();
        $sidebar = substr($html, strpos($html, '<aside'), strpos($html, '</aside>') - strpos($html, '<aside'));

        // Éléments structurels conservés.
        $this->assertStringContainsString('Espace administratif', $sidebar);
        $this->assertStringContainsString(route('admin.home'), $sidebar);

        // Les deux seules entrées fonctionnelles.
        $this->assertStringContainsString('Tous les stagiaires', $sidebar);
        $this->assertStringContainsString('Ajouter un stagiaire', $sidebar);

        // Groupe Dashboard et groupe Dossiers retirés de la sidebar.
        $this->assertStringNotContainsString('Tableau de bord', $sidebar);
        $this->assertStringNotContainsString(route('stagiaires.dashboard'), $sidebar);
        $this->assertStringNotContainsString('Dossiers complets', $sidebar);
        $this->assertStringNotContainsString('Dossiers incomplets', $sidebar);
        $this->assertStringNotContainsString(route('stagiaires.documents'), $sidebar);

        // Hors périmètre : liens du contexte domaine.
        $this->assertStringNotContainsString('Dashboard Santé', $sidebar);
        $this->assertStringNotContainsString('Pédagogie', $sidebar);
    }

    /**
     * 14. Contexte Santé : vérification de la navigation Santé et absence des liens Stagiaires.
     */
    public function test_sante_context_displays_sante_navigation_and_not_stagiaires(): void
    {
        $response = $this->actingAs($this->superAdmin)
            ->get(route('super-admin.sante.dashboard'));

        $response->assertOk()
            ->assertSee('Dashboard Santé')
            ->assertSee('Pédagogie')
            ->assertSee('Santé')
            ->assertDontSee('Tous les stagiaires')
            ->assertDontSee('Dossiers complets');
    }

    /**
     * 15. Les filtres « dossier complet / incomplet » ont été retirés de l'interface :
     * la liste affiche tous les stagiaires autorisés, quel que soit l'état du dossier,
     * et l'ancien paramètre ?filter ne restreint plus rien.
     */
    public function test_dossier_completeness_filters_are_removed_and_all_stagiaires_are_listed(): void
    {
        // Dossier « complet » (CIN + dates renseignés)
        Stagiaire::create([
            'first_name' => 'Jean-Luc',
            'last_name' => 'Complet',
            'cin_number' => 'CIN112233',
            'cin_recto_path' => 'stagiaires/cin/test_recto.pdf',
            'stage_start_date' => '2026-11-01',
            'stage_end_date' => '2027-02-28',
            'stage_price' => 1200.00,
            'center_id' => $this->centerParis->id,
        ]);

        // Dossier « incomplet » (CIN et dates manquants)
        Stagiaire::create([
            'first_name' => 'Paul',
            'last_name' => 'Incomplet',
            'center_id' => $this->centerParis->id,
        ]);

        // Aucun onglet ni compteur de dossiers dans la page.
        $page = $this->actingAs($this->superAdmin)
            ->get(route('stagiaires.index'))
            ->assertOk()
            ->assertSee('Complet')
            ->assertSee('Incomplet')
            ->getContent();

        $this->assertStringNotContainsString('Tous les dossiers', $page);
        $this->assertStringNotContainsString('Dossiers complets', $page);
        $this->assertStringNotContainsString('Dossiers incomplets', $page);

        // L'ancien paramètre ne filtre plus la liste : les deux stagiaires restent visibles.
        $this->actingAs($this->superAdmin)
            ->get(route('stagiaires.index', ['filter' => 'complets']))
            ->assertOk()
            ->assertSee('Complet')
            ->assertSee('Incomplet');

        // Les données ne sont évidemment pas modifiées par ces changements d'affichage.
        $this->assertDatabaseHas('stagiaires', ['last_name' => 'Complet', 'cin_number' => 'CIN112233']);
        $this->assertDatabaseHas('stagiaires', ['last_name' => 'Incomplet', 'cin_number' => null]);
    }

    /**
     * 16. Espace Documents centralisé accessible.
     */
    public function test_stagiaires_documents_view_is_accessible(): void
    {
        $this->actingAs($this->superAdmin)
            ->get(route('stagiaires.documents'))
            ->assertOk()
            ->assertSee('Espace Documents')
            ->assertSee('Cartes CIN')
            ->assertSee('Diplômes');
    }
}
