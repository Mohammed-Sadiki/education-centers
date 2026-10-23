<?php

namespace Tests\Feature;

use App\Models\Center;
use App\Models\Enrollment;
use App\Models\Formation;
use App\Models\Payment;
use App\Models\Student;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Phase 6 — Unification du module financier avec le contexte de domaine actif.
 *
 * Le Super Admin qui a sélectionné un domaine (session active_domain) voit la
 * finance filtrée sur ce domaine sans action supplémentaire, mais un filtre
 * explicite ?domain (y compris « all ») garde la priorité. Les KPI/lignes
 * financiers proviennent bien des données réelles du domaine.
 */
class FinanceDomainContextTest extends TestCase
{
    use RefreshDatabase;

    private Center $center;

    private User $superAdmin;

    protected function setUp(): void
    {
        parent::setUp();

        $this->center = Center::create(['name' => 'Alpha', 'code' => 'AL', 'address' => 'A', 'status' => 'active']);
        $this->superAdmin = User::factory()->superAdmin()->create();

        $sante = Formation::create([
            'center_id' => $this->center->id, 'domain' => 'sante',
            'name' => 'Infirmier', 'code' => 'INF', 'status' => 'active', 'annual_price' => 15000,
        ]);
        $info = Formation::create([
            'center_id' => $this->center->id, 'domain' => 'informatique',
            'name' => 'Developpeur', 'code' => 'DEV', 'status' => 'active', 'annual_price' => 14000,
        ]);

        $stuSante = $this->makeStudent('S-SANTE');
        $stuInfo = $this->makeStudent('S-INFO');

        $enrSante = Enrollment::create([
            'center_id' => $this->center->id, 'student_id' => $stuSante->id, 'formation_id' => $sante->id,
            'registration_number' => 'E-1', 'application_date' => '2026-01-01',
            'school_year' => '2026/2027', 'status' => 'accepted',
        ]);
        $enrInfo = Enrollment::create([
            'center_id' => $this->center->id, 'student_id' => $stuInfo->id, 'formation_id' => $info->id,
            'registration_number' => 'E-2', 'application_date' => '2026-01-01',
            'school_year' => '2026/2027', 'status' => 'accepted',
        ]);

        // Un paiement réel pour chaque domaine.
        Payment::create([
            'center_id' => $this->center->id, 'student_id' => $stuSante->id, 'formation_id' => $sante->id,
            'enrollment_id' => $enrSante->id, 'school_year' => '2026/2027', 'domain' => 'sante',
            'amount' => 5000, 'currency' => 'MAD', 'payment_date' => '2026-10-01',
            'method' => 'cash', 'reference' => 'PAY-SANTE', 'status' => 'paid', 'recorded_by' => $this->superAdmin->id,
        ]);
        Payment::create([
            'center_id' => $this->center->id, 'student_id' => $stuInfo->id, 'formation_id' => $info->id,
            'enrollment_id' => $enrInfo->id, 'school_year' => '2026/2027', 'domain' => 'informatique',
            'amount' => 3000, 'currency' => 'MAD', 'payment_date' => '2026-10-01',
            'method' => 'cash', 'reference' => 'PAY-INFO', 'status' => 'paid', 'recorded_by' => $this->superAdmin->id,
        ]);
    }

    private function makeStudent(string $number): Student
    {
        $user = User::factory()->create(['role' => 'student', 'center_id' => $this->center->id]);

        return Student::create([
            'user_id' => $user->id, 'center_id' => $this->center->id, 'student_number' => $number,
            'first_name' => 'Eleve', 'last_name' => 'X', 'date_of_birth' => '2000-01-01',
            'gender' => 'male', 'status' => 'active',
        ]);
    }

    public function test_finance_defaults_to_active_domain_context(): void
    {
        $response = $this->actingAs($this->superAdmin)
            ->withSession(['active_domain' => 'sante'])
            ->get(route('super-admin.finance.dashboard'));

        $response->assertOk();

        // Le domaine actif est repris automatiquement par le module financier.
        $this->assertSame('sante', $response->viewData('domain'));

        // Les lignes étudiantes sont restreintes au domaine santé.
        $numbers = collect($response->viewData('studentRows'))->pluck('student.student_number')->all();
        $this->assertContains('S-SANTE', $numbers);
        $this->assertNotContains('S-INFO', $numbers);

        // KPI encaissé = paiement santé uniquement (5000), pas celui d'informatique.
        $kpis = $response->viewData('kpis');
        $this->assertSame(5000.0, (float) $kpis['total_encaisse']);
    }

    public function test_explicit_domain_filter_overrides_context(): void
    {
        $response = $this->actingAs($this->superAdmin)
            ->withSession(['active_domain' => 'sante'])
            ->get(route('super-admin.finance.dashboard', ['domain' => 'informatique']));

        $this->assertSame('informatique', $response->viewData('domain'));

        $numbers = collect($response->viewData('studentRows'))->pluck('student.student_number')->all();
        $this->assertContains('S-INFO', $numbers);
        $this->assertNotContains('S-SANTE', $numbers);
    }

    public function test_explicit_all_overrides_active_domain(): void
    {
        $response = $this->actingAs($this->superAdmin)
            ->withSession(['active_domain' => 'sante'])
            ->get(route('super-admin.finance.dashboard', ['domain' => 'all']));

        $this->assertSame('all', $response->viewData('domain'));

        $numbers = collect($response->viewData('studentRows'))->pluck('student.student_number')->all();
        $this->assertContains('S-SANTE', $numbers);
        $this->assertContains('S-INFO', $numbers);
    }

    public function test_no_active_domain_keeps_finance_global(): void
    {
        // Aucun domaine actif => comportement historique « tous les domaines ».
        $response = $this->actingAs($this->superAdmin)
            ->get(route('super-admin.finance.dashboard'));

        $this->assertSame('all', $response->viewData('domain'));
        $this->assertSame(8000.0, (float) $response->viewData('kpis')['total_encaisse']);
    }
}
