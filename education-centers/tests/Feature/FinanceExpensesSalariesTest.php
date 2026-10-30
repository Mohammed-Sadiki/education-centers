<?php

namespace Tests\Feature;

use App\Models\Center;
use App\Models\Expense;
use App\Models\ExpenseCategory;
use App\Models\Teacher;
use App\Models\TeacherSalary;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

/**
 * Intégration des salaires des professeurs dans le module « Dépenses » et
 * nettoyage de la sidebar financière (mission : réorganisation de la gestion
 * financière).
 */
class FinanceExpensesSalariesTest extends TestCase
{
    use RefreshDatabase;

    protected Center $berlin;
    protected Center $munich;
    protected User $superAdmin;
    protected User $adminBerlin;
    protected User $adminMunich;
    protected Teacher $teacherBerlin;
    protected Teacher $teacherMunich;
    protected ExpenseCategory $fournitures;
    protected ExpenseCategory $salaryCat;

    protected function setUp(): void
    {
        parent::setUp();

        $this->berlin = Center::factory()->create(['name' => 'Centre Berlin', 'code' => 'BER']);
        $this->munich = Center::factory()->create(['name' => 'Centre Munich', 'code' => 'MUN']);

        $this->superAdmin = User::factory()->superAdmin()->create(['email' => 'sa.finance@test.com', 'status' => 'active']);

        $this->adminBerlin = User::factory()->centerAdmin($this->berlin->id)->create(['email' => 'ab.finance@test.com', 'status' => 'active']);
        $this->adminMunich = User::factory()->centerAdmin($this->munich->id)->create(['email' => 'am.finance@test.com', 'status' => 'active']);

        $t1 = User::factory()->teacher($this->berlin->id)->create(['email' => 't1@test.com', 'status' => 'active']);
        $this->teacherBerlin = Teacher::create([
            'user_id' => $t1->id, 'center_id' => $this->berlin->id,
            'teacher_number' => 'BER-T-900001', 'first_name' => 'Karim', 'last_name' => 'Berli',
            'domain' => 'informatique', 'monthly_salary' => 6000.00, 'status' => 'active',
        ]);

        $t2 = User::factory()->teacher($this->munich->id)->create(['email' => 't2@test.com', 'status' => 'active']);
        $this->teacherMunich = Teacher::create([
            'user_id' => $t2->id, 'center_id' => $this->munich->id,
            'teacher_number' => 'MUN-T-900002', 'first_name' => 'Lea', 'last_name' => 'Muni',
            'domain' => 'sante', 'monthly_salary' => 5000.00, 'status' => 'active',
        ]);

        $this->fournitures = ExpenseCategory::create(['name' => 'Fournitures scolaires', 'code' => 'fournitures', 'color' => '#0DAD98', 'description' => 'Papeterie']);
        $this->salaryCat = ExpenseCategory::create(['name' => 'Salaires des professeurs', 'code' => 'salaires', 'color' => '#8B5CF6', 'description' => 'Rémunérations']);
    }

    /** Payload d'un salaire via le workflow Dépenses. */
    private function salaryPayload(array $overrides = []): array
    {
        return array_merge([
            'category_id' => $this->salaryCat->id,   // déclenche la branche salaire
            'center_id' => $this->berlin->id,        // volontairement présent : doit être ignoré
            'teacher_id' => $this->teacherBerlin->id,
            'month' => 10,
            'year' => 2026,
            'school_year' => '2026/2027',
            'expected_amount' => 6000.00,
            'paid_amount' => 6000.00,
            'currency' => 'MAD',
            'payment_date' => '2026-10-05',
            'payment_method' => 'bank_transfer',
            'reference' => 'VIR-2026-001',
            'notes' => 'Salaire octobre',
        ], $overrides);
    }

    /** Payload d'une dépense classique. */
    private function expensePayload(array $overrides = []): array
    {
        return array_merge([
            'category_id' => $this->fournitures->id,
            'center_id' => $this->berlin->id,
            'title' => 'Achat de rames A4',
            'supplier' => 'Bureau Plus',
            'expense_date' => '2026-10-12',
            'payment_method' => 'cash',
            'school_year' => '2026/2027',
            'amount' => 300.00,
        ], $overrides);
    }

    // ─── Sidebar (scénarios 1-4) ────────────────────────────────────────────

    public function test_removed_entries_are_absent_from_finance_sidebar(): void
    {
        $this->actingAs($this->superAdmin)
            ->get(route('super-admin.finance.configuration'))
            ->assertOk()
            // Entrées supprimées de la navigation (vérifiées via leurs URL).
            ->assertDontSee('/super-admin/finance/rapports')
            ->assertDontSee('/super-admin/finance/compte-bancaire')
            ->assertDontSee('/super-admin/finance/comptabilite')
            ->assertDontSee('/super-admin/finance/professeurs')   // rubrique salaires autonome
            ->assertDontSee('Rapports financiers')
            ->assertDontSee('Compte bancaire')
            ->assertDontSee('Trésorerie');
    }

    public function test_expected_navigation_entries_remain_in_sidebar(): void
    {
        $this->actingAs($this->superAdmin)
            ->get(route('super-admin.finance.configuration'))
            ->assertOk()
            ->assertSee('/super-admin/finance/depenses')
            ->assertSee('/super-admin/finance/etudiants')
            ->assertSee('/super-admin/finance/recouvrement')
            ->assertSee('Navigation')
            ->assertSee('Configuration des prix');
    }

    // ─── Dépenses accessibles + catégorie salaires (scénarios 5-7) ───────────

    public function test_expenses_page_is_accessible(): void
    {
        $this->actingAs($this->superAdmin)
            ->get(route('super-admin.finance.expenses'))
            ->assertOk()
            ->assertSee('Dépenses')
            ->assertSee('Charges engagées');
    }

    public function test_salary_category_is_offered_in_expenses(): void
    {
        $this->actingAs($this->superAdmin)
            ->get(route('super-admin.finance.expenses'))
            ->assertOk()
            // La catégorie « Salaires des professeurs » est présente (filtre + modale).
            ->assertSee('Salaires des professeurs');
    }

    public function test_salary_specific_fields_markup_is_present_in_modal(): void
    {
        $html = $this->actingAs($this->superAdmin)
            ->get(route('super-admin.finance.expenses'))
            ->assertOk()
            ->getContent();

        // Champs spécifiques salary présents dans le <template x-if="isSalary">.
        foreach (['name="teacher_id"', 'name="expected_amount"', 'name="paid_amount"', 'name="month"', 'name="year"', 'name="payment_date"'] as $needle) {
            $this->assertStringContainsString($needle, $html, "Le champ $needle doit exister dans la modale.");
        }
    }

    // ─── Dépenses classiques et salaires via Dépenses (scénarios 8-9) ─────────

    public function test_classic_expense_still_works(): void
    {
        $this->actingAs($this->superAdmin)
            ->post(route('super-admin.finance.expenses.store'), $this->expensePayload())
            ->assertRedirect();

        $this->assertDatabaseHas('expenses', ['title' => 'Achat de rames A4', 'amount' => 300.00]);
    }

    public function test_salary_can_be_recorded_through_expenses_workflow(): void
    {
        $this->actingAs($this->superAdmin)
            ->post(route('super-admin.finance.expenses.store'), $this->salaryPayload())
            ->assertRedirect();

        $this->assertDatabaseHas('teacher_salaries', [
            'teacher_id' => $this->teacherBerlin->id,
            'month' => 10,
            'year' => 2026,
            'paid_amount' => 6000.00,
            'status' => 'paid',
            'center_id' => $this->berlin->id,
        ]);

        // AUCUNE dépense créée pour un salaire (pas de doublon comptable).
        $this->assertSame(0, Expense::count(), 'Un salaire ne doit jamais créer une ligne Expense.');
    }

    // ─── Historique centralisé + non-duplication (scénarios 10-11) ────────────

    public function test_existing_salaries_are_visible_in_centralized_list(): void
    {
        TeacherSalary::create([
            'center_id' => $this->berlin->id, 'teacher_id' => $this->teacherBerlin->id,
            'month' => 9, 'year' => 2026, 'school_year' => '2026/2027',
            'expected_amount' => 6000.00, 'paid_amount' => 6000.00, 'currency' => 'MAD',
            'status' => 'paid', 'payment_date' => '2026-09-05', 'payment_method' => 'bank_transfer',
            'reference' => 'VIR-SEPT', 'notes' => 'Salaire sept.',
        ]);

        $this->actingAs($this->superAdmin)
            ->get(route('super-admin.finance.expenses', ['school_year' => '2026/2027']))
            ->assertOk()
            ->assertSee('Karim Berli')
            ->assertSee('Salaires des professeurs');
    }

    public function test_no_salary_is_duplicated(): void
    {
        // Premier enregistrement (partiel).
        $this->actingAs($this->superAdmin)
            ->post(route('super-admin.finance.expenses.store'), $this->salaryPayload(['paid_amount' => 3000.00]));

        // Même période, même professeur ⇒ mise à jour, pas de doublon.
        $this->actingAs($this->superAdmin)
            ->post(route('super-admin.finance.expenses.store'), $this->salaryPayload(['paid_amount' => 6000.00]));

        $this->assertSame(1, TeacherSalary::count(), 'updateOrCreate ne doit jamais dupliquer une fiche de paie.');
        $salary = TeacherSalary::first();
        $this->assertEquals(6000.00, (float) $salary->paid_amount);
        $this->assertEquals('paid', $salary->status);
    }

    // ─── Cohérence comptable (scénarios 12-14) ────────────────────────────────

    public function test_paid_salary_contributes_to_paid_expenses_total(): void
    {
        TeacherSalary::create([
            'center_id' => $this->berlin->id, 'teacher_id' => $this->teacherBerlin->id,
            'month' => 10, 'year' => 2026, 'school_year' => '2026/2027',
            'expected_amount' => 6000.00, 'paid_amount' => 6000.00, 'currency' => 'MAD',
            'status' => 'paid', 'payment_date' => '2026-10-05', 'payment_method' => 'bank_transfer',
        ]);

        // Dépense payée de 300 ⇒ sortie réalisée totale = 6300.
        $this->actingAs($this->superAdmin)->post(route('super-admin.finance.expenses.store'), $this->expensePayload());

        $this->actingAs($this->superAdmin)
            ->get(route('super-admin.finance.expenses', ['school_year' => '2026/2027']))
            ->assertOk()
            ->assertSee('6 300,00 DH');   // versé : 300 + 6000
    }

    public function test_pending_salary_is_not_counted_as_realized_cashout(): void
    {
        TeacherSalary::create([
            'center_id' => $this->berlin->id, 'teacher_id' => $this->teacherBerlin->id,
            'month' => 10, 'year' => 2026, 'school_year' => '2026/2027',
            'expected_amount' => 6000.00, 'paid_amount' => 0.00, 'currency' => 'MAD',
            'status' => 'unpaid', 'payment_method' => 'bank_transfer',
        ]);

        $response = $this->actingAs($this->superAdmin)
            ->get(route('super-admin.finance.expenses', ['school_year' => '2026/2027']))
            ->assertOk()
            ->getContent();

        // Charges engagées = 6000 (commité) mais versé = 0, reste = 6000.
        $this->assertStringContainsString('6 000,00 DH', $response);
        $this->assertStringContainsString('vers', $response); // « versé : 0,00 DH »
        $this->assertStringContainsString('0,00 DH', $response);
        // Le montant 6000 n'apparaît pas comme déjà versé.
        $this->assertStringNotContainsString('versé : 6 000,00 DH', $response);
    }

    public function test_financial_totals_have_no_double_counting(): void
    {
        // Un seul salaire payé de 6000, aucune dépense.
        $this->actingAs($this->superAdmin)
            ->post(route('super-admin.finance.expenses.store'), $this->salaryPayload(['paid_amount' => 6000.00]));

        $html = $this->actingAs($this->superAdmin)
            ->get(route('super-admin.finance.expenses', ['school_year' => '2026/2027']))
            ->assertOk()
            ->getContent();

        // Total engagé = 6000, jamais 12000 (le salaire ne doit pas être compté deux fois).
        $this->assertStringNotContainsString('12 000,00 DH', $html);
        $this->assertStringContainsString('6 000,00 DH', $html);
    }

    // ─── Filtres de catégorie (scénario 15) ──────────────────────────────────

    public function test_category_filter_separates_salaries_from_expenses(): void
    {
        $this->actingAs($this->superAdmin)->post(route('super-admin.finance.expenses.store'), $this->expensePayload());
        $this->actingAs($this->superAdmin)->post(route('super-admin.finance.expenses.store'), $this->salaryPayload());

        // Filtre « salaires » ⇒ le salaire s'affiche, la dépense classique non.
        $this->actingAs($this->superAdmin)
            ->get(route('super-admin.finance.expenses', ['category_id' => $this->salaryCat->id, 'school_year' => '2026/2027']))
            ->assertOk()
            ->assertSee('Karim Berli')
            ->assertDontSee('Achat de rames A4');

        // Filtre « fournitures » ⇒ la dépense s'affiche, pas le salaire.
        $this->actingAs($this->superAdmin)
            ->get(route('super-admin.finance.expenses', ['category_id' => $this->fournitures->id, 'school_year' => '2026/2027']))
            ->assertOk()
            ->assertSee('Achat de rames A4')
            ->assertDontSee('Salaire — Karim Berli');
    }

    // ─── Permissions et périmètre (scénarios 16-18) ──────────────────────────

    public function test_super_admin_keeps_full_scope(): void
    {
        // Salaires des deux centres visibles pour le Super Admin (aucun filtre centre).
        $this->actingAs($this->superAdmin)->post(route('super-admin.finance.expenses.store'), $this->salaryPayload());
        $this->actingAs($this->superAdmin)->post(route('super-admin.finance.expenses.store'),
            $this->salaryPayload(['teacher_id' => $this->teacherMunich->id, 'expected_amount' => 5000.00, 'paid_amount' => 5000.00]));

        $this->actingAs($this->superAdmin)
            ->get(route('super-admin.finance.expenses', ['category_id' => $this->salaryCat->id, 'school_year' => '2026/2027']))
            ->assertOk()
            ->assertSee('Karim Berli')
            ->assertSee('Lea Muni');
    }

    public function test_center_admin_manages_salaries_scoped_to_their_center(): void
    {
        // Le module financier est désormais ouvert à l'Admin Centre, mais STRICTEMENT
        // limité à SON centre (périmètre imposé côté serveur).

        // Consultation autorisée.
        $this->actingAs($this->adminBerlin)
            ->get(route('super-admin.finance.expenses'))
            ->assertOk();

        // Salaire d'un professeur de SON centre : accepté.
        $this->actingAs($this->adminBerlin)
            ->post(route('super-admin.finance.expenses.store'), $this->salaryPayload())
            ->assertSessionHas('success');

        $this->assertDatabaseHas('teacher_salaries', [
            'teacher_id' => $this->teacherBerlin->id,
            'center_id' => $this->berlin->id,
            'month' => 10,
            'year' => 2026,
            'status' => 'paid',
        ]);

        // Salaire d'un professeur d'un AUTRE centre : refusé (403), la garde
        // d'appartenance empêche toute évasion de périmètre.
        $this->actingAs($this->adminBerlin)
            ->post(route('super-admin.finance.expenses.store'), $this->salaryPayload([
                'teacher_id' => $this->teacherMunich->id,
                'center_id' => $this->berlin->id,
                'expected_amount' => 5000.00,
                'paid_amount' => 5000.00,
            ]))
            ->assertForbidden();
    }

    public function test_center_of_salary_is_always_the_teachers_center_not_the_post(): void
    {
        // Preuve défensive : le centre est dérivé du PROFESSEUR, jamais du POST.
        // On poste volontairement center_id = Berlin pour un professeur de Munich.
        $this->actingAs($this->superAdmin)
            ->post(route('super-admin.finance.expenses.store'), $this->salaryPayload([
                'teacher_id' => $this->teacherMunich->id,
                'center_id' => $this->berlin->id,
                'expected_amount' => 5000.00,
                'paid_amount' => 5000.00,
            ]));

        $salary = TeacherSalary::where('teacher_id', $this->teacherMunich->id)->first();
        $this->assertNotNull($salary);
        $this->assertSame($this->munich->id, (int) $salary->center_id, 'Le centre doit suivre le professeur, pas la requête HTTP.');
    }

    // ─── Routes héritées et préservation (scénarios 19-20) ───────────────────

    public function test_old_salary_route_redirects_to_expenses(): void
    {
        $this->actingAs($this->superAdmin)
            ->get('/super-admin/finance/professeurs')
            ->assertRedirectContains('/super-admin/finance/depenses');
    }

    public function test_existing_history_and_reference_are_preserved(): void
    {
        TeacherSalary::create([
            'center_id' => $this->berlin->id, 'teacher_id' => $this->teacherBerlin->id,
            'month' => 8, 'year' => 2026, 'school_year' => '2026/2027',
            'expected_amount' => 6000.00, 'paid_amount' => 6000.00, 'currency' => 'MAD',
            'status' => 'paid', 'payment_date' => '2026-08-05', 'payment_method' => 'bank_transfer',
            'reference' => 'VIR-AOUT-123', 'notes' => 'Justificatif conservé',
        ]);

        $this->actingAs($this->superAdmin)
            ->get(route('super-admin.finance.expenses', ['category_id' => $this->salaryCat->id, 'school_year' => '2026/2027']))
            ->assertOk()
            ->assertSee('VIR-AOUT-123');

        // L'enregistrement d'un nouveau salaire ne supprime pas l'historique existant.
        $this->actingAs($this->superAdmin)->post(route('super-admin.finance.expenses.store'), $this->salaryPayload(['month' => 7]));
        $this->assertDatabaseHas('teacher_salaries', ['reference' => 'VIR-AOUT-123', 'month' => 8]);
    }

    // ─── Autres modules finance intacts (scénario 21) ────────────────────────

    public function test_other_finance_sections_remain_functional(): void
    {
        $this->actingAs($this->superAdmin)->get(route('super-admin.finance.students'))->assertOk();
        $this->actingAs($this->superAdmin)->get(route('super-admin.finance.recovery'))->assertOk();
        $this->actingAs($this->superAdmin)->get(route('super-admin.finance.configuration'))->assertOk();
        $this->actingAs($this->superAdmin)->get(route('super-admin.finance.dashboard'))->assertOk();
        $this->actingAs($this->superAdmin)->get(route('super-admin.finance.statistics'))->assertOk();
    }

    // ─── i18n / RTL (scénario 22) ────────────────────────────────────────────

    public function test_expenses_page_renders_in_arabic_rtl(): void
    {
        $this->actingAs($this->superAdmin)
            ->post(route('super-admin.finance.expenses.store'), $this->expensePayload())
            ->assertRedirect();

        $this->actingAs($this->superAdmin)
            ->get('/super-admin/finance/depenses?lang=ar')
            ->assertOk()
            ->assertSee('الطبيعة')   // « Nature » en arabe (en-tête du tableau unifié)
            ->assertSee('rtl', false);
    }

    public function test_expenses_page_renders_in_french(): void
    {
        $this->actingAs($this->superAdmin)
            ->post(route('super-admin.finance.expenses.store'), $this->expensePayload())
            ->assertRedirect();

        $this->actingAs($this->superAdmin)
            ->get(route('super-admin.finance.expenses'))
            ->assertOk()
            ->assertSee('Nature');
    }

    // ─── Refus de validation sans doublon (règle §6 + atomicité) ─────────────

    public function test_invalid_salary_payload_is_rejected_without_side_effects(): void
    {
        $before = TeacherSalary::count();

        $this->actingAs($this->superAdmin)
            ->from(route('super-admin.finance.expenses'))
            ->post(route('super-admin.finance.expenses.store'), $this->salaryPayload(['teacher_id' => '']))
            ->assertSessionHasErrors('teacher_id');

        $this->assertSame($before, TeacherSalary::count());
        $this->assertSame(0, Expense::count());
    }

    public function test_super_admin_password_is_valid_for_auth(): void
    {
        // Garde-fou de setup : le compte Super Admin est bien utilisable.
        $this->assertTrue(Hash::check('password', $this->superAdmin->password));
    }
}
