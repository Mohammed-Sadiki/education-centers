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

/**
 * Vérifie la refonte de la page « Emploi du temps / Planning des séances » :
 * calendrier interactif, séances issues de la base, détection visuelle des
 * conflits, filtres et isolation par centre.
 */
class SchedulePlanningTest extends TestCase
{
    use RefreshDatabase;

    protected User $superAdmin;
    protected User $adminBerlin;
    protected Center $berlin;
    protected Center $munich;
    protected ClassRoom $classBerlin;
    protected ClassRoom $classMunich;
    protected Teacher $teacherBerlin;
    protected Teacher $teacherMunich;
    protected Room $roomBerlin;
    protected Room $roomMunich;

    protected function setUp(): void
    {
        parent::setUp();

        $this->berlin = Center::create(['name' => 'Centre Berlin', 'code' => 'BER', 'address' => 'A', 'status' => 'active']);
        $this->munich = Center::create(['name' => 'Centre Munich', 'code' => 'MUN', 'address' => 'B', 'status' => 'active']);

        $formationBerlin = Formation::create(['center_id' => $this->berlin->id, 'name' => 'Deutsch A1', 'code' => 'BER-A1', 'duration_hours' => 100, 'price' => 400, 'currency' => 'EUR', 'status' => 'active']);
        $formationMunich = Formation::create(['center_id' => $this->munich->id, 'name' => 'Deutsch A1 M', 'code' => 'MUN-A1', 'duration_hours' => 100, 'price' => 400, 'currency' => 'EUR', 'status' => 'active']);

        $this->classBerlin = ClassRoom::create(['center_id' => $this->berlin->id, 'formation_id' => $formationBerlin->id, 'name' => 'Berlin A1-01', 'code' => 'A1-01', 'capacity' => 20, 'status' => 'active']);
        $this->classMunich = ClassRoom::create(['center_id' => $this->munich->id, 'formation_id' => $formationMunich->id, 'name' => 'Munich A1-01', 'code' => 'A1-01', 'capacity' => 20, 'status' => 'active']);

        $this->superAdmin = User::create(['name' => 'Super Admin', 'email' => 'super@plateforme.test', 'password' => bcrypt('password123'), 'role' => 'super_admin', 'center_id' => null, 'status' => 'active']);
        $this->adminBerlin = User::create(['name' => 'Admin Berlin', 'email' => 'admin.berlin@plateforme.test', 'password' => bcrypt('password123'), 'role' => 'center_admin', 'center_id' => $this->berlin->id, 'status' => 'active']);

        $tBer = User::create(['name' => 'Johannes', 'email' => 'johannes@plateforme.test', 'password' => bcrypt('password123'), 'role' => 'teacher', 'center_id' => $this->berlin->id, 'status' => 'active']);
        $this->teacherBerlin = Teacher::create(['user_id' => $tBer->id, 'center_id' => $this->berlin->id, 'teacher_number' => 'BER-T-1', 'first_name' => 'Johannes', 'last_name' => 'Kohl', 'gender' => 'male', 'status' => 'active']);

        $tMun = User::create(['name' => 'Greta', 'email' => 'greta@plateforme.test', 'password' => bcrypt('password123'), 'role' => 'teacher', 'center_id' => $this->munich->id, 'status' => 'active']);
        $this->teacherMunich = Teacher::create(['user_id' => $tMun->id, 'center_id' => $this->munich->id, 'teacher_number' => 'MUN-T-1', 'first_name' => 'Greta', 'last_name' => 'Schneider', 'gender' => 'female', 'status' => 'active']);

        $this->roomBerlin = Room::create(['center_id' => $this->berlin->id, 'name' => 'Salle Berlin 01', 'code' => 'ROOM-01', 'capacity' => 20, 'status' => 'active']);
        $this->roomMunich = Room::create(['center_id' => $this->munich->id, 'name' => 'Salle Munich 01', 'code' => 'ROOM-01', 'capacity' => 20, 'status' => 'active']);

        $this->classBerlin->teachers()->attach($this->teacherBerlin->id);
        $this->classMunich->teachers()->attach($this->teacherMunich->id);
    }

    /** Créer une séance directement en base (court-circuite la validation). */
    private function makeSchedule(array $overrides = []): Schedule
    {
        return Schedule::create(array_merge([
            'center_id' => $this->berlin->id,
            'class_id' => $this->classBerlin->id,
            'teacher_id' => $this->teacherBerlin->id,
            'room_id' => $this->roomBerlin->id,
            'date' => '2026-10-25',
            'start_time' => '09:00',
            'end_time' => '11:00',
            'title' => 'Séance standard',
            'type' => 'course',
            'status' => 'scheduled',
        ], $overrides));
    }

    public function test_planning_page_renders_interactive_calendar(): void
    {
        $response = $this->actingAs($this->superAdmin)->get(route('schedules.index'));

        $response->assertOk();
        $response->assertSee('Planning des séances');
        $response->assertSee('Programmer une séance');
        // Conteneurs du calendrier + source de données JSON + légende.
        $response->assertSee('planning-calendar', false);
        $response->assertSee('planning-events', false);
        $response->assertSee('Types de séances');
    }

    public function test_planning_displays_real_sessions_from_database(): void
    {
        $this->makeSchedule(['title' => 'Planning Session Alpha']);

        $this->actingAs($this->superAdmin)
            ->get(route('schedules.index'))
            ->assertSee('Planning Session Alpha');
    }

    public function test_planning_marks_overlapping_sessions_as_conflict(): void
    {
        // Deux séances qui se chevauchent (même prof / salle / classe).
        $this->makeSchedule(['title' => 'Conflit A', 'start_time' => '09:00', 'end_time' => '11:00']);
        $this->makeSchedule(['title' => 'Conflit B', 'start_time' => '10:00', 'end_time' => '12:00']);

        $this->actingAs($this->superAdmin)
            ->get(route('schedules.index'))
            ->assertSee('"conflict":true', false);
    }

    public function test_planning_without_overlap_has_no_conflict_marker(): void
    {
        $this->makeSchedule(['title' => 'Creneau 1', 'start_time' => '09:00', 'end_time' => '11:00']);
        $this->makeSchedule(['title' => 'Creneau 2', 'start_time' => '11:00', 'end_time' => '13:00']);

        $this->actingAs($this->superAdmin)
            ->get(route('schedules.index'))
            ->assertDontSee('"conflict":true', false);
    }

    public function test_center_admin_planning_only_shows_own_center_sessions(): void
    {
        $this->makeSchedule(['title' => 'Berlin Planning Vue']);
        $this->makeSchedule([
            'center_id' => $this->munich->id,
            'class_id' => $this->classMunich->id,
            'teacher_id' => $this->teacherMunich->id,
            'room_id' => $this->roomMunich->id,
            'title' => 'Munich Planning Cachée',
        ]);

        $response = $this->actingAs($this->adminBerlin)->get(route('schedules.index'));
        $response->assertOk();
        $response->assertSee('Berlin Planning Vue');
        $response->assertDontSee('Munich Planning Cachée');
    }

    public function test_planning_respects_type_filter(): void
    {
        $this->makeSchedule(['title' => 'Cours de grammaire', 'type' => 'course']);
        $this->makeSchedule(['title' => 'Examen blanc', 'type' => 'exam']);

        $response = $this->actingAs($this->superAdmin)->get(route('schedules.index', ['type' => 'exam']));
        $response->assertOk();
        $response->assertSee('Examen blanc');
        $response->assertDontSee('Cours de grammaire');
    }

    public function test_planning_is_forbidden_for_teacher_and_student(): void
    {
        $teacherUser = $this->teacherBerlin->user;
        $student = User::create(['name' => 'Hans', 'email' => 'hans@plateforme.test', 'password' => bcrypt('password123'), 'role' => 'student', 'center_id' => $this->berlin->id, 'status' => 'active']);

        $this->actingAs($teacherUser)->get(route('schedules.index'))->assertForbidden();
        $this->actingAs($student)->get(route('schedules.index'))->assertForbidden();
    }
}
