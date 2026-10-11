<?php

namespace Tests\Feature;

use App\Models\Center;
use App\Models\Room;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RoomManagementTest extends TestCase
{
    use RefreshDatabase;

    protected User $superAdmin;
    protected User $adminBerlin;
    protected User $adminMunich;
    protected User $teacherBerlin;
    protected User $studentBerlin;
    protected Center $berlin;
    protected Center $munich;

    protected function setUp(): void
    {
        parent::setUp();

        $this->berlin = Center::create([
            'name' => 'Centre Berlin',
            'code' => 'BER',
            'address' => 'Alexanderplatz 1',
            'status' => 'active',
        ]);

        $this->munich = Center::create([
            'name' => 'Centre Munich',
            'code' => 'MUN',
            'address' => 'Marienplatz 1',
            'status' => 'active',
        ]);

        $this->superAdmin = User::create([
            'name' => 'Super Admin',
            'email' => 'super@plateforme.test',
            'password' => bcrypt('password123'),
            'role' => 'super_admin',
            'center_id' => null,
            'status' => 'active',
        ]);

        $this->adminBerlin = User::create([
            'name' => 'Admin Berlin',
            'email' => 'admin.berlin@plateforme.test',
            'password' => bcrypt('password123'),
            'role' => 'center_admin',
            'center_id' => $this->berlin->id,
            'status' => 'active',
        ]);

        $this->adminMunich = User::create([
            'name' => 'Admin Munich',
            'email' => 'admin.munich@plateforme.test',
            'password' => bcrypt('password123'),
            'role' => 'center_admin',
            'center_id' => $this->munich->id,
            'status' => 'active',
        ]);

        $this->teacherBerlin = User::create([
            'name' => 'Prof Berlin',
            'email' => 'prof.berlin@plateforme.test',
            'password' => bcrypt('password123'),
            'role' => 'teacher',
            'center_id' => $this->berlin->id,
            'status' => 'active',
        ]);

        $this->studentBerlin = User::create([
            'name' => 'Eleve Berlin',
            'email' => 'eleve.berlin@plateforme.test',
            'password' => bcrypt('password123'),
            'role' => 'student',
            'center_id' => $this->berlin->id,
            'status' => 'active',
        ]);
    }

    // --- 1. CRUD Super Admin ---

    public function test_super_admin_can_view_rooms_list_and_filter_by_center(): void
    {
        Room::create([
            'center_id' => $this->berlin->id,
            'name' => 'Salle Berlin',
            'code' => 'ROOM-B1',
            'capacity' => 20,
            'status' => 'active',
        ]);

        Room::create([
            'center_id' => $this->munich->id,
            'name' => 'Salle Munich',
            'code' => 'ROOM-M1',
            'capacity' => 25,
            'status' => 'active',
        ]);

        $response = $this->actingAs($this->superAdmin)->get(route('rooms.index'));
        $response->assertOk();
        $response->assertSee('ROOM-B1');
        $response->assertSee('ROOM-M1');

        // Filter by Berlin
        $filterResponse = $this->actingAs($this->superAdmin)->get(route('rooms.index', ['center_id' => $this->berlin->id]));
        $filterResponse->assertOk();
        $filterResponse->assertSee('ROOM-B1');
        $filterResponse->assertDontSee('ROOM-M1');
    }

    public function test_super_admin_can_create_room(): void
    {
        $response = $this->actingAs($this->superAdmin)->post(route('rooms.store'), [
            'center_id' => $this->berlin->id,
            'name' => 'Salle Informatique',
            'code' => 'INFO-01',
            'capacity' => 18,
            'building' => 'Bâtiment A',
            'floor' => '2ème étage',
            'equipment' => '18 PC, Vidéoprojecteur',
            'description' => 'Salle dédiée au développement web',
            'status' => 'active',
        ]);

        $response->assertRedirect(route('rooms.show', Room::first()));
        $this->assertDatabaseHas('rooms', [
            'center_id' => $this->berlin->id,
            'code' => 'INFO-01',
            'capacity' => 18,
            'status' => 'active',
        ]);
    }

    public function test_super_admin_can_view_room(): void
    {
        $room = Room::create([
            'center_id' => $this->berlin->id,
            'name' => 'Salle Polyvalente',
            'code' => 'ROOM-P1',
            'capacity' => 30,
            'status' => 'active',
        ]);

        $response = $this->actingAs($this->superAdmin)->get(route('rooms.show', $room));
        $response->assertOk();
        $response->assertSee('Salle Polyvalente');
        $response->assertSee('ROOM-P1');
    }

    public function test_super_admin_can_update_room(): void
    {
        $room = Room::create([
            'center_id' => $this->berlin->id,
            'name' => 'Salle Alpha',
            'code' => 'ALPHA-01',
            'capacity' => 20,
            'status' => 'active',
        ]);

        $response = $this->actingAs($this->superAdmin)->put(route('rooms.update', $room), [
            'center_id' => $this->berlin->id,
            'name' => 'Salle Alpha Renommée',
            'code' => 'ALPHA-01',
            'capacity' => 25,
            'status' => 'active',
        ]);

        $response->assertRedirect(route('rooms.show', $room));
        $this->assertDatabaseHas('rooms', [
            'id' => $room->id,
            'name' => 'Salle Alpha Renommée',
            'capacity' => 25,
        ]);
    }

    public function test_super_admin_can_delete_room(): void
    {
        $room = Room::create([
            'center_id' => $this->berlin->id,
            'name' => 'Salle Éphémère',
            'code' => 'TEMP-01',
            'capacity' => 10,
            'status' => 'active',
        ]);

        $response = $this->actingAs($this->superAdmin)->delete(route('rooms.destroy', $room));
        $response->assertRedirect(route('rooms.index'));
        $this->assertDatabaseMissing('rooms', ['id' => $room->id]);
    }

    // --- 2. Admin Centre Operations & Isolation ---

    public function test_center_admin_can_view_only_own_center_rooms(): void
    {
        Room::create([
            'center_id' => $this->berlin->id,
            'name' => 'Salle Berlin',
            'code' => 'BER-101',
            'capacity' => 20,
            'status' => 'active',
        ]);

        Room::create([
            'center_id' => $this->munich->id,
            'name' => 'Salle Munich',
            'code' => 'MUN-101',
            'capacity' => 20,
            'status' => 'active',
        ]);

        $response = $this->actingAs($this->adminBerlin)->get(route('rooms.index'));
        $response->assertOk();
        $response->assertSee('BER-101');
        $response->assertDontSee('MUN-101');
    }

    public function test_center_admin_cannot_see_other_center_rooms_via_query_manipulation(): void
    {
        Room::create([
            'center_id' => $this->munich->id,
            'name' => 'Salle Munich Secrète',
            'code' => 'MUN-SECRET',
            'capacity' => 20,
            'status' => 'active',
        ]);

        // Attempting ?center_id=munich
        $response = $this->actingAs($this->adminBerlin)->get(route('rooms.index', ['center_id' => $this->munich->id]));
        $response->assertOk();
        $response->assertDontSee('MUN-SECRET');
    }

    public function test_center_admin_can_create_room_in_own_center(): void
    {
        $response = $this->actingAs($this->adminBerlin)->post(route('rooms.store'), [
            'name' => 'Salle Berlin 102',
            'code' => 'BER-102',
            'capacity' => 22,
            'status' => 'active',
        ]);

        $room = Room::where('code', 'BER-102')->first();
        $response->assertRedirect(route('rooms.show', $room));
        $this->assertDatabaseHas('rooms', [
            'center_id' => $this->berlin->id,
            'code' => 'BER-102',
        ]);
    }

    public function test_center_admin_cannot_spoof_center_id_on_create(): void
    {
        // Admin Berlin tries to create a room inside Munich
        $response = $this->actingAs($this->adminBerlin)->post(route('rooms.store'), [
            'center_id' => $this->munich->id,
            'name' => 'Salle Spoofée',
            'code' => 'SPOOF-01',
            'capacity' => 15,
            'status' => 'active',
        ]);

        $spoofedRoom = Room::where('code', 'SPOOF-01')->first();
        $response->assertRedirect(route('rooms.show', $spoofedRoom));

        // The room MUST be assigned to Berlin (forced server-side), NOT Munich
        $this->assertDatabaseMissing('rooms', [
            'center_id' => $this->munich->id,
            'code' => 'SPOOF-01',
        ]);

        $this->assertDatabaseHas('rooms', [
            'center_id' => $this->berlin->id,
            'code' => 'SPOOF-01',
        ]);
    }

    public function test_center_admin_can_view_own_room(): void
    {
        $room = Room::create([
            'center_id' => $this->berlin->id,
            'name' => 'Salle Berlin Test',
            'code' => 'BER-TEST',
            'capacity' => 15,
            'status' => 'active',
        ]);

        $response = $this->actingAs($this->adminBerlin)->get(route('rooms.show', $room));
        $response->assertOk();
        $response->assertSee('BER-TEST');
    }

    public function test_center_admin_cannot_view_other_center_room(): void
    {
        $roomMunich = Room::create([
            'center_id' => $this->munich->id,
            'name' => 'Salle Munich Privée',
            'code' => 'MUN-PRIV',
            'capacity' => 15,
            'status' => 'active',
        ]);

        $response = $this->actingAs($this->adminBerlin)->get(route('rooms.show', $roomMunich));
        $response->assertForbidden();
    }

    public function test_center_admin_cannot_edit_other_center_room(): void
    {
        $roomMunich = Room::create([
            'center_id' => $this->munich->id,
            'name' => 'Salle Munich Privée',
            'code' => 'MUN-PRIV',
            'capacity' => 15,
            'status' => 'active',
        ]);

        $response = $this->actingAs($this->adminBerlin)->get(route('rooms.edit', $roomMunich));
        $response->assertForbidden();
    }

    public function test_center_admin_cannot_update_other_center_room(): void
    {
        $roomMunich = Room::create([
            'center_id' => $this->munich->id,
            'name' => 'Salle Munich Privée',
            'code' => 'MUN-PRIV',
            'capacity' => 15,
            'status' => 'active',
        ]);

        $response = $this->actingAs($this->adminBerlin)->put(route('rooms.update', $roomMunich), [
            'name' => 'Tentative Hack',
            'code' => 'MUN-HACK',
            'capacity' => 15,
            'status' => 'active',
        ]);

        $response->assertForbidden();
    }

    public function test_center_admin_cannot_change_center_id_of_own_room_during_update(): void
    {
        $roomBerlin = Room::create([
            'center_id' => $this->berlin->id,
            'name' => 'Salle Berlin Immuable',
            'code' => 'BER-IMMUTABLE',
            'capacity' => 15,
            'status' => 'active',
        ]);

        // Trying to pass center_id = munich
        $response = $this->actingAs($this->adminBerlin)->put(route('rooms.update', $roomBerlin), [
            'center_id' => $this->munich->id,
            'name' => 'Salle Modifiée',
            'code' => 'BER-IMMUTABLE',
            'capacity' => 16,
            'status' => 'active',
        ]);

        $response->assertRedirect(route('rooms.show', $roomBerlin));

        // Center must strictly remain Berlin
        $roomBerlin->refresh();
        $this->assertEquals($this->berlin->id, $roomBerlin->center_id);
    }

    public function test_center_admin_cannot_delete_other_center_room(): void
    {
        $roomMunich = Room::create([
            'center_id' => $this->munich->id,
            'name' => 'Salle Munich Privée',
            'code' => 'MUN-PRIV',
            'capacity' => 15,
            'status' => 'active',
        ]);

        $response = $this->actingAs($this->adminBerlin)->delete(route('rooms.destroy', $roomMunich));
        $response->assertForbidden();
        $this->assertDatabaseHas('rooms', ['id' => $roomMunich->id]);
    }

    // --- 3. Uniqueness scoped by Center ---

    public function test_duplicate_code_in_same_center_is_rejected(): void
    {
        Room::create([
            'center_id' => $this->berlin->id,
            'name' => 'Salle Originale',
            'code' => 'ROOM-SAME',
            'capacity' => 20,
            'status' => 'active',
        ]);

        $response = $this->actingAs($this->adminBerlin)->post(route('rooms.store'), [
            'name' => 'Salle Doublon',
            'code' => 'ROOM-SAME',
            'capacity' => 25,
            'status' => 'active',
        ]);

        $response->assertSessionHasErrors('code');
    }

    public function test_same_code_can_coexist_in_different_centers(): void
    {
        // Berlin creates ROOM-01
        Room::create([
            'center_id' => $this->berlin->id,
            'name' => 'Salle Berlin 01',
            'code' => 'ROOM-01',
            'capacity' => 20,
            'status' => 'active',
        ]);

        // Munich creates ROOM-01 -> MUST be accepted
        $response = $this->actingAs($this->adminMunich)->post(route('rooms.store'), [
            'name' => 'Salle Munich 01',
            'code' => 'ROOM-01',
            'capacity' => 20,
            'status' => 'active',
        ]);

        $munichRoom = Room::where('center_id', $this->munich->id)->where('code', 'ROOM-01')->first();
        $response->assertRedirect(route('rooms.show', $munichRoom));
        $this->assertEquals(2, Room::where('code', 'ROOM-01')->count());
    }

    // --- 4. Validation Rules ---

    public function test_capacity_must_be_positive_integer_and_strictly_greater_than_zero(): void
    {
        // Zero capacity
        $responseZero = $this->actingAs($this->adminBerlin)->post(route('rooms.store'), [
            'name' => 'Salle Zéro',
            'code' => 'ZERO-01',
            'capacity' => 0,
            'status' => 'active',
        ]);
        $responseZero->assertSessionHasErrors('capacity');

        // Negative capacity
        $responseNeg = $this->actingAs($this->adminBerlin)->post(route('rooms.store'), [
            'name' => 'Salle Négative',
            'code' => 'NEG-01',
            'capacity' => -5,
            'status' => 'active',
        ]);
        $responseNeg->assertSessionHasErrors('capacity');
    }

    public function test_name_and_code_are_required(): void
    {
        $response = $this->actingAs($this->adminBerlin)->post(route('rooms.store'), [
            'capacity' => 15,
            'status' => 'active',
        ]);

        $response->assertSessionHasErrors(['name', 'code']);
    }

    public function test_invalid_status_is_rejected(): void
    {
        $response = $this->actingAs($this->adminBerlin)->post(route('rooms.store'), [
            'name' => 'Salle Statut Invalide',
            'code' => 'STAT-01',
            'capacity' => 15,
            'status' => 'nonexistent_status',
        ]);

        $response->assertSessionHasErrors('status');
    }

    // --- 5. Status Toggle ---

    public function test_toggle_status_active_to_inactive_and_back(): void
    {
        $room = Room::create([
            'center_id' => $this->berlin->id,
            'name' => 'Salle Toggle',
            'code' => 'TOGGLE-01',
            'capacity' => 15,
            'status' => 'active',
        ]);

        // Toggle to inactive
        $response1 = $this->actingAs($this->adminBerlin)->patch(route('rooms.toggle-status', $room));
        $response1->assertRedirect();
        $this->assertEquals('inactive', $room->fresh()->status);
        $this->assertTrue($room->fresh()->isInactive());

        // Toggle back to active
        $response2 = $this->actingAs($this->adminBerlin)->patch(route('rooms.toggle-status', $room));
        $response2->assertRedirect();
        $this->assertEquals('active', $room->fresh()->status);
        $this->assertTrue($room->fresh()->isActive());
    }

    public function test_center_admin_cannot_toggle_status_of_other_center_room(): void
    {
        $roomMunich = Room::create([
            'center_id' => $this->munich->id,
            'name' => 'Salle Munich Toggle',
            'code' => 'MUN-TOGGLE',
            'capacity' => 15,
            'status' => 'active',
        ]);

        $response = $this->actingAs($this->adminBerlin)->patch(route('rooms.toggle-status', $roomMunich));
        $response->assertForbidden();
    }

    // --- 6. Role Security (Teachers and Students Forbidden) ---

    public function test_teacher_cannot_access_rooms_management(): void
    {
        $room = Room::create([
            'center_id' => $this->berlin->id,
            'name' => 'Salle Berlin',
            'code' => 'BER-SEC-01',
            'capacity' => 20,
            'status' => 'active',
        ]);

        $this->actingAs($this->teacherBerlin)->get(route('rooms.index'))->assertForbidden();
        $this->actingAs($this->teacherBerlin)->get(route('rooms.create'))->assertForbidden();
        $this->actingAs($this->teacherBerlin)->get(route('rooms.show', $room))->assertForbidden();
        $this->actingAs($this->teacherBerlin)->get(route('rooms.edit', $room))->assertForbidden();
        $this->actingAs($this->teacherBerlin)->post(route('rooms.store'), ['name' => 'T', 'code' => 'T', 'capacity' => 10])->assertForbidden();
        $this->actingAs($this->teacherBerlin)->put(route('rooms.update', $room), ['name' => 'T', 'code' => 'T', 'capacity' => 10])->assertForbidden();
        $this->actingAs($this->teacherBerlin)->delete(route('rooms.destroy', $room))->assertForbidden();
        $this->actingAs($this->teacherBerlin)->patch(route('rooms.toggle-status', $room))->assertForbidden();
    }

    public function test_student_cannot_access_rooms_management(): void
    {
        $room = Room::create([
            'center_id' => $this->berlin->id,
            'name' => 'Salle Berlin',
            'code' => 'BER-SEC-02',
            'capacity' => 20,
            'status' => 'active',
        ]);

        $this->actingAs($this->studentBerlin)->get(route('rooms.index'))->assertForbidden();
        $this->actingAs($this->studentBerlin)->get(route('rooms.create'))->assertForbidden();
        $this->actingAs($this->studentBerlin)->get(route('rooms.show', $room))->assertForbidden();
        $this->actingAs($this->studentBerlin)->get(route('rooms.edit', $room))->assertForbidden();
        $this->actingAs($this->studentBerlin)->post(route('rooms.store'), ['name' => 'S', 'code' => 'S', 'capacity' => 10])->assertForbidden();
        $this->actingAs($this->studentBerlin)->put(route('rooms.update', $room), ['name' => 'S', 'code' => 'S', 'capacity' => 10])->assertForbidden();
        $this->actingAs($this->studentBerlin)->delete(route('rooms.destroy', $room))->assertForbidden();
        $this->actingAs($this->studentBerlin)->patch(route('rooms.toggle-status', $room))->assertForbidden();
    }

    // --- 7. Eloquent Relationships ---

    public function test_center_rooms_and_room_center_relationships(): void
    {
        $room = Room::create([
            'center_id' => $this->berlin->id,
            'name' => 'Salle Relation',
            'code' => 'REL-01',
            'capacity' => 20,
            'status' => 'active',
        ]);

        $this->assertTrue($this->berlin->rooms->contains($room));
        $this->assertEquals($this->berlin->id, $room->center->id);
    }
}
