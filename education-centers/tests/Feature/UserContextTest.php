<?php

namespace Tests\Feature;

use App\Models\Center;
use App\Models\Student;
use App\Models\User;
use App\Support\DomainRegistry;
use App\Support\UserContext;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Phase 3 — Contexte utilisateur centralisé + sécurité.
 *
 * Vérifie que le middleware ResolveDomainContext valide le domaine actif côté
 * serveur, force le centre pour un Admin Centre, et que le contexte reste cohérent
 * quel que soit le domaine sélectionné (le rôle ne change pas en changeant de domaine).
 */
class UserContextTest extends TestCase
{
    use RefreshDatabase;

    private Center $berlin;
    private Center $munich;
    private User $superAdmin;
    private User $adminBerlin;
    private User $adminMunich;

    protected function setUp(): void
    {
        parent::setUp();

        $this->berlin = Center::create(['name' => 'Berlin', 'code' => 'BER', 'address' => 'A', 'status' => 'active']);
        $this->munich = Center::create(['name' => 'Munich', 'code' => 'MUN', 'address' => 'B', 'status' => 'active']);

        $this->superAdmin = User::factory()->superAdmin()->create();
        $this->adminBerlin = User::factory()->centerAdmin($this->berlin->id)->create();
        $this->adminMunich = User::factory()->centerAdmin($this->munich->id)->create();
    }

    private function makeStudent(Center $center, string $number, string $first): Student
    {
        $user = User::factory()->create(['role' => 'student', 'center_id' => $center->id]);

        return Student::create([
            'user_id' => $user->id, 'center_id' => $center->id, 'student_number' => $number,
            'first_name' => $first, 'last_name' => 'X', 'date_of_birth' => '2000-01-01',
            'gender' => 'male', 'status' => 'active',
        ]);
    }

    public function test_registry_uses_canonical_fallback_when_table_empty(): void
    {
        // En tests, la table domains est vide : on retombe sur la liste canonique.
        $this->assertFalse(DomainRegistry::hasDatabaseRows());
        $this->assertTrue(DomainRegistry::isValid('sante'));
        $this->assertTrue(DomainRegistry::isValid('informatique'));
        $this->assertFalse(DomainRegistry::isValid('cuisine'));
        $this->assertFalse(DomainRegistry::isValid(null));
        $this->assertFalse(DomainRegistry::isValid('all'));
    }

    public function test_invalid_active_domain_is_rejected_and_reset(): void
    {
        $response = $this->actingAs($this->superAdmin)
            ->withSession(['active_domain' => 'domaine-fantaisiste'])
            ->get(route('students.index'));

        $response->assertRedirect(route('super-admin.domaines'));
        $this->assertNull(session('active_domain'));
    }

    public function test_valid_active_domain_is_preserved_in_context(): void
    {
        $response = $this->actingAs($this->superAdmin)
            ->withSession(['active_domain' => 'sante'])
            ->get(route('students.index'));

        $response->assertStatus(200);

        /** @var UserContext $ctx */
        $ctx = app(UserContext::class);
        $this->assertSame('sante', $ctx->domainSlug());
        $this->assertTrue($ctx->hasDomain());
        // Super Admin sans ?center_id => pas de restriction centre.
        $this->assertNull($ctx->centerId());
        $this->assertTrue($ctx->canSwitchCenter());
    }

    public function test_center_admin_context_is_forced_to_own_center(): void
    {
        // Même en tentant d'imposer un autre centre via l'URL, le contexte reste le sien.
        $this->actingAs($this->adminBerlin)
            ->get(route('students.index', ['center_id' => $this->munich->id]));

        /** @var UserContext $ctx */
        $ctx = app(UserContext::class);
        $this->assertSame($this->berlin->id, $ctx->centerId());
        $this->assertFalse($ctx->canSwitchCenter());
    }

    public function test_cross_center_student_direct_access_is_denied_by_server(): void
    {
        $foreignStudent = $this->makeStudent($this->munich, 'MUN-000001', 'Oscar');

        $response = $this->actingAs($this->adminBerlin)
            ->get(route('students.show', $foreignStudent));

        $response->assertStatus(403);
    }

    public function test_role_is_not_altered_by_switching_domain(): void
    {
        $this->assertSame('center_admin', $this->adminBerlin->role);

        // Changer de domaine ne change jamais le rôle de l'utilisateur.
        $this->actingAs($this->adminBerlin)->withSession(['active_domain' => 'informatique'])
            ->get(route('dashboard'));

        $this->adminBerlin->refresh();
        $this->assertSame('center_admin', $this->adminBerlin->role);
        $this->assertTrue(app(UserContext::class)->isCenterAdmin());
    }

    public function test_helper_context_resolves_empty_context_outside_request(): void
    {
        // Aucun middleware exécuté => contexte vide, aucun filtrage implicite.
        $ctx = user_context();
        $this->assertInstanceOf(UserContext::class, $ctx);
        $this->assertNull($ctx->centerId());
        $this->assertNull($ctx->domainSlug());
    }

    public function test_within_context_scope_filters_by_center(): void
    {
        $this->makeStudent($this->berlin, 'BER-000001', 'Anna');
        $this->makeStudent($this->munich, 'MUN-000001', 'Oscar');

        $ctx = new UserContext($this->adminBerlin, null, $this->berlin->id);

        $ids = Student::withinContext($ctx)->pluck('student_number')->all();

        $this->assertContains('BER-000001', $ids);
        $this->assertNotContains('MUN-000001', $ids);
    }
}
