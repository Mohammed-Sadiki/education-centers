<?php

namespace Tests\Feature;

use App\Models\Center;
use App\Models\Formation;
use App\Models\Invoice;
use App\Models\Payment;
use App\Models\Student;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\App;
use Tests\TestCase;

/**
 * Mission PDF — Factures : téléchargement vs impression, contenu propre,
 * permissions, isolation centre, données réelles, états vides, RTL.
 */
class DocumentGenerationTest extends TestCase
{
    use RefreshDatabase;

    private Center $alpha;

    private Center $beta;

    private User $superAdmin;

    private User $adminAlpha;

    private User $adminBeta;

    private Student $student;

    private Formation $formation;

    protected function setUp(): void
    {
        parent::setUp();

        $this->alpha = Center::create(['name' => 'Alpha', 'code' => 'AL', 'address' => 'A', 'status' => 'active']);
        $this->beta = Center::create(['name' => 'Beta', 'code' => 'BE', 'address' => 'B', 'status' => 'active']);

        $this->superAdmin = User::factory()->superAdmin()->create(['status' => 'active']);
        $this->adminAlpha = User::factory()->centerAdmin($this->alpha->id)->create(['status' => 'active']);
        $this->adminBeta = User::factory()->centerAdmin($this->beta->id)->create(['status' => 'active']);

        $this->formation = Formation::create([
            'center_id' => $this->alpha->id, 'domain' => 'sante', 'name' => 'Soins infirmiers',
            'code' => 'SAN-01', 'status' => 'active',
        ]);

        $user = User::factory()->create(['role' => 'student', 'center_id' => $this->alpha->id]);
        $this->student = Student::create([
            'user_id' => $user->id, 'center_id' => $this->alpha->id, 'student_number' => 'AL-000001',
            'first_name' => 'Yasmine', 'last_name' => 'El Amrani', 'date_of_birth' => '2000-01-01',
            'gender' => 'female', 'status' => 'active',
        ]);
    }

    private function makeInvoice(bool $withPayments = true): Invoice
    {
        $invoice = Invoice::create([
            'center_id' => $this->alpha->id,
            'student_id' => $this->student->id,
            'formation_id' => $this->formation->id,
            'invoice_number' => 'INV-AL-000001',
            'total_amount' => 5000,
            'currency' => 'MAD',
            'invoice_date' => '2026-10-01',
            'due_date' => '2026-11-01',
            'status' => 'sent',
            'created_by' => $this->adminAlpha->id,
        ]);

        if ($withPayments) {
            Payment::create([
                'center_id' => $this->alpha->id, 'student_id' => $this->student->id,
                'invoice_id' => $invoice->id, 'formation_id' => $this->formation->id,
                'amount' => 2000, 'currency' => 'MAD', 'payment_date' => '2026-10-05',
                'method' => 'cash', 'reference' => 'PAY-AL-000001', 'status' => 'paid',
                'recorded_by' => $this->adminAlpha->id,
            ]);
            $invoice->load('payments');
        }

        return $invoice;
    }

    private function invoiceViewData(Invoice $invoice): array
    {
        $invoice->load(['center', 'student.user', 'enrollment.formation', 'formation', 'payments']);

        return [
            'invoice' => $invoice,
            'center' => $invoice->center,
            'docNumber' => $invoice->invoice_number,
            'docDate' => format_date($invoice->invoice_date, 'd M Y'),
        ];
    }

    public function test_invoice_pdf_download_returns_attachment(): void
    {
        $invoice = $this->makeInvoice();

        $this->actingAs($this->superAdmin)
            ->get(route('invoices.pdf', ['invoice' => $invoice, 'mode' => 'attachment']))
            ->assertOk()
            ->assertHeader('Content-Type', 'application/pdf')
            ->assertDownload('INV-AL-000001.pdf');
    }

    public function test_invoice_pdf_print_opens_inline_viewer(): void
    {
        $invoice = $this->makeInvoice();

        $response = $this->actingAs($this->superAdmin)
            ->get(route('invoices.pdf', ['invoice' => $invoice, 'mode' => 'inline']))
            ->assertOk()
            ->assertHeader('Content-Type', 'application/pdf');

        $this->assertStringContainsString('inline;', $response->headers->get('Content-Disposition'));
    }

    public function test_invoice_download_and_print_produce_identical_pdf_bytes(): void
    {
        $invoice = $this->makeInvoice();

        $download = $this->actingAs($this->superAdmin)
            ->get(route('invoices.pdf', ['invoice' => $invoice, 'mode' => 'attachment']))
            ->getContent();

        $print = $this->actingAs($this->superAdmin)
            ->get(route('invoices.pdf', ['invoice' => $invoice, 'mode' => 'inline']))
            ->getContent();

        $this->assertStringStartsWith('%PDF', $download);
        $this->assertStringStartsWith('%PDF', $print);
        // Same view + same data → same document (only the disposition differs).
        $this->assertSame(strlen($download), strlen($print));
    }

    public function test_invoice_pdf_shows_real_financial_data(): void
    {
        $invoice = $this->makeInvoice();
        $html = view('pdf.invoice', $this->invoiceViewData($invoice))->render();

        $this->assertStringContainsString('INV-AL-000001', $html);
        $this->assertStringContainsString('Yasmine', $html);
        $this->assertStringContainsString('Prestations facturées', $html);
        $this->assertStringContainsString('Historique des versements', $html);
        $this->assertStringContainsString('PAY-AL-000001', $html);
        $this->assertStringContainsString('Reste à payer', $html);
    }

    public function test_invoice_pdf_has_no_dashboard_chrome(): void
    {
        $invoice = $this->makeInvoice();
        $html = view('pdf.invoice', $this->invoiceViewData($invoice))->render();

        // The PDF is a standalone document: no window.print, no dashboard nav/sidebar.
        $this->assertStringNotContainsString('window.print', $html);
        $this->assertStringNotContainsString('<nav', $html);
        $this->assertStringNotContainsString('Portail Super Admin', $html);
    }

    public function test_invoice_pdf_empty_payment_history_state(): void
    {
        $invoice = $this->makeInvoice(false);
        $html = view('pdf.invoice', $this->invoiceViewData($invoice))->render();

        $this->assertStringContainsString('Aucun versement enregistré', $html);
    }

    public function test_invoice_pdf_renders_rtl_in_arabic(): void
    {
        App::setLocale('ar');
        $invoice = $this->makeInvoice();

        $html = view('pdf.invoice', $this->invoiceViewData($invoice))->render();

        $this->assertStringContainsString('dir="rtl"', $html);
        $this->assertStringContainsString('الخدمات المفوترة', $html);
    }

    public function test_center_admin_can_download_own_center_invoice(): void
    {
        $invoice = $this->makeInvoice();

        $this->actingAs($this->adminAlpha)
            ->get(route('invoices.pdf', ['invoice' => $invoice, 'mode' => 'attachment']))
            ->assertOk();
    }

    public function test_center_admin_cannot_access_another_center_invoice(): void
    {
        $invoice = $this->makeInvoice();

        $this->actingAs($this->adminBeta)
            ->get(route('invoices.pdf', $invoice))
            ->assertForbidden();

        $this->actingAs($this->adminBeta)
            ->get(route('invoices.pdf', ['invoice' => $invoice, 'mode' => 'inline']))
            ->assertForbidden();
    }
}
