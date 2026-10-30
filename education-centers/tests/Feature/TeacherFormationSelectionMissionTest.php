<?php

namespace Tests\Feature;

use App\Models\Center;
use App\Models\Domain;
use App\Models\Formation;
use App\Models\SchoolClass;
use App\Models\Teacher;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TeacherFormationSelectionMissionTest extends TestCase
{
    use RefreshDatabase;

    protected Center $centerBerlin;
    protected Center $centerMunich;
    protected User $superAdmin;
    protected User $adminBerlin;
    protected User $adminMunich;

    protected function setUp(): void
    {
        parent::setUp();

        $this->centerBerlin = Center::factory()->create(['name' => 'Centre Berlin', 'code' => 'BERLIN']);
        $this->centerMunich = Center::factory()->create(['name' => 'Centre Munich', 'code' => 'MUNICH']);

        $this->superAdmin = User::factory()->superAdmin()->create(['email' => 'sa@ec.test']);
        $this->adminBerlin = User::factory()->centerAdmin($this->centerBerlin->id)->create(['email' => 'ab@ec.test']);
        $this->adminMunich = User::factory()->centerAdmin($this->centerMunich->id)->create(['email' => 'am@ec.test']);

        // Créer les 6 domaines officiels
        $domains = [
            ['slug' => 'sante', 'name' => 'Santé'],
            ['slug' => 'informatique', 'name' => 'Informatique'],
            ['slug' => 'hotellerie', 'name' => 'Hôtellerie'],
            ['slug' => 'maconnerie', 'name' => 'Maçonnerie'],
            ['slug' => 'design-interieur', 'name' => 'Design intérieur'],
            ['slug' => 'design-graphique', 'name' => 'Design graphique'],
        ];

        foreach ($domains as $d) {
            Domain::firstOrCreate(['slug' => $d['slug']], [
                'name' => $d['name'],
                'icon' => 'folder',
                'color' => 'blue',
                'sort_order' => 1,
                'is_active' => true,
            ]);
        }
    }

    /**
     * 1. Le formulaire d'ajout contient les deux Selects liés et n'a plus de cases à cocher pour les domaines.
     */
    public function test_create_form_has_two_selects_and_no_checkboxes(): void
    {
        $response = $this->actingAs($this->superAdmin)->get(route('users.create'));
        $response->assertStatus(200);

        // Doit avoir le select Domaine d'enseignement
        $response->assertSee('name="domain"', false);
        $response->assertSee('Domaine d\'enseignement');

        // Doit avoir le select Formation enseignée
        $response->assertSee('name="formation_id"', false);
        $response->assertSee('Formation enseignée');

        // Ne doit plus afficher des cases à cocher 'domains[]'
        $response->assertDontSee('name="domains[]"', false);

        // Doit contenir les 6 domaines dans le select
        $response->assertSee('Santé');
        $response->assertSee('Informatique');
        $response->assertSee('Hôtellerie');
        $response->assertSee('Maçonnerie');
        $response->assertSee('Design intérieur');
        $response->assertSee('Design graphique');
    }

    /**
     * 2. Création réussie d'un professeur avec domaine et formation assignés.
     */
    public function test_teacher_creation_stores_domain_and_formation_id(): void
    {
        $formationBerlinInfo = Formation::create([
            'center_id' => $this->centerBerlin->id,
            'domain' => 'informatique',
            'name' => 'Développement Web Fullstack',
            'code' => 'DEV-WEB-BER',
            'duration_hours' => 140,
            'price' => 600,
            'status' => 'active',
        ]);

        $payload = [
            'name' => 'Professeur Informatique',
            'email' => 'prof.info@ec.test',
            'password' => 'Secret123!',
            'password_confirmation' => 'Secret123!',
            'role' => User::ROLE_TEACHER,
            'center_id' => $this->centerBerlin->id,
            'status' => 'active',
            'domain' => 'informatique',
            'formation_id' => $formationBerlinInfo->id,
        ];

        $response = $this->actingAs($this->superAdmin)->post(route('users.store'), $payload);

        $response->assertRedirect(route('users.index'));

        // Vérification dans users
        $this->assertDatabaseHas('users', [
            'email' => 'prof.info@ec.test',
            'role' => User::ROLE_TEACHER,
            'center_id' => $this->centerBerlin->id,
            'domain' => 'informatique',
            'formation_id' => $formationBerlinInfo->id,
        ]);

        $user = User::where('email', 'prof.info@ec.test')->first();
        $this->assertNotNull($user);
        $this->assertNotNull($user->formation);
        $this->assertEquals('Développement Web Fullstack', $user->formation->name);
        $this->assertContains('informatique', $user->domainSlugs()->toArray());
    }

    /**
     * 3. Isolation multi-centres : l'Admin Centre Berlin ne peut pas assigner une formation de Munich.
     */
    public function test_center_admin_cannot_assign_formation_from_another_center(): void
    {
        $formationMunich = Formation::create([
            'center_id' => $this->centerMunich->id,
            'domain' => 'informatique',
            'name' => 'Data Science Munich',
            'code' => 'DS-MUC',
            'duration_hours' => 100,
            'price' => 500,
            'status' => 'active',
        ]);

        $payload = [
            'name' => 'Prof Hacker',
            'email' => 'hacker@ec.test',
            'password' => 'Secret123!',
            'password_confirmation' => 'Secret123!',
            'role' => User::ROLE_TEACHER,
            'status' => 'active',
            'domain' => 'informatique',
            'formation_id' => $formationMunich->id, // Formation d'un autre centre !
        ];

        // L'admin de Berlin tente de rattacher la formation de Munich -> Refus 403 (création interdite aux admin centres)
        $response = $this->actingAs($this->adminBerlin)->post(route('users.store'), $payload);

        $response->assertStatus(403);
        $this->assertDatabaseMissing('users', ['email' => 'hacker@ec.test']);
    }

    /**
     * 4. Validation stricte : la formation doit correspondre au domaine sélectionné.
     */
    public function test_formation_must_match_the_selected_domain(): void
    {
        $formationSante = Formation::create([
            'center_id' => $this->centerBerlin->id,
            'domain' => 'sante',
            'name' => 'Soins Infirmiers',
            'code' => 'SOIN-BER',
            'duration_hours' => 100,
            'price' => 500,
            'status' => 'active',
        ]);

        $payload = [
            'name' => 'Prof Mismatch',
            'email' => 'mismatch@ec.test',
            'password' => 'Secret123!',
            'password_confirmation' => 'Secret123!',
            'role' => User::ROLE_TEACHER,
            'center_id' => $this->centerBerlin->id,
            'status' => 'active',
            'domain' => 'informatique', // Ne correspond pas à sante !
            'formation_id' => $formationSante->id,
        ];

        $response = $this->actingAs($this->superAdmin)->post(route('users.store'), $payload);
        $response->assertSessionHasErrors('formation_id');
        $this->assertDatabaseMissing('users', ['email' => 'mismatch@ec.test']);
    }

    /**
     * 5. Modification du domaine et de la formation dans users.edit.
     */
    public function test_edit_user_updates_domain_and_formation(): void
    {
        $formation1 = Formation::create([
            'center_id' => $this->centerBerlin->id,
            'domain' => 'informatique',
            'name' => 'Formation Info',
            'code' => 'INFO-1',
            'duration_hours' => 50,
            'price' => 300,
            'status' => 'active',
        ]);

        $formation2 = Formation::create([
            'center_id' => $this->centerBerlin->id,
            'domain' => 'sante',
            'name' => 'Formation Sante',
            'code' => 'SANTE-1',
            'duration_hours' => 60,
            'price' => 400,
            'status' => 'active',
        ]);

        $teacherUser = User::factory()->teacher($this->centerBerlin->id)->create([
            'name' => 'Prof Initial',
            'email' => 'teacher.update@ec.test',
            'domain' => 'informatique',
            'formation_id' => $formation1->id,
        ]);

        // Vérifier l'accès à la page d'édition
        $responseGet = $this->actingAs($this->adminBerlin)->get(route('users.edit', $teacherUser));
        $responseGet->assertStatus(200);
        $responseGet->assertSee('name="domain"', false);
        $responseGet->assertSee('name="formation_id"', false);

        // Mettre à jour vers le domaine Santé et Formation Santé
        $responsePut = $this->actingAs($this->adminBerlin)->put(route('users.update', $teacherUser), [
            'name' => 'Prof Santé Modifié',
            'email' => 'teacher.update@ec.test',
            'role' => User::ROLE_TEACHER,
            'status' => 'active',
            'domain' => 'sante',
            'formation_id' => $formation2->id,
        ]);

        $responsePut->assertRedirect(route('users.show', $teacherUser));

        $teacherUser->refresh();
        $this->assertEquals('sante', $teacherUser->domain);
        $this->assertEquals($formation2->id, $teacherUser->formation_id);
    }

    /**
     * 6. Compatibilité Planning de classe : classes() fonctionne indépendamment sur le profil Teacher sans conflit.
     */
    public function test_teacher_class_planning_compatibility(): void
    {
        $formation = Formation::create([
            'center_id' => $this->centerBerlin->id,
            'domain' => 'informatique',
            'name' => 'Dev Web',
            'code' => 'DWB',
            'duration_hours' => 60,
            'price' => 400,
            'status' => 'active',
        ]);

        $teacherUser = User::factory()->teacher($this->centerBerlin->id)->create([
            'name' => 'Prof Planning',
            'domain' => 'informatique',
            'formation_id' => $formation->id,
        ]);

        $teacherProfile = Teacher::create([
            'user_id' => $teacherUser->id,
            'center_id' => $this->centerBerlin->id,
            'domain' => 'informatique',
            'formation_id' => $formation->id,
            'teacher_number' => 'T-TEST-001',
            'first_name' => 'Prof',
            'last_name' => 'Planning',
            'gender' => 'male',
            'status' => 'active',
        ]);

        // Créer une classe pour cette formation
        $classA = \App\Models\ClassRoom::create([
            'center_id' => $this->centerBerlin->id,
            'formation_id' => $formation->id,
            'name' => 'Classe DW-1',
            'code' => 'DW1',
            'start_date' => '2026-11-01',
            'end_date' => '2026-12-01',
            'status' => 'active',
        ]);

        // La classe n'est pas encore assignée au professeur via class_teacher
        $this->assertCount(0, $teacherProfile->classes);

        // Assigner la classe manuellement
        $teacherProfile->classes()->attach($classA->id);
        $teacherProfile->load('classes');

        $this->assertCount(1, $teacherProfile->classes);
        $this->assertEquals('Classe DW-1', $teacherProfile->classes->first()->name);
    }
}
