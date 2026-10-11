<?php

namespace Tests\Feature;

use App\Models\Center;
use App\Models\ClassRoom;
use App\Models\Formation;
use App\Models\Room;
use App\Models\Schedule;
use App\Models\Teacher;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ScheduleManagementTest extends TestCase
{
    use RefreshDatabase;

    protected User $superAdmin;
    protected User $adminBerlin;
    protected User $adminMunich;
    protected User $teacherBerlinUser;
    protected User $studentBerlinUser;
    protected Center $berlin;
    protected Center $munich;
    protected Formation $formationBerlin;
    protected Formation $formationMunich;
    protected ClassRoom $classBerlin;
    protected ClassRoom $classMunich;
    protected Teacher $teacherBerlin;
    protected Teacher $teacherMunich;
    protected Room $roomBerlin;
    protected Room $roomMunich;

    protected function setUp(): void
    {
        parent::setUp();

        // Centres
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

        // Formations
        $this->formationBerlin = Formation::create([
            'center_id' => $this->berlin->id,
            'name' => 'Deutsch A1',
            'code' => 'BER-A1',
            'duration_hours' => 100,
            'price' => 400,
            'currency' => 'EUR',
            'status' => 'active',
        ]);

        $this->formationMunich = Formation::create([
            'center_id' => $this->munich->id,
            'name' => 'Deutsch A1 Munich',
            'code' => 'MUN-A1',
            'duration_hours' => 100,
            'price' => 400,
            'currency' => 'EUR',
            'status' => 'active',
        ]);

        // Classes
        $this->classBerlin = ClassRoom::create([
            'center_id' => $this->berlin->id,
            'formation_id' => $this->formationBerlin->id,
            'name' => 'Classe Berlin A1-01',
            'code' => 'A1-01',
            'capacity' => 20,
            'status' => 'active',
        ]);

        $this->classMunich = ClassRoom::create([
            'center_id' => $this->munich->id,
            'formation_id' => $this->formationMunich->id,
            'name' => 'Classe Munich A1-01',
            'code' => 'A1-01',
            'capacity' => 20,
            'status' => 'active',
        ]);

        // Utilisateurs et Enseignants
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

        $this->teacherBerlinUser = User::create([
            'name' => 'Johannes Kohl',
            'email' => 'johannes@plateforme.test',
            'password' => bcrypt('password123'),
            'role' => 'teacher',
            'center_id' => $this->berlin->id,
            'status' => 'active',
        ]);

        $this->teacherBerlin = Teacher::create([
            'user_id' => $this->teacherBerlinUser->id,
            'center_id' => $this->berlin->id,
            'teacher_number' => 'BER-T-000001',
            'first_name' => 'Johannes',
            'last_name' => 'Kohl',
            'gender' => 'male',
            'status' => 'active',
        ]);

        $teacherMunichUser = User::create([
            'name' => 'Greta Schneider',
            'email' => 'greta@plateforme.test',
            'password' => bcrypt('password123'),
            'role' => 'teacher',
            'center_id' => $this->munich->id,
            'status' => 'active',
        ]);

        $this->teacherMunich = Teacher::create([
            'user_id' => $teacherMunichUser->id,
            'center_id' => $this->munich->id,
            'teacher_number' => 'MUN-T-000001',
            'first_name' => 'Greta',
            'last_name' => 'Schneider',
            'gender' => 'female',
            'status' => 'active',
        ]);

        $this->studentBerlinUser = User::create([
            'name' => 'Hans Student',
            'email' => 'hans@plateforme.test',
            'password' => bcrypt('password123'),
            'role' => 'student',
            'center_id' => $this->berlin->id,
            'status' => 'active',
        ]);

        // Salles
        $this->roomBerlin = Room::create([
            'center_id' => $this->berlin->id,
            'name' => 'Salle Berlin 01',
            'code' => 'ROOM-01',
            'capacity' => 20,
            'status' => 'active',
        ]);

        $this->roomMunich = Room::create([
            'center_id' => $this->munich->id,
            'name' => 'Salle Munich 01',
            'code' => 'ROOM-01',
            'capacity' => 20,
            'status' => 'active',
        ]);

        // Affectation par défaut : Johannes enseigne à la classe Berlin A1-01
        $this->classBerlin->teachers()->attach($this->teacherBerlin->id);
        // Greta enseigne à la classe Munich A1-01
        $this->classMunich->teachers()->attach($this->teacherMunich->id);
    }

    // --- 1. CRUD Super Admin ---

    public function test_super_admin_can_create_schedule(): void
    {
        $response = $this->actingAs($this->superAdmin)->post(route('schedules.store'), [
            'center_id' => $this->berlin->id,
            'class_id' => $this->classBerlin->id,
            'teacher_id' => $this->teacherBerlin->id,
            'room_id' => $this->roomBerlin->id,
            'date' => '2026-10-25',
            'start_time' => '09:00',
            'end_time' => '11:00',
            'title' => 'Cours Grammaire',
            'type' => 'course',
            'status' => 'scheduled',
        ]);

        $schedule = Schedule::first();
        $response->assertRedirect(route('schedules.show', $schedule));
        $this->assertDatabaseHas('schedules', [
            'center_id' => $this->berlin->id,
            'class_id' => $this->classBerlin->id,
            'title' => 'Cours Grammaire',
        ]);
    }

    public function test_super_admin_can_view_schedule(): void
    {
        $schedule = Schedule::create([
            'center_id' => $this->berlin->id,
            'class_id' => $this->classBerlin->id,
            'teacher_id' => $this->teacherBerlin->id,
            'room_id' => $this->roomBerlin->id,
            'date' => '2026-10-25',
            'start_time' => '09:00',
            'end_time' => '11:00',
            'title' => 'Cours Test',
            'type' => 'course',
            'status' => 'scheduled',
        ]);

        $response = $this->actingAs($this->superAdmin)->get(route('schedules.show', $schedule));
        $response->assertOk();
        $response->assertSee('Cours Test');
    }

    public function test_super_admin_can_update_schedule(): void
    {
        $schedule = Schedule::create([
            'center_id' => $this->berlin->id,
            'class_id' => $this->classBerlin->id,
            'teacher_id' => $this->teacherBerlin->id,
            'room_id' => $this->roomBerlin->id,
            'date' => '2026-10-25',
            'start_time' => '09:00',
            'end_time' => '11:00',
            'title' => 'Cours Ancien',
            'type' => 'course',
            'status' => 'scheduled',
        ]);

        $response = $this->actingAs($this->superAdmin)->put(route('schedules.update', $schedule), [
            'center_id' => $this->berlin->id,
            'class_id' => $this->classBerlin->id,
            'teacher_id' => $this->teacherBerlin->id,
            'room_id' => $this->roomBerlin->id,
            'date' => '2026-10-25',
            'start_time' => '09:00',
            'end_time' => '11:00',
            'title' => 'Cours Modifié',
            'type' => 'workshop',
            'status' => 'scheduled',
        ]);

        $response->assertRedirect(route('schedules.show', $schedule));
        $this->assertDatabaseHas('schedules', [
            'id' => $schedule->id,
            'title' => 'Cours Modifié',
            'type' => 'workshop',
        ]);
    }

    public function test_super_admin_can_delete_schedule(): void
    {
        $schedule = Schedule::create([
            'center_id' => $this->berlin->id,
            'class_id' => $this->classBerlin->id,
            'teacher_id' => $this->teacherBerlin->id,
            'room_id' => $this->roomBerlin->id,
            'date' => '2026-10-25',
            'start_time' => '09:00',
            'end_time' => '11:00',
            'status' => 'scheduled',
        ]);

        $response = $this->actingAs($this->superAdmin)->delete(route('schedules.destroy', $schedule));
        $response->assertRedirect(route('schedules.index'));
        $this->assertDatabaseMissing('schedules', ['id' => $schedule->id]);
    }

    public function test_super_admin_can_toggle_schedule_status(): void
    {
        $schedule = Schedule::create([
            'center_id' => $this->berlin->id,
            'class_id' => $this->classBerlin->id,
            'teacher_id' => $this->teacherBerlin->id,
            'room_id' => $this->roomBerlin->id,
            'date' => '2026-10-25',
            'start_time' => '09:00',
            'end_time' => '11:00',
            'status' => 'scheduled',
        ]);

        $response = $this->actingAs($this->superAdmin)->patch(route('schedules.toggle-status', $schedule), [
            'status' => 'cancelled',
        ]);

        $response->assertRedirect();
        $this->assertEquals('cancelled', $schedule->fresh()->status);
        $this->assertTrue($schedule->fresh()->isCancelled());
    }

    // --- 2. Admin Centre Operations & Isolation ---

    public function test_admin_berlin_can_create_schedule_in_own_center(): void
    {
        $response = $this->actingAs($this->adminBerlin)->post(route('schedules.store'), [
            'class_id' => $this->classBerlin->id,
            'teacher_id' => $this->teacherBerlin->id,
            'room_id' => $this->roomBerlin->id,
            'date' => '2026-10-26',
            'start_time' => '14:00',
            'end_time' => '16:00',
            'type' => 'course',
            'status' => 'scheduled',
        ]);

        $schedule = Schedule::whereDate('date', '2026-10-26')->first();
        $this->assertNotNull($schedule);
        $response->assertRedirect(route('schedules.show', $schedule));
        $this->assertEquals($this->berlin->id, $schedule->center_id);
    }

    public function test_admin_berlin_can_view_own_schedules_and_not_munich(): void
    {
        $berlinSchedule = Schedule::create([
            'center_id' => $this->berlin->id,
            'class_id' => $this->classBerlin->id,
            'teacher_id' => $this->teacherBerlin->id,
            'room_id' => $this->roomBerlin->id,
            'date' => '2026-10-25',
            'start_time' => '09:00',
            'end_time' => '11:00',
            'title' => 'Berlin Seance',
            'status' => 'scheduled',
        ]);

        $munichSchedule = Schedule::create([
            'center_id' => $this->munich->id,
            'class_id' => $this->classMunich->id,
            'teacher_id' => $this->teacherMunich->id,
            'room_id' => $this->roomMunich->id,
            'date' => '2026-10-25',
            'start_time' => '09:00',
            'end_time' => '11:00',
            'title' => 'Munich Seance',
            'status' => 'scheduled',
        ]);

        $response = $this->actingAs($this->adminBerlin)->get(route('schedules.index'));
        $response->assertOk();
        $response->assertSee('Berlin Seance');
        $response->assertDontSee('Munich Seance');
    }

    public function test_admin_berlin_cannot_view_munich_schedule(): void
    {
        $munichSchedule = Schedule::create([
            'center_id' => $this->munich->id,
            'class_id' => $this->classMunich->id,
            'teacher_id' => $this->teacherMunich->id,
            'room_id' => $this->roomMunich->id,
            'date' => '2026-10-25',
            'start_time' => '09:00',
            'end_time' => '11:00',
            'status' => 'scheduled',
        ]);

        $this->actingAs($this->adminBerlin)->get(route('schedules.show', $munichSchedule))->assertForbidden();
    }

    public function test_admin_berlin_cannot_edit_or_update_munich_schedule(): void
    {
        $munichSchedule = Schedule::create([
            'center_id' => $this->munich->id,
            'class_id' => $this->classMunich->id,
            'teacher_id' => $this->teacherMunich->id,
            'room_id' => $this->roomMunich->id,
            'date' => '2026-10-25',
            'start_time' => '09:00',
            'end_time' => '11:00',
            'status' => 'scheduled',
        ]);

        $this->actingAs($this->adminBerlin)->get(route('schedules.edit', $munichSchedule))->assertForbidden();
        $this->actingAs($this->adminBerlin)->put(route('schedules.update', $munichSchedule), [
            'class_id' => $this->classMunich->id,
            'teacher_id' => $this->teacherMunich->id,
            'room_id' => $this->roomMunich->id,
            'date' => '2026-10-25',
            'start_time' => '09:00',
            'end_time' => '11:00',
            'type' => 'course',
            'status' => 'scheduled',
        ])->assertForbidden();
    }

    public function test_admin_berlin_cannot_delete_munich_schedule(): void
    {
        $munichSchedule = Schedule::create([
            'center_id' => $this->munich->id,
            'class_id' => $this->classMunich->id,
            'teacher_id' => $this->teacherMunich->id,
            'room_id' => $this->roomMunich->id,
            'date' => '2026-10-25',
            'start_time' => '09:00',
            'end_time' => '11:00',
            'status' => 'scheduled',
        ]);

        $this->actingAs($this->adminBerlin)->delete(route('schedules.destroy', $munichSchedule))->assertForbidden();
        $this->assertDatabaseHas('schedules', ['id' => $munichSchedule->id]);
    }

    public function test_admin_berlin_cannot_use_munich_class(): void
    {
        $response = $this->actingAs($this->adminBerlin)->post(route('schedules.store'), [
            'class_id' => $this->classMunich->id,
            'teacher_id' => $this->teacherBerlin->id,
            'room_id' => $this->roomBerlin->id,
            'date' => '2026-10-25',
            'start_time' => '09:00',
            'end_time' => '11:00',
            'type' => 'course',
            'status' => 'scheduled',
        ]);

        $response->assertSessionHasErrors('class_id');
    }

    public function test_admin_berlin_cannot_use_munich_teacher(): void
    {
        $response = $this->actingAs($this->adminBerlin)->post(route('schedules.store'), [
            'class_id' => $this->classBerlin->id,
            'teacher_id' => $this->teacherMunich->id,
            'room_id' => $this->roomBerlin->id,
            'date' => '2026-10-25',
            'start_time' => '09:00',
            'end_time' => '11:00',
            'type' => 'course',
            'status' => 'scheduled',
        ]);

        $response->assertSessionHasErrors('teacher_id');
    }

    public function test_admin_berlin_cannot_use_munich_room(): void
    {
        $response = $this->actingAs($this->adminBerlin)->post(route('schedules.store'), [
            'class_id' => $this->classBerlin->id,
            'teacher_id' => $this->teacherBerlin->id,
            'room_id' => $this->roomMunich->id,
            'date' => '2026-10-25',
            'start_time' => '09:00',
            'end_time' => '11:00',
            'type' => 'course',
            'status' => 'scheduled',
        ]);

        $response->assertSessionHasErrors('room_id');
    }

    public function test_center_id_spoofing_attempt_is_forced_to_admin_center(): void
    {
        $response = $this->actingAs($this->adminBerlin)->post(route('schedules.store'), [
            'center_id' => $this->munich->id, // Spoof attempt
            'class_id' => $this->classBerlin->id,
            'teacher_id' => $this->teacherBerlin->id,
            'room_id' => $this->roomBerlin->id,
            'date' => '2026-10-27',
            'start_time' => '09:00',
            'end_time' => '11:00',
            'type' => 'course',
            'status' => 'scheduled',
        ]);

        $schedule = Schedule::whereDate('date', '2026-10-27')->first();
        $this->assertNotNull($schedule);
        $this->assertEquals($this->berlin->id, $schedule->center_id);
    }

    // --- 3. Business Rules: Teacher, Class & Room Assignment and Status ---

    public function test_unassigned_teacher_to_class_is_rejected(): void
    {
        // Détacher Johannes de la classe Berlin A1-01
        $this->classBerlin->teachers()->detach($this->teacherBerlin->id);

        $response = $this->actingAs($this->adminBerlin)->post(route('schedules.store'), [
            'class_id' => $this->classBerlin->id,
            'teacher_id' => $this->teacherBerlin->id,
            'room_id' => $this->roomBerlin->id,
            'date' => '2026-10-25',
            'start_time' => '09:00',
            'end_time' => '11:00',
            'type' => 'course',
            'status' => 'scheduled',
        ]);

        $response->assertSessionHasErrors('teacher_id');
    }

    public function test_inactive_teacher_is_rejected(): void
    {
        $this->teacherBerlin->update(['status' => 'inactive']);

        $response = $this->actingAs($this->adminBerlin)->post(route('schedules.store'), [
            'class_id' => $this->classBerlin->id,
            'teacher_id' => $this->teacherBerlin->id,
            'room_id' => $this->roomBerlin->id,
            'date' => '2026-10-25',
            'start_time' => '09:00',
            'end_time' => '11:00',
            'type' => 'course',
            'status' => 'scheduled',
        ]);

        $response->assertSessionHasErrors('teacher_id');
    }

    public function test_inactive_class_is_rejected(): void
    {
        $this->classBerlin->update(['status' => 'inactive']);

        $response = $this->actingAs($this->adminBerlin)->post(route('schedules.store'), [
            'class_id' => $this->classBerlin->id,
            'teacher_id' => $this->teacherBerlin->id,
            'room_id' => $this->roomBerlin->id,
            'date' => '2026-10-25',
            'start_time' => '09:00',
            'end_time' => '11:00',
            'type' => 'course',
            'status' => 'scheduled',
        ]);

        $response->assertSessionHasErrors('class_id');
    }

    public function test_inactive_room_is_rejected(): void
    {
        $this->roomBerlin->update(['status' => 'inactive']);

        $response = $this->actingAs($this->adminBerlin)->post(route('schedules.store'), [
            'class_id' => $this->classBerlin->id,
            'teacher_id' => $this->teacherBerlin->id,
            'room_id' => $this->roomBerlin->id,
            'date' => '2026-10-25',
            'start_time' => '09:00',
            'end_time' => '11:00',
            'type' => 'course',
            'status' => 'scheduled',
        ]);

        $response->assertSessionHasErrors('room_id');
    }

    // --- 4. Horaires et Validation ---

    public function test_end_time_must_be_strictly_after_start_time(): void
    {
        // Same time
        $responseSame = $this->actingAs($this->adminBerlin)->post(route('schedules.store'), [
            'class_id' => $this->classBerlin->id,
            'teacher_id' => $this->teacherBerlin->id,
            'room_id' => $this->roomBerlin->id,
            'date' => '2026-10-25',
            'start_time' => '09:00',
            'end_time' => '09:00',
            'type' => 'course',
            'status' => 'scheduled',
        ]);
        $responseSame->assertSessionHasErrors('end_time');

        // End time before start time
        $responseBefore = $this->actingAs($this->adminBerlin)->post(route('schedules.store'), [
            'class_id' => $this->classBerlin->id,
            'teacher_id' => $this->teacherBerlin->id,
            'room_id' => $this->roomBerlin->id,
            'date' => '2026-10-25',
            'start_time' => '11:00',
            'end_time' => '09:00',
            'type' => 'course',
            'status' => 'scheduled',
        ]);
        $responseBefore->assertSessionHasErrors('end_time');
    }

    // --- 5. Conflicts Detection ---

    public function test_teacher_overlap_conflict_is_rejected(): void
    {
        // Existing schedule 09:00 -> 11:00
        Schedule::create([
            'center_id' => $this->berlin->id,
            'class_id' => $this->classBerlin->id,
            'teacher_id' => $this->teacherBerlin->id,
            'room_id' => $this->roomBerlin->id,
            'date' => '2026-10-25',
            'start_time' => '09:00',
            'end_time' => '11:00',
            'status' => 'scheduled',
        ]);

        // Autre classe et autre salle pour isoler le conflit prof
        $class2 = ClassRoom::create([
            'center_id' => $this->berlin->id,
            'formation_id' => $this->formationBerlin->id,
            'name' => 'Classe Berlin 02',
            'code' => 'BER-02',
            'capacity' => 20,
            'status' => 'active',
        ]);
        $class2->teachers()->attach($this->teacherBerlin->id);

        $room2 = Room::create([
            'center_id' => $this->berlin->id,
            'name' => 'Salle 02',
            'code' => 'ROOM-02',
            'capacity' => 20,
            'status' => 'active',
        ]);

        // Attempt overlap 10:00 -> 12:00 avec le même prof
        $response = $this->actingAs($this->adminBerlin)->post(route('schedules.store'), [
            'class_id' => $class2->id,
            'teacher_id' => $this->teacherBerlin->id,
            'room_id' => $room2->id,
            'date' => '2026-10-25',
            'start_time' => '10:00',
            'end_time' => '12:00',
            'type' => 'course',
            'status' => 'scheduled',
        ]);

        $response->assertSessionHasErrors('teacher_id');
    }

    public function test_adjacent_slot_for_teacher_is_allowed(): void
    {
        // Existing schedule 09:00 -> 11:00
        Schedule::create([
            'center_id' => $this->berlin->id,
            'class_id' => $this->classBerlin->id,
            'teacher_id' => $this->teacherBerlin->id,
            'room_id' => $this->roomBerlin->id,
            'date' => '2026-10-25',
            'start_time' => '09:00',
            'end_time' => '11:00',
            'status' => 'scheduled',
        ]);

        $class2 = ClassRoom::create([
            'center_id' => $this->berlin->id,
            'formation_id' => $this->formationBerlin->id,
            'name' => 'Classe Berlin 02',
            'code' => 'BER-02',
            'capacity' => 20,
            'status' => 'active',
        ]);
        $class2->teachers()->attach($this->teacherBerlin->id);

        $room2 = Room::create([
            'center_id' => $this->berlin->id,
            'name' => 'Salle 02',
            'code' => 'ROOM-02',
            'capacity' => 20,
            'status' => 'active',
        ]);

        // Adjacent: 11:00 -> 13:00 -> MUST be allowed
        $response = $this->actingAs($this->adminBerlin)->post(route('schedules.store'), [
            'class_id' => $class2->id,
            'teacher_id' => $this->teacherBerlin->id,
            'room_id' => $room2->id,
            'date' => '2026-10-25',
            'start_time' => '11:00',
            'end_time' => '13:00',
            'type' => 'course',
            'status' => 'scheduled',
        ]);

        $response->assertRedirect();
        $this->assertDatabaseHas('schedules', [
            'class_id' => $class2->id,
            'teacher_id' => $this->teacherBerlin->id,
            'room_id' => $room2->id,
            'status' => 'scheduled',
        ]);
    }

    public function test_room_overlap_conflict_is_rejected(): void
    {
        // Existing schedule 09:00 -> 11:00 in ROOM-01
        Schedule::create([
            'center_id' => $this->berlin->id,
            'class_id' => $this->classBerlin->id,
            'teacher_id' => $this->teacherBerlin->id,
            'room_id' => $this->roomBerlin->id,
            'date' => '2026-10-25',
            'start_time' => '09:00',
            'end_time' => '11:00',
            'status' => 'scheduled',
        ]);

        // Autre prof et autre classe
        $teacherUser2 = User::create([
            'name' => 'Prof Deux',
            'email' => 'prof2@berlin.test',
            'password' => bcrypt('password123'),
            'role' => 'teacher',
            'center_id' => $this->berlin->id,
            'status' => 'active',
        ]);
        $teacher2 = Teacher::create([
            'user_id' => $teacherUser2->id,
            'center_id' => $this->berlin->id,
            'teacher_number' => 'BER-T-000002',
            'first_name' => 'Prof',
            'last_name' => 'Deux',
            'gender' => 'male',
            'status' => 'active',
        ]);
        $class2 = ClassRoom::create([
            'center_id' => $this->berlin->id,
            'formation_id' => $this->formationBerlin->id,
            'name' => 'Classe 2',
            'code' => 'BER-C2',
            'capacity' => 15,
            'status' => 'active',
        ]);
        $class2->teachers()->attach($teacher2->id);

        // Attempting to book ROOM-01 between 10:00 -> 12:00
        $response = $this->actingAs($this->adminBerlin)->post(route('schedules.store'), [
            'class_id' => $class2->id,
            'teacher_id' => $teacher2->id,
            'room_id' => $this->roomBerlin->id,
            'date' => '2026-10-25',
            'start_time' => '10:00',
            'end_time' => '12:00',
            'type' => 'course',
            'status' => 'scheduled',
        ]);

        $response->assertSessionHasErrors('room_id');
    }

    public function test_class_overlap_conflict_is_rejected(): void
    {
        // Existing schedule 09:00 -> 11:00 for Class A1-01
        Schedule::create([
            'center_id' => $this->berlin->id,
            'class_id' => $this->classBerlin->id,
            'teacher_id' => $this->teacherBerlin->id,
            'room_id' => $this->roomBerlin->id,
            'date' => '2026-10-25',
            'start_time' => '09:00',
            'end_time' => '11:00',
            'status' => 'scheduled',
        ]);

        $teacherUser2 = User::create([
            'name' => 'Prof Trois',
            'email' => 'prof3@berlin.test',
            'password' => bcrypt('password123'),
            'role' => 'teacher',
            'center_id' => $this->berlin->id,
            'status' => 'active',
        ]);
        $teacher3 = Teacher::create([
            'user_id' => $teacherUser2->id,
            'center_id' => $this->berlin->id,
            'teacher_number' => 'BER-T-000003',
            'first_name' => 'Prof',
            'last_name' => 'Trois',
            'gender' => 'female',
            'status' => 'active',
        ]);
        $this->classBerlin->teachers()->attach($teacher3->id);

        $room2 = Room::create([
            'center_id' => $this->berlin->id,
            'name' => 'Salle 02',
            'code' => 'ROOM-02',
            'capacity' => 20,
            'status' => 'active',
        ]);

        // Attempting to schedule another session for the same class from 10:30 -> 12:30
        $response = $this->actingAs($this->adminBerlin)->post(route('schedules.store'), [
            'class_id' => $this->classBerlin->id,
            'teacher_id' => $teacher3->id,
            'room_id' => $room2->id,
            'date' => '2026-10-25',
            'start_time' => '10:30',
            'end_time' => '12:30',
            'type' => 'course',
            'status' => 'scheduled',
        ]);

        $response->assertSessionHasErrors('class_id');
    }

    public function test_updating_schedule_does_not_conflict_with_itself(): void
    {
        $schedule = Schedule::create([
            'center_id' => $this->berlin->id,
            'class_id' => $this->classBerlin->id,
            'teacher_id' => $this->teacherBerlin->id,
            'room_id' => $this->roomBerlin->id,
            'date' => '2026-10-25',
            'start_time' => '09:00',
            'end_time' => '11:00',
            'title' => 'Titre Initial',
            'type' => 'course',
            'status' => 'scheduled',
        ]);

        $response = $this->actingAs($this->adminBerlin)->put(route('schedules.update', $schedule), [
            'class_id' => $this->classBerlin->id,
            'teacher_id' => $this->teacherBerlin->id,
            'room_id' => $this->roomBerlin->id,
            'date' => '2026-10-25',
            'start_time' => '09:00',
            'end_time' => '11:00',
            'title' => 'Titre Modifié',
            'type' => 'course',
            'status' => 'scheduled',
        ]);

        $response->assertRedirect(route('schedules.show', $schedule));
        $this->assertEquals('Titre Modifié', $schedule->fresh()->title);
    }

    // --- 6. Status Lifecycle and Retention ---

    public function test_schedule_can_be_cancelled_and_completed_and_is_retained_in_database(): void
    {
        $schedule = Schedule::create([
            'center_id' => $this->berlin->id,
            'class_id' => $this->classBerlin->id,
            'teacher_id' => $this->teacherBerlin->id,
            'room_id' => $this->roomBerlin->id,
            'date' => '2026-10-25',
            'start_time' => '09:00',
            'end_time' => '11:00',
            'status' => 'scheduled',
        ]);

        // Cancel
        $this->actingAs($this->adminBerlin)->patch(route('schedules.toggle-status', $schedule), [
            'status' => 'cancelled',
        ])->assertRedirect();

        $this->assertDatabaseHas('schedules', [
            'id' => $schedule->id,
            'status' => 'cancelled',
        ]);

        // Cancelled session does not block a new booking on same slot
        $response = $this->actingAs($this->adminBerlin)->post(route('schedules.store'), [
            'class_id' => $this->classBerlin->id,
            'teacher_id' => $this->teacherBerlin->id,
            'room_id' => $this->roomBerlin->id,
            'date' => '2026-10-25',
            'start_time' => '09:00',
            'end_time' => '11:00',
            'type' => 'course',
            'status' => 'scheduled',
        ]);

        $response->assertRedirect();
        $this->assertEquals(2, Schedule::count());
    }

    // --- 7. Role Security: Teacher and Student Forbidden ---

    public function test_teacher_cannot_manage_schedules(): void
    {
        $schedule = Schedule::create([
            'center_id' => $this->berlin->id,
            'class_id' => $this->classBerlin->id,
            'teacher_id' => $this->teacherBerlin->id,
            'room_id' => $this->roomBerlin->id,
            'date' => '2026-10-25',
            'start_time' => '09:00',
            'end_time' => '11:00',
            'status' => 'scheduled',
        ]);

        $this->actingAs($this->teacherBerlinUser)->get(route('schedules.index'))->assertForbidden();
        $this->actingAs($this->teacherBerlinUser)->get(route('schedules.create'))->assertForbidden();
        $this->actingAs($this->teacherBerlinUser)->get(route('schedules.show', $schedule))->assertForbidden();
        $this->actingAs($this->teacherBerlinUser)->get(route('schedules.edit', $schedule))->assertForbidden();
        $this->actingAs($this->teacherBerlinUser)->post(route('schedules.store'), [])->assertForbidden();
        $this->actingAs($this->teacherBerlinUser)->put(route('schedules.update', $schedule), [])->assertForbidden();
        $this->actingAs($this->teacherBerlinUser)->delete(route('schedules.destroy', $schedule))->assertForbidden();
        $this->actingAs($this->teacherBerlinUser)->patch(route('schedules.toggle-status', $schedule), [])->assertForbidden();
    }

    public function test_student_cannot_manage_schedules(): void
    {
        $schedule = Schedule::create([
            'center_id' => $this->berlin->id,
            'class_id' => $this->classBerlin->id,
            'teacher_id' => $this->teacherBerlin->id,
            'room_id' => $this->roomBerlin->id,
            'date' => '2026-10-25',
            'start_time' => '09:00',
            'end_time' => '11:00',
            'status' => 'scheduled',
        ]);

        $this->actingAs($this->studentBerlinUser)->get(route('schedules.index'))->assertForbidden();
        $this->actingAs($this->studentBerlinUser)->get(route('schedules.create'))->assertForbidden();
        $this->actingAs($this->studentBerlinUser)->get(route('schedules.show', $schedule))->assertForbidden();
        $this->actingAs($this->studentBerlinUser)->get(route('schedules.edit', $schedule))->assertForbidden();
        $this->actingAs($this->studentBerlinUser)->post(route('schedules.store'), [])->assertForbidden();
        $this->actingAs($this->studentBerlinUser)->put(route('schedules.update', $schedule), [])->assertForbidden();
        $this->actingAs($this->studentBerlinUser)->delete(route('schedules.destroy', $schedule))->assertForbidden();
        $this->actingAs($this->studentBerlinUser)->patch(route('schedules.toggle-status', $schedule), [])->assertForbidden();
    }

    // --- 8. Relations Eloquent ---

    public function test_eloquent_relationships(): void
    {
        $schedule = Schedule::create([
            'center_id' => $this->berlin->id,
            'class_id' => $this->classBerlin->id,
            'teacher_id' => $this->teacherBerlin->id,
            'room_id' => $this->roomBerlin->id,
            'date' => '2026-10-25',
            'start_time' => '09:00',
            'end_time' => '11:00',
            'status' => 'scheduled',
        ]);

        $this->assertTrue($this->berlin->schedules->contains($schedule));
        $this->assertTrue($this->classBerlin->schedules->contains($schedule));
        $this->assertTrue($this->teacherBerlin->schedules->contains($schedule));
        $this->assertTrue($this->roomBerlin->schedules->contains($schedule));

        $this->assertEquals($this->berlin->id, $schedule->center->id);
        $this->assertEquals($this->classBerlin->id, $schedule->classRoom->id);
        $this->assertEquals($this->teacherBerlin->id, $schedule->teacher->id);
        $this->assertEquals($this->roomBerlin->id, $schedule->room->id);
    }

    public function test_adjacent_slot_for_room_is_allowed(): void
    {
        // Existing 09:00 -> 11:00 in ROOM-01
        Schedule::create([
            'center_id' => $this->berlin->id,
            'class_id' => $this->classBerlin->id,
            'teacher_id' => $this->teacherBerlin->id,
            'room_id' => $this->roomBerlin->id,
            'date' => '2026-10-25',
            'start_time' => '09:00',
            'end_time' => '11:00',
            'status' => 'scheduled',
        ]);

        $teacherUser2 = User::create([
            'name' => 'Prof Deux',
            'email' => 'prof2@berlin.test',
            'password' => bcrypt('password123'),
            'role' => 'teacher',
            'center_id' => $this->berlin->id,
            'status' => 'active',
        ]);
        $teacher2 = Teacher::create([
            'user_id' => $teacherUser2->id,
            'center_id' => $this->berlin->id,
            'teacher_number' => 'BER-T-000002',
            'first_name' => 'Prof',
            'last_name' => 'Deux',
            'gender' => 'male',
            'status' => 'active',
        ]);
        $class2 = ClassRoom::create([
            'center_id' => $this->berlin->id,
            'formation_id' => $this->formationBerlin->id,
            'name' => 'Classe 2',
            'code' => 'BER-C2',
            'capacity' => 15,
            'status' => 'active',
        ]);
        $class2->teachers()->attach($teacher2->id);

        // Booking the same ROOM-01 right after: 11:00 -> 13:00 -> MUST be allowed
        $response = $this->actingAs($this->adminBerlin)->post(route('schedules.store'), [
            'class_id' => $class2->id,
            'teacher_id' => $teacher2->id,
            'room_id' => $this->roomBerlin->id,
            'date' => '2026-10-25',
            'start_time' => '11:00',
            'end_time' => '13:00',
            'type' => 'course',
            'status' => 'scheduled',
        ]);

        $response->assertRedirect();
        $this->assertEquals(2, Schedule::where('room_id', $this->roomBerlin->id)->count());
    }

    public function test_adjacent_slot_for_class_is_allowed(): void
    {
        // Existing 09:00 -> 11:00 for Class A1-01
        Schedule::create([
            'center_id' => $this->berlin->id,
            'class_id' => $this->classBerlin->id,
            'teacher_id' => $this->teacherBerlin->id,
            'room_id' => $this->roomBerlin->id,
            'date' => '2026-10-25',
            'start_time' => '09:00',
            'end_time' => '11:00',
            'status' => 'scheduled',
        ]);

        $teacherUser2 = User::create([
            'name' => 'Prof Trois',
            'email' => 'prof3@berlin.test',
            'password' => bcrypt('password123'),
            'role' => 'teacher',
            'center_id' => $this->berlin->id,
            'status' => 'active',
        ]);
        $teacher3 = Teacher::create([
            'user_id' => $teacherUser2->id,
            'center_id' => $this->berlin->id,
            'teacher_number' => 'BER-T-000003',
            'first_name' => 'Prof',
            'last_name' => 'Trois',
            'gender' => 'female',
            'status' => 'active',
        ]);
        $this->classBerlin->teachers()->attach($teacher3->id);

        $room2 = Room::create([
            'center_id' => $this->berlin->id,
            'name' => 'Salle 02',
            'code' => 'ROOM-02',
            'capacity' => 20,
            'status' => 'active',
        ]);

        // Same class A1-01 right after from 11:00 -> 13:00 -> MUST be allowed
        $response = $this->actingAs($this->adminBerlin)->post(route('schedules.store'), [
            'class_id' => $this->classBerlin->id,
            'teacher_id' => $teacher3->id,
            'room_id' => $room2->id,
            'date' => '2026-10-25',
            'start_time' => '11:00',
            'end_time' => '13:00',
            'type' => 'course',
            'status' => 'scheduled',
        ]);

        $response->assertRedirect();
        $this->assertEquals(2, Schedule::where('class_id', $this->classBerlin->id)->count());
    }

    public function test_updating_schedule_towards_a_conflicting_slot_is_rejected(): void
    {
        // Schedule 1: 09:00 -> 11:00
        Schedule::create([
            'center_id' => $this->berlin->id,
            'class_id' => $this->classBerlin->id,
            'teacher_id' => $this->teacherBerlin->id,
            'room_id' => $this->roomBerlin->id,
            'date' => '2026-10-25',
            'start_time' => '09:00',
            'end_time' => '11:00',
            'status' => 'scheduled',
        ]);

        $class2 = ClassRoom::create([
            'center_id' => $this->berlin->id,
            'formation_id' => $this->formationBerlin->id,
            'name' => 'Classe 2',
            'code' => 'BER-C2',
            'capacity' => 15,
            'status' => 'active',
        ]);
        $class2->teachers()->attach($this->teacherBerlin->id);

        $room2 = Room::create([
            'center_id' => $this->berlin->id,
            'name' => 'Salle 02',
            'code' => 'ROOM-02',
            'capacity' => 20,
            'status' => 'active',
        ]);

        // Schedule 2: 14:00 -> 16:00
        $schedule2 = Schedule::create([
            'center_id' => $this->berlin->id,
            'class_id' => $class2->id,
            'teacher_id' => $this->teacherBerlin->id,
            'room_id' => $room2->id,
            'date' => '2026-10-25',
            'start_time' => '14:00',
            'end_time' => '16:00',
            'status' => 'scheduled',
        ]);

        // Trying to update Schedule 2 into 10:00 -> 12:00 (overlapping Schedule 1 on teacher)
        $response = $this->actingAs($this->adminBerlin)->put(route('schedules.update', $schedule2), [
            'class_id' => $class2->id,
            'teacher_id' => $this->teacherBerlin->id,
            'room_id' => $room2->id,
            'date' => '2026-10-25',
            'start_time' => '10:00',
            'end_time' => '12:00',
            'type' => 'course',
            'status' => 'scheduled',
        ]);

        $response->assertSessionHasErrors('teacher_id');
    }
}
