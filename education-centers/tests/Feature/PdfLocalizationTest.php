<?php

namespace Tests\Feature;

use App\Models\Certificate;
use App\Models\Expense;
use App\Services\PdfService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class PdfLocalizationTest extends TestCase
{
    use RefreshDatabase;

    public function test_private_disk_is_configured_for_generated_pdfs(): void
    {
        $this->assertSame(storage_path('app/private'), config('filesystems.disks.private.root'));
        $this->assertTrue(Storage::disk('private')->put('tests/private-disk.txt', 'pdf'));
        $this->assertTrue(Storage::disk('private')->exists('tests/private-disk.txt'));

        Storage::disk('private')->delete('tests/private-disk.txt');
    }

    public function test_pdf_service_generates_an_arabic_pdf(): void
    {
        $this->app->setLocale('ar');

        $pdf = app(PdfService::class)->renderHtml(
            '<html lang="ar" dir="rtl"><body><h1>شهادة رسمية</h1><p>مرحبا بكم في مركز التعليم</p></body></html>'
        );

        $this->assertStringStartsWith('%PDF', $pdf);
        $this->assertGreaterThan(1000, strlen($pdf));
    }

    public function test_certificate_pdf_view_uses_arabic_labels(): void
    {
        $this->app->setLocale('ar');

        $certificate = new Certificate([
            'certificate_number' => 'CERT-AR-000001',
            'title' => 'شهادة تكوين',
            'issued_date' => Carbon::parse('2026-10-22'),
            'verify_token' => 'verification-token-123',
            'status' => 'issued',
        ]);

        $html = view('certificates.pdf.generic', ['certificate' => $certificate])->render();

        $this->assertStringContainsString('شهادة رسمية', $html);
        $this->assertStringContainsString('تشهد هذه الوثيقة أن', $html);
        $this->assertStringContainsString('رمز التحقق', $html);
        $this->assertStringContainsString('dir="rtl"', $html);

        $pdf = app(PdfService::class)->render('certificates.pdf.generic', [
            'certificate' => $certificate,
        ]);

        $this->assertStringStartsWith('%PDF', $pdf);
        $this->assertGreaterThan(1000, strlen($pdf));
    }

    public function test_expense_receipt_pdf_view_uses_arabic_labels(): void
    {
        $this->app->setLocale('ar');

        $expense = new Expense([
            'title' => 'Achat de matériel',
            'amount' => 1500,
            'currency' => 'MAD',
            'expense_date' => Carbon::parse('2026-10-22'),
            'payment_method' => 'cash',
            'supplier' => 'Fournisseur test',
            'reference' => 'DEP-00001',
            'status' => 'recorded',
        ]);
        $expense->setRelation('items', collect());
        $expense->setRelation('center', null);
        $expense->setRelation('category', null);

        $html = view('super-admin.finance.pdf.expense-receipt', ['expense' => $expense])->render();

        $this->assertStringContainsString('إيصال مصروف', $html);
        $this->assertStringContainsString('معلومات المصروف', $html);
        $this->assertStringContainsString('د.م.', $html);

        $pdf = app(PdfService::class)->render('super-admin.finance.pdf.expense-receipt', [
            'expense' => $expense,
        ]);

        $this->assertStringStartsWith('%PDF', $pdf);
        $this->assertGreaterThan(1000, strlen($pdf));
    }
}
