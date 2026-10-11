<?php

namespace Tests\Feature;

use App\Models\Center;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class CenterTest extends TestCase
{
    use RefreshDatabase;

    protected User $superAdmin;

    protected function setUp(): void
    {
        parent::setUp();

        $this->superAdmin = User::factory()->superAdmin()->create([
            'email' => 'superadmin@centers-test.de',
        ]);
        $this->actingAs($this->superAdmin);
    }
    public function test_can_view_centers_index_page(): void
    {
        Center::factory()->create([
            'name' => 'Centre Test Berlin',
            'code' => 'BERLIN_TEST',
        ]);

        $response = $this->get(route('centers.index'));

        $response->assertStatus(200);
        $response->assertSee('Centre Test Berlin');
        $response->assertSee('BERLIN_TEST');
    }

    /**
     * Teste la création d'un centre via le formulaire.
     */
    public function test_can_create_a_center(): void
    {
        Storage::fake('public');

        $data = [
            'name'        => 'Centre Francfort',
            'code'        => 'FRANCFORT',
            'address'     => 'Mainzer Landstrasse 50',
            'city'        => 'Francfort',
            'postal_code' => '60329',
            'country'     => 'Germany',
            'phone'       => '+49 69 123456',
            'email'       => 'francfort@education-centers.de',
            'status'      => 'active',
        ];

        $response = $this->post(route('centers.store'), $data);

        $this->assertDatabaseHas('centers', [
            'name' => 'Centre Francfort',
            'code' => 'FRANCFORT',
            'city' => 'Francfort',
        ]);

        $center = Center::where('code', 'FRANCFORT')->first();
        $response->assertRedirect(route('centers.show', $center));
    }

    /**
     * Teste la modification d'un centre.
     */
    public function test_can_update_a_center(): void
    {
        $center = Center::create([
            'name'    => 'Ancien Nom',
            'code'    => 'OLD_CODE',
            'city'    => 'Hambourg',
            'country' => 'Germany',
            'status'  => 'active',
        ]);

        $updateData = [
            'name'    => 'Nouveau Nom Hambourg',
            'code'    => 'OLD_CODE', // conserve le code
            'city'    => 'Hambourg',
            'country' => 'Germany',
            'status'  => 'inactive',
        ];

        $response = $this->put(route('centers.update', $center), $updateData);

        $response->assertRedirect(route('centers.show', $center));
        $this->assertDatabaseHas('centers', [
            'id'     => $center->id,
            'name'   => 'Nouveau Nom Hambourg',
            'status' => 'inactive',
        ]);
    }

    /**
     * Teste la validation de l'unicité du code d'un centre.
     */
    public function test_center_code_must_be_unique(): void
    {
        Center::create([
            'name'    => 'Centre Unique 1',
            'code'    => 'UNIQUE_CODE',
            'country' => 'Germany',
            'status'  => 'active',
        ]);

        $response = $this->post(route('centers.store'), [
            'name'    => 'Centre Unique 2',
            'code'    => 'UNIQUE_CODE',
            'country' => 'Germany',
            'status'  => 'active',
        ]);

        $response->assertSessionHasErrors('code');
        $this->assertDatabaseCount('centers', 1);
    }

    /**
     * Teste la relation User -> Center.
     */
    public function test_user_belongs_to_center_relation(): void
    {
        $center = Center::create([
            'name'    => 'Centre Cologne',
            'code'    => 'COLOGNE',
            'country' => 'Germany',
            'status'  => 'active',
        ]);

        $user = User::factory()->create([
            'name'      => 'Utilisateur Cologne',
            'center_id' => $center->id,
        ]);

        $this->assertNotNull($user->center);
        $this->assertInstanceOf(Center::class, $user->center);
        $this->assertEquals($center->id, $user->center->id);
        $this->assertEquals('COLOGNE', $user->center->code);
    }

    /**
     * Teste la relation Center -> Users.
     */
    public function test_center_has_many_users_relation(): void
    {
        $center = Center::create([
            'name'    => 'Centre Stuttgart',
            'code'    => 'STUTTGART',
            'country' => 'Germany',
            'status'  => 'active',
        ]);

        User::factory()->count(3)->create([
            'center_id' => $center->id,
        ]);

        $this->assertCount(3, $center->users);
        $this->assertTrue($center->hasLinkedUsers());
    }

    /**
     * Teste la possibilité pour center_id d'être NULL pour un Super Admin.
     */
    public function test_user_can_have_null_center_id_as_super_admin(): void
    {
        $superAdmin = User::factory()->create([
            'name'      => 'Super Admin Test',
            'email'     => 'superadmin@test.de',
            'center_id' => null,
        ]);

        $this->assertNull($superAdmin->center_id);
        $this->assertNull($superAdmin->center);
        $this->assertTrue($superAdmin->isSuperAdmin());
    }

    /**
     * Teste que la suppression est bloquée si des utilisateurs sont rattachés.
     */
    public function test_cannot_delete_center_with_linked_users(): void
    {
        $center = Center::create([
            'name'    => 'Centre Protégé',
            'code'    => 'PROTECTED',
            'country' => 'Germany',
            'status'  => 'active',
        ]);

        User::factory()->create([
            'center_id' => $center->id,
        ]);

        $response = $this->delete(route('centers.destroy', $center));

        $response->assertSessionHas('error');
        $this->assertDatabaseHas('centers', ['id' => $center->id]);
    }

    /**
     * Teste la suppression réussie d'un centre sans utilisateurs rattachés.
     */
    public function test_can_delete_empty_center(): void
    {
        $center = Center::create([
            'name'    => 'Centre Vide',
            'code'    => 'EMPTY_CENTER',
            'country' => 'Germany',
            'status'  => 'active',
        ]);

        $response = $this->delete(route('centers.destroy', $center));

        $response->assertRedirect(route('centers.index'));
        $this->assertDatabaseMissing('centers', ['id' => $center->id]);
    }

    /**
     * Teste le basculement de statut (toggleStatus).
     */
    public function test_can_toggle_center_status(): void
    {
        $center = Center::create([
            'name'    => 'Centre Toggle',
            'code'    => 'TOGGLE',
            'country' => 'Germany',
            'status'  => 'active',
        ]);

        $this->patch(route('centers.toggle-status', $center));
        $this->assertEquals('inactive', $center->fresh()->status);

        $this->patch(route('centers.toggle-status', $center));
        $this->assertEquals('active', $center->fresh()->status);
    }
}
