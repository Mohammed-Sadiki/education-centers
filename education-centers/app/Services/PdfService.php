<?php

namespace App\Services;

use Illuminate\Http\Response;
use Mpdf\Config\ConfigVariables;
use Mpdf\Config\FontVariables;
use Mpdf\Mpdf;
use Mpdf\Output\Destination;

/**
 * Renders PDF documents with correct Arabic shaping and RTL direction.
 *
 * Dompdf cannot shape Arabic script reliably, so all generated documents
 * (certificates, expense receipts) go through mPDF which performs Arabic
 * glyph shaping and supports right-to-left layout.
 */
class PdfService
{
    /**
     * Render a Blade view to raw PDF bytes.
     *
     * @param  array<string, mixed>  $data
     */
    public function render(string $view, array $data = [], string $paper = 'A4', string $orientation = 'portrait'): string
    {
        return $this->renderHtml(view($view, $data)->render(), $paper, $orientation);
    }

    /**
     * Render raw HTML to raw PDF bytes.
     */
    public function renderHtml(string $html, string $paper = 'A4', string $orientation = 'portrait', string $title = ''): string
    {
        $mpdf = new Mpdf($this->configuration($paper, $orientation));

        $mpdf->SetDirectionality(is_rtl() ? 'rtl' : 'ltr');
        $mpdf->autoScriptToLang = true;
        $mpdf->autoLangToFont = true;

        if ($title !== '') {
            $mpdf->SetTitle($title);
        }

        $mpdf->WriteHTML($html);

        return $mpdf->Output('', Destination::STRING_RETURN);
    }

    /**
     * Build an HTTP response containing a generated PDF.
     *
     * @param  array<string, mixed>  $data
     */
    public function download(string $view, array $data, string $filename, string $paper = 'A4', string $orientation = 'portrait'): Response
    {
        $content = $this->render($view, $data, $paper, $orientation);

        return response($content, 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'attachment; filename="'.$filename.'"',
            'Cache-Control' => 'private, max-age=0, must-revalidate',
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    private function configuration(string $paper, string $orientation): array
    {
        $defaultConfig = (new ConfigVariables)->getDefaults();
        $fontDirectories = $defaultConfig['fontDir'];
        $defaultFontConfig = (new FontVariables)->getDefaults();

        $tempDirectory = storage_path('app/mpdf');
        if (! is_dir($tempDirectory)) {
            mkdir($tempDirectory, 0775, true);
        }

        return [
            'mode' => 'utf-8',
            'format' => $paper,
            'orientation' => $orientation,
            'tempDir' => $tempDirectory,
            'fontDir' => array_merge($fontDirectories, [resource_path('fonts')]),
            'fontdata' => $defaultFontConfig['fontdata'],
            'default_font' => is_rtl() ? 'xbriyaz' : 'dejavusans',
            'autoScriptToLang' => true,
            'autoLangToFont' => true,
            'margin_left' => 12,
            'margin_right' => 12,
            'margin_top' => 14,
            'margin_bottom' => 16,
            'margin_footer' => 6,
        ];
    }
}
