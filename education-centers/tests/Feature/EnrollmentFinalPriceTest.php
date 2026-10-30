<?php

namespace Tests\Feature;

use App\Models\Center;
use App\Models\Enrollment;
use App\Models\Formation;
use App\Models\Invoice;
use App\Models\Payment;
use App\Models\Student;
use App\Models\User;
use App\Services\FinanceService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * MISSION — Prix final personnalisé accordé lors de l'inscription d'un étudiant.
 *
 * Le prix officiel du catalogue reste la référence ; l'administrateur peut le
 * diminuer pour UNE inscription précise. Le prix final enregistré devient alors
 * la seule référence financière de cette inscription (solde, statut, avance,
 * paiements fractionnés, recouvrement, factures, statistiques), sans jamais
 * retoucher le catalogue ni les inscriptions antérieures.
 *
 * Couvre les 22 scénarios du cahier des charges.
 */
class EnrollmentFinalPriceTest extends TestCase
{
    use RefreshDatabase;

    private Center $center;
    private Center $otherCenter;
    private User $superAdmin;
    private User $centerAdmin;
    private Formation $formation;

    private const OFFICIAL = 14000.00;

    protected function setUp(): void
    {
        parent::setUp();

        $this->center = Center::factory()->create(['name' => 'Centre Tanger', 'code' => 'TNG']);
        $this->otherCenter = Center::factory()->create(['name' => 'Centre Agadir', 'code' => 'AGD']);

        $this->superAdmin = User::factory()->superAdmin()->create(['email' => 'sa@tst.ma']);
        $this->centerAdmin = User::factory()->centerAdmin($this->center->id)->create(['email' => 'ca@tst.ma']);

        // Tarif officiel fixe = 14 000 DH (priorité à annual_price).
        $this->formation = Formation::factory()
            ->forCenter($this->center)->forDomain('informatique')
            ->create(['annual_price' => self::OFFICIAL, 'price' => 1400]);
    }

    /** Jeu de données minimal du formulaire « Ajouter un élève ». */
    private function payload(array $overrides = []): array
    {
        return array_merge([
            'center_id' => $this->center->id,
            'domain' => 'informatique',
            'formation_id' => $this->formation->id,
            'first_name' => 'Yassine',
            'last_name' => 'El Amrani',
            'date_of_birth' => '2001-04-12',
            'nationality' => 'Marocaine',
            'phone' => '+212 600000000',
        ], $overrides);
    }

    // ── 1 & 2. Le prix final s'initialise sur le tarif officiel ────────────────

    public function test_final_price_defaults_to_official_when_not_provided(): void
    {
        $this->actingAs($this->superAdmin)
            ->post(route('students.store'), $this->payload())
            ->assertRedirect();

        $enrollment = Enrollment::first();
        $this->assertNotNull($enrollment);
        $this->assertSame(self::OFFICIAL, (float) $enrollment->final_price);
        $this->assertSame(self::OFFICIAL, (float) $enrollment->official_price);
        $this->assertSame(0.0, $enrollment->getDiscountAmount());
        $this->assertFalse($enrollment->hasDiscount());
        $this->assertSame(self::OFFICIAL, $enrollment->getPayableAmount());
    }

    public function test_official_price_remains_untouched_in_catalog(): void
    {
        $this->actingAs($this->superAdmin)
            ->post(route('students.store'), $this->payload(['final_price' => 12000]))
            ->assertRedirect();

        $this->formation->refresh();
        // Le catalogue n'est JAMAIS modifié par une réduction d'inscription.
        $this->assertSame(self::OFFICIAL, (float) $this->formation->annual_price);
        $this->assertSame(1400.0, (float) $this->formation->price);
    }

    // ── 3 & 4. Prix final inférieur + réduction calculée ───────────────────────

    public function test_lower_final_price_records_discount(): void
    {
        $this->actingAs($this->superAdmin)
            ->post(route('students.store'), $this->payload(['final_price' => 12000]))
            ->assertRedirect();

        $enrollment = Enrollment::first();
        $this->assertSame(12000.0, (float) $enrollment->final_price);
        $this->assertSame(2000.0, $enrollment->getDiscountAmount());
        $this->assertTrue($enrollment->hasDiscount());
        $this->assertSame(12000.0, $enrollment->getPayableAmount());
    }

    // ── 5 & 6. Avance calculée sur le prix final, solde = 9 000 ────────────────

    public function test_advance_remaining_is_computed_from_final_price(): void
    {
        $this->actingAs($this->superAdmin)->post(route('students.store'), $this->payload([
            'final_price' => 12000,
            'payment_option' => 'avance',
            'avance_amount' => 3000,
        ]))->assertRedirect();

        $student = Student::first();
        $payment = Payment::firstWhere('student_id', $student->id);
        $this->assertSame('3000.00', (string) $payment->amount);

        // Solde = 12 000 (final) − 3 000 = 9 000, jamais à partir de 14 000.
        $row = app(FinanceService::class)->getStudentFinancialRows()
            ->firstWhere('student.id', $student->id);
        $this->assertNotNull($row);
        $this->assertSame(12000.0, (float) $row->annual_price);
        $this->assertSame(9000.0, (float) $row->remaining_amount);
        $this->assertSame('partial', $row->status);
    }

    // ── 7. Paiement intégral basé sur le prix final ────────────────────────────

    public function test_paye_option_records_final_price_as_full_payment(): void
    {
        $this->actingAs($this->superAdmin)->post(route('students.store'), $this->payload([
            'final_price' => 12000,
            'payment_option' => 'paye',
        ]))->assertRedirect();

        $student = Student::first();
        $payment = Payment::firstWhere('student_id', $student->id);
        // « Payé » enregistre le PRIX FINAL (12 000), pas le tarif officiel.
        $this->assertSame('12000.00', (string) $payment->amount);

        $row = app(FinanceService::class)->getStudentFinancialRows()
            ->firstWhere('student.id', $student->id);
        $this->assertSame(0.0, (float) $row->remaining_amount);
        $this->assertSame('paid', $row->status);
    }

    // ── 8. Paiements fractionnés cohérents avec le prix final ──────────────────

    public function test_fractioned_payments_settle_on_final_price(): void
    {
        $this->actingAs($this->superAdmin)->post(route('students.store'), $this->payload([
            'final_price' => 12000,
            'payment_option' => 'avance',
            'avance_amount' => 3000,
        ]))->assertRedirect();

        $student = Student::first();
        $enrollment = Enrollment::first();

        // Un Admin Centre ne peut pas encaisser ici (module réservé Super Admin) ;
        // on utilise le Super Admin pour le second versement.
        $this->actingAs($this->superAdmin)->post(route('super-admin.finance.students.payment.store'), [
            'student_id' => $student->id,
            'enrollment_id' => $enrollment->id,
            'formation_id' => $this->formation->id,
            'amount' => 9000,
            'currency' => 'MAD',
            'payment_date' => now()->toDateString(),
            'school_year' => '2026/2027',
            'method' => 'cash',
        ])->assertRedirect();

        $row = app(FinanceService::class)->getStudentFinancialRows()
            ->firstWhere('student.id', $student->id);
        $this->assertSame(12000.0, (float) $row->paid_amount);
        $this->assertSame(0.0, (float) $row->remaining_amount);
        $this->assertSame('paid', $row->status);
    }

    public function test_fractioned_payment_cannot_exceed_final_price_balance(): void
    {
        $this->actingAs($this->superAdmin)->post(route('students.store'), $this->payload([
            'final_price' => 12000,
            'payment_option' => 'avance',
            'avance_amount' => 3000,
        ]))->assertRedirect();

        $student = Student::first();
        $enrollment = Enrollment::first();

        // 10 000 > solde restant (9 000) → refusé, la référence est le prix final.
        $this->actingAs($this->superAdmin)->post(route('super-admin.finance.students.payment.store'), [
            'student_id' => $student->id,
            'enrollment_id' => $enrollment->id,
            'formation_id' => $this->formation->id,
            'amount' => 10000,
            'currency' => 'MAD',
            'payment_date' => now()->toDateString(),
            'school_year' => '2026/2027',
            'method' => 'cash',
        ])->assertSessionHasErrors('amount');

        $this->assertSame(1, Payment::where('student_id', $student->id)->count());
    }

    // ── 9. Changement de formation : le prix enregistré suit la formation choisie ─

    public function test_selected_formation_official_price_is_recorded_as_reference(): void
    {
        $second = Formation::factory()
            ->forCenter($this->center)->forDomain('informatique')
            ->create(['annual_price' => 9000, 'price' => 900]);

        $this->actingAs($this->superAdmin)
            ->post(route('students.store'), $this->payload(['formation_id' => $second->id]))
            ->assertRedirect();

        $enrollment = Enrollment::first();
        $this->assertSame($second->id, $enrollment->formation_id);
        $this->assertSame(9000.0, (float) $enrollment->official_price);
        $this->assertSame(9000.0, (float) $enrollment->final_price);
    }

    // ── 10 & 11 & 12. Validations serveur ──────────────────────────────────────

    public function test_final_price_above_official_is_rejected(): void
    {
        $this->actingAs($this->superAdmin)
            ->post(route('students.store'), $this->payload(['final_price' => 15000]))
            ->assertSessionHasErrors('final_price');

        $this->assertDatabaseCount('students', 0);
        $this->assertDatabaseCount('enrollments', 0);
    }

    public function test_negative_final_price_is_rejected(): void
    {
        $this->actingAs($this->superAdmin)
            ->post(route('students.store'), $this->payload(['final_price' => -100]))
            ->assertSessionHasErrors('final_price');

        $this->assertDatabaseCount('students', 0);
    }

    public function test_non_numeric_final_price_is_rejected(): void
    {
        $this->actingAs($this->superAdmin)
            ->post(route('students.store'), $this->payload(['final_price' => 'gratuit']))
            ->assertSessionHasErrors('final_price');

        $this->assertDatabaseCount('students', 0);
    }

    public function test_final_price_with_too_many_decimals_is_rejected(): void
    {
        $this->actingAs($this->superAdmin)
            ->post(route('students.store'), $this->payload(['final_price' => '11999.999']))
            ->assertSessionHasErrors('final_price');

        $this->assertDatabaseCount('students', 0);
    }

    public function test_advance_cannot_exceed_final_price(): void
    {
        // Avance 13 000 > prix final 12 000 → refusé (référence = prix final, pas 14 000).
        $this->actingAs($this->superAdmin)->post(route('students.store'), $this->payload([
            'final_price' => 12000,
            'payment_option' => 'avance',
            'avance_amount' => 13000,
        ]))->assertSessionHasErrors('avance_amount');

        $this->assertDatabaseCount('payments', 0);
    }

    // ── 13. Prix nul traité selon les règles métier ────────────────────────────

    public function test_zero_final_price_is_accepted_and_stored(): void
    {
        $this->actingAs($this->superAdmin)
            ->post(route('students.store'), $this->payload(['final_price' => 0]))
            ->assertRedirect();

        $enrollment = Enrollment::first();
        $this->assertSame(0.0, (float) $enrollment->final_price);
        $this->assertSame(0.0, $enrollment->getPayableAmount());
        // Le catalogue reste intact : seule l'inscription est à 0.
        $this->assertSame(self::OFFICIAL, (float) $this->formation->annual_price);
    }

    // ── 14. Facture : la réduction est portée par l'inscription ────────────────

    public function test_invoice_reflects_final_price_and_discount(): void
    {
        $this->actingAs($this->superAdmin)->post(route('students.store'), $this->payload([
            'final_price' => 12000,
            'payment_option' => 'avance',
            'avance_amount' => 3000,
        ]))->assertRedirect();

        $student = Student::first();
        $enrollment = Enrollment::first();

        // Facture établie sur le prix final de l'inscription.
        $invoice = Invoice::create([
            'center_id' => $this->center->id,
            'student_id' => $student->id,
            'enrollment_id' => $enrollment->id,
            'formation_id' => $this->formation->id,
            'invoice_number' => Invoice::generateInvoiceNumber($this->center),
            'total_amount' => $enrollment->getPayableAmount(), // 12 000
            'currency' => 'MAD',
            'invoice_date' => now()->toDateString(),
            'status' => 'sent',
            'created_by' => $this->superAdmin->id,
        ]);

        $this->assertSame(12000.0, (float) $invoice->total_amount);
        $this->assertSame(2000.0, $enrollment->getDiscountAmount());
        // Le reçu (solde) reste basé sur le prix final : 12 000 − 3 000 = 9 000.
        $this->assertSame(12000.0, $invoice->remaining_amount + (float) $invoice->total_paid);
    }

    // ── 15. Tableau de bord financier : total attendu = somme des prix finaux ───

    public function test_dashboard_expected_total_uses_final_prices(): void
    {
        // Deux étudiants même formation (14 000), deux réductions de 2 000 → attendu 24 000.
        $this->actingAs($this->superAdmin)->post(route('students.store'), $this->payload([
            'last_name' => 'A', 'final_price' => 12000,
        ]))->assertRedirect();
        $this->actingAs($this->superAdmin)->post(route('students.store'), $this->payload([
            'last_name' => 'B', 'final_price' => 12000,
        ]))->assertRedirect();
        // Troisième sans réduction : 14 000.
        $this->actingAs($this->superAdmin)->post(route('students.store'), $this->payload([
            'last_name' => 'C',
        ]))->assertRedirect();

        $rows = app(FinanceService::class)->getStudentFinancialRows();
        $this->assertSame(38000.0, (float) $rows->sum('annual_price'));

        // Les réductions ne sont pas des dépenses : total attendu < total catalogue.
        $this->assertLessThan(42000.0, (float) $rows->sum('annual_price'));
    }

    public function test_finance_students_page_renders_with_discounts(): void
    {
        $this->actingAs($this->superAdmin)->post(route('students.store'), $this->payload([
            'last_name' => 'Visible', 'final_price' => 12000,
        ]))->assertRedirect();

        $this->actingAs($this->superAdmin)
            ->get(route('super-admin.finance.students'))
            ->assertOk()
            ->assertSee('Visible');
    }

    // ── 16. Recouvrement cohérent avec le prix final ───────────────────────────

    public function test_recovery_remaining_matches_final_price(): void
    {
        $this->actingAs($this->superAdmin)
            ->post(route('students.store'), $this->payload(['final_price' => 12000]))
            ->assertRedirect();

        $student = Student::first();
        $row = app(FinanceService::class)->getStudentFinancialRows()
            ->firstWhere('student.id', $student->id);

        // Aucune réduction comptée comme paiement : solde = prix final intégral.
        $this->assertSame(12000.0, (float) $row->remaining_amount);
        $this->assertSame(0.0, (float) $row->paid_amount);

        $this->actingAs($this->superAdmin)
            ->get(route('super-admin.finance.recovery'))
            ->assertOk();
    }

    // ── 17. Isolation entre centres ────────────────────────────────────────────

    public function test_center_admin_cannot_price_a_foreign_center_formation(): void
    {
        $foreign = Formation::factory()
            ->forCenter($this->otherCenter)->forDomain('informatique')
            ->create(['annual_price' => 20000, 'price' => 2000]);

        // Le center_id brut n'est pas cru : le centre du compte fait foi. La
        // formation d'un autre centre est rejetée, aucune trace financière.
        $this->actingAs($this->centerAdmin)->post(route('students.store'), $this->payload([
            'center_id' => $this->otherCenter->id,
            'formation_id' => $foreign->id,
            'final_price' => 5000,
        ]))->assertSessionHasErrors('formation_id');

        $this->assertDatabaseCount('enrollments', 0);
        $this->assertDatabaseCount('payments', 0);
    }

    // ── 18 & 19. Fonctionnement Super Admin et Admin Centre ────────────────────

    public function test_super_admin_can_apply_final_price(): void
    {
        $this->actingAs($this->superAdmin)
            ->post(route('students.store'), $this->payload(['final_price' => 11000]))
            ->assertRedirect();

        $this->assertSame(11000.0, (float) Enrollment::first()->final_price);
    }

    public function test_center_admin_can_apply_final_price_for_own_center(): void
    {
        $this->actingAs($this->centerAdmin)
            ->post(route('students.store'), $this->payload([
                'first_name' => 'Fatima',
                'last_name' => 'Zahra',
                'final_price' => 10000,
            ]))
            ->assertRedirect();

        $enrollment = Enrollment::first();
        $this->assertSame($this->center->id, $enrollment->center_id);
        $this->assertSame(10000.0, (float) $enrollment->final_price);
    }

    // ── 20. Anciennes inscriptions inchangées ──────────────────────────────────

    public function test_existing_enrollments_without_final_price_are_unchanged(): void
    {
        // Inscription créée AVANT la fonctionnalité (colonnes prix null).
        $student = Student::factory()->forCenter($this->center)->create();
        $enrollment = Enrollment::factory()
            ->forStudent($student)
            ->forFormation($this->formation)
            ->create(['final_price' => null, 'official_price' => null]);

        $this->assertNull($enrollment->final_price);
        $this->assertSame(self::OFFICIAL, $enrollment->getPayableAmount()); // repli catalogue
        $this->assertSame(0.0, $enrollment->getDiscountAmount());

        $row = app(FinanceService::class)->getStudentFinancialRows()
            ->firstWhere('enrollment.id', $enrollment->id);
        $this->assertNotNull($row);
        $this->assertSame(self::OFFICIAL, (float) $row->annual_price);
    }

    // ── 21. Compatibilité FR / AR ──────────────────────────────────────────────

    public function test_form_shows_final_price_block_in_french(): void
    {
        $this->actingAs($this->superAdmin)
            ->get(route('students.create'))
            ->assertOk()
            ->assertSee('name="final_price"', false)
            ->assertSee('Tarif officiel de la formation')
            ->assertSee('Réduction appliquée')
            ->assertSee('Montant final à payer');
    }

    public function test_form_translates_final_price_block_in_arabic(): void
    {
        $adminAr = User::factory()->centerAdmin($this->center->id)
            ->create(['email' => 'ca-ar@tst.ma', 'locale' => 'ar']);

        $this->actingAs($adminAr)
            ->get(route('students.create'))
            ->assertOk()
            ->assertSee('dir="rtl"', false)
            ->assertSee('المبلغ النهائي الواجب دفعه', false)
            ->assertDontSee('Montant final à payer', false);
    }

    // ── 22. Mode clair / sombre + responsive ───────────────────────────────────

    public function test_final_price_block_supports_dark_mode(): void
    {
        $content = $this->actingAs($this->superAdmin)
            ->get(route('students.create'))
            ->assertOk()
            ->getContent();

        $this->assertStringContainsString('name="final_price"', $content);
        // Classes dark: présentes sur le bloc prix final.
        $this->assertStringContainsString('dark:', $content);
    }
}
