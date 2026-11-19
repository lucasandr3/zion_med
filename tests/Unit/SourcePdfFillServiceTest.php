<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Models\FormSubmission;
use App\Models\FormTemplate;
use App\Services\SourcePdfFillService;
use Illuminate\Support\Collection;
use Tests\TestCase;

/**
 * DOC-02 — regressão sintética (diagnose + normalização + prioridade do snapshot).
 * Sem dados de pacientes reais e sem depender de MinIO/HTTP.
 */
final class SourcePdfFillServiceTest extends TestCase
{
    public function test_diagnose_empty_layout(): void
    {
        $service = new SourcePdfFillService;
        $warnings = $service->diagnoseLayout(null);
        $this->assertSame('empty_layout', $warnings[0]['code'] ?? null);
    }

    public function test_diagnose_out_of_page_too_small_and_overlap(): void
    {
        $service = new SourcePdfFillService;
        $warnings = $service->diagnoseLayout([
            'version' => 1,
            'placements' => [
                ['name_key' => 'fora', 'page' => 0, 'x' => 0.9, 'y' => 0.9, 'w' => 0.2, 'h' => 0.2, 'kind' => 'text'],
                ['name_key' => 'mini', 'page' => 0, 'x' => 0.1, 'y' => 0.1, 'w' => 0.005, 'h' => 0.005, 'kind' => 'text'],
                ['name_key' => 'a', 'page' => 0, 'x' => 0.2, 'y' => 0.3, 'w' => 0.3, 'h' => 0.1, 'kind' => 'text'],
                ['name_key' => 'b', 'page' => 0, 'x' => 0.25, 'y' => 0.32, 'w' => 0.3, 'h' => 0.1, 'kind' => 'text'],
            ],
        ]);

        $codes = array_column($warnings, 'code');
        $this->assertContains('out_of_page', $codes);
        $this->assertContains('too_small', $codes);
        $this->assertContains('overlap', $codes);
    }

    public function test_normalize_layout_rejects_invalid_placements(): void
    {
        $service = new SourcePdfFillService;
        $this->assertNull($service->normalizeLayout(['placements' => []]));
        $this->assertNull($service->normalizeLayout(['placements' => [
            ['name_key' => '', 'page' => 0, 'x' => 0.1, 'y' => 0.1, 'w' => 0.2, 'h' => 0.05],
        ]]));

        $ok = $service->normalizeLayout([
            'version' => 1,
            'placements' => [
                ['name_key' => 'nome', 'page' => 0, 'x' => 0.1, 'y' => 0.1, 'w' => 0.4, 'h' => 0.04, 'kind' => 'text'],
            ],
        ]);
        $this->assertNotNull($ok);
        $this->assertCount(1, $ok['placements']);
        $this->assertSame('nome', $ok['placements'][0]['name_key']);
    }

    public function test_can_fill_prefers_snapshot_over_live_template(): void
    {
        $service = new SourcePdfFillService;

        $template = new FormTemplate;
        $template->forceFill([
            'source_document_path' => 'docs/sintetico.pdf',
            'source_document_disk' => 'minio_assets',
            // Layout vivo diferente (não deve prevalecer se o snapshot for válido).
            'print_layout' => [
                'version' => 1,
                'placements' => [
                    ['name_key' => 'outro', 'page' => 0, 'x' => 0.1, 'y' => 0.5, 'w' => 0.4, 'h' => 0.04, 'kind' => 'text'],
                ],
            ],
        ]);

        $submission = new FormSubmission;
        $submission->setRelation('template', $template);
        $submission->forceFill([
            'document_snapshot' => [
                'print_layout' => [
                    'version' => 1,
                    'placements' => [
                        ['name_key' => 'nome', 'page' => 0, 'x' => 0.1, 'y' => 0.1, 'w' => 0.5, 'h' => 0.04, 'kind' => 'text'],
                    ],
                ],
            ],
        ]);

        $this->assertTrue($service->canFill($submission));

        // Sem snapshot utilizável, ainda pode usar o mapa vivo.
        $submissionEmptySnap = new FormSubmission;
        $submissionEmptySnap->setRelation('template', $template);
        $submissionEmptySnap->forceFill(['document_snapshot' => ['print_layout' => ['placements' => []]]]);
        $this->assertTrue($service->canFill($submissionEmptySnap));

        $templateBare = new FormTemplate;
        $templateBare->forceFill([
            'source_document_path' => 'docs/sintetico.pdf',
            'print_layout' => null,
        ]);
        $submissionBare = new FormSubmission;
        $submissionBare->setRelation('template', $templateBare);
        $submissionBare->forceFill(['document_snapshot' => []]);
        $this->assertFalse($service->canFill($submissionBare));
    }

    public function test_fill_bytes_keeps_pdf_header_on_synthetic_a4(): void
    {
        if (! class_exists(\FPDF::class) && ! class_exists(\setasign\Fpdi\FpdfTpl::class)) {
            $this->markTestSkipped('FPDF/FPDI indisponível neste ambiente.');
        }

        $source = $this->syntheticA4Pdf('Formulario sintetico A4');
        $service = new SourcePdfFillService;
        $submission = new FormSubmission;
        $submission->setRelation('values', new Collection);
        $submission->setRelation('signatures', new Collection);

        $layout = [
            'version' => 1,
            'placements' => [
                [
                    'name_key' => 'nome',
                    'page' => 0,
                    'x' => 0.12,
                    'y' => 0.18,
                    'w' => 0.5,
                    'h' => 0.04,
                    'kind' => 'text',
                    'font_size' => 11,
                    'align' => 'left',
                ],
            ],
        ];

        // Sem value → ainda gera PDF (caixa vazia / sem texto).
        $filled = $service->fillBytes($source, $layout, $submission);
        $this->assertNotSame('', $filled);
        $this->assertStringStartsWith('%PDF', $filled);
        $this->assertNotSame($source, $filled);
    }

    private function syntheticA4Pdf(string $label): string
    {
        // FPDI estende FPDF; Output('S') devolve string.
        $pdf = new \setasign\Fpdi\Fpdi('P', 'mm', 'A4');
        $pdf->AddPage();
        $pdf->SetFont('Helvetica', '', 12);
        $pdf->Cell(0, 10, $label, 0, 1);
        $out = $pdf->Output('S');

        return is_string($out) ? $out : '';
    }
}
