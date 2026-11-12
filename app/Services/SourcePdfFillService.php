<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\FormSubmission;
use App\Models\FormTemplate;
use Illuminate\Support\Facades\Storage;
use setasign\Fpdi\Fpdi;
use setasign\Fpdi\PdfParser\StreamReader;

/**
 * Preenche o PDF original da clínica usando o mapa print_layout (separado dos campos digitais).
 */
class SourcePdfFillService
{
    /**
     * @return string|null Bytes do PDF preenchido, ou null se não houver layout/PDF fonte.
     */
    public function fillSubmission(FormSubmission $submission): ?string
    {
        $submission->loadMissing(['template', 'values', 'signatures']);
        $template = $submission->template;
        if (! $template instanceof FormTemplate || ! $template->hasSourceDocument()) {
            return null;
        }

        $layout = $this->normalizeLayout(
            $submission->document_snapshot['print_layout']
                ?? $template->print_layout
                ?? null
        );
        if ($layout === null) {
            return null;
        }

        $sourceBytes = $this->readSourceDocument($template);
        if ($sourceBytes === null) {
            return null;
        }

        return $this->fillBytes($sourceBytes, $layout, $submission);
    }

    /**
     * @param  array{version?:int,placements:list<array<string,mixed>>}  $layout
     */
    public function fillBytes(string $sourceBytes, array $layout, FormSubmission $submission): string
    {
        $pdf = new Fpdi;
        $pageCount = $pdf->setSourceFile(StreamReader::createByString($sourceBytes));
        $byPage = [];
        foreach ($layout['placements'] as $placement) {
            $page = (int) ($placement['page'] ?? 0);
            $byPage[$page][] = $placement;
        }

        $values = $submission->getValuesKeyed();
        $signaturesByField = [];
        foreach ($submission->signatures as $sig) {
            $key = (string) ($sig->field_key ?? '');
            if ($key !== '') {
                $signaturesByField[$key] = $sig;
            }
        }

        $tempFiles = [];

        try {
            for ($pageIndex = 0; $pageIndex < $pageCount; $pageIndex++) {
                $tplId = $pdf->importPage($pageIndex + 1);
                $size = $pdf->getTemplateSize($tplId);
                $orientation = ($size['width'] > $size['height']) ? 'L' : 'P';
                $pdf->AddPage($orientation, [$size['width'], $size['height']]);
                $pdf->useTemplate($tplId);

                $pageW = (float) $size['width'];
                $pageH = (float) $size['height'];

                foreach ($byPage[$pageIndex] ?? [] as $placement) {
                    $nameKey = (string) ($placement['name_key'] ?? '');
                    if ($nameKey === '') {
                        continue;
                    }

                    $x = ((float) $placement['x']) * $pageW;
                    $y = ((float) $placement['y']) * $pageH;
                    $w = ((float) $placement['w']) * $pageW;
                    $h = ((float) $placement['h']) * $pageH;
                    if ($w <= 0 || $h <= 0) {
                        continue;
                    }

                    $kind = (string) ($placement['kind'] ?? 'text');
                    $fontSize = (float) ($placement['font_size'] ?? max(8, min(14, $h * 0.55)));
                    $align = strtoupper((string) ($placement['align'] ?? 'L'));
                    $align = in_array($align, ['L', 'C', 'R'], true) ? $align : 'L';

                    if ($kind === 'signature' || $kind === 'image') {
                        $sig = $signaturesByField[$nameKey] ?? null;
                        $path = is_object($sig) ? (string) ($sig->image_path ?? '') : '';
                        if ($path === '') {
                            continue;
                        }
                        $imgBytes = $this->readSignatureBytes($path);
                        if ($imgBytes === null) {
                            continue;
                        }
                        $tmp = tempnam(sys_get_temp_dir(), 'sigpng_');
                        if ($tmp === false) {
                            continue;
                        }
                        $tmpPng = $tmp.'.png';
                        @unlink($tmp);
                        file_put_contents($tmpPng, $imgBytes);
                        $tempFiles[] = $tmpPng;
                        $pdf->Image($tmpPng, $x, $y, $w, $h, 'PNG');
                        continue;
                    }

                    if ($kind === 'checkbox') {
                        if ($this->isChecked($values->get($nameKey))) {
                            $this->drawCheckmark($pdf, $x, $y, $w, $h);
                        }
                        continue;
                    }

                    $text = $this->valueToText($values->get($nameKey));
                    if ($text === '') {
                        continue;
                    }

                    $pdf->SetFont('Helvetica', '', $fontSize);
                    $pdf->SetTextColor(15, 23, 42);
                    $pdf->SetXY($x, $y);
                    $pdf->MultiCell($w, max(3.2, $fontSize * 0.45), $this->safeText($text), 0, $align);
                }
            }

            return $pdf->Output('S');
        } finally {
            foreach ($tempFiles as $file) {
                @unlink($file);
            }
        }
    }

    /**
     * @return array{version:int,placements:list<array<string,mixed>>}|null
     */
    public function normalizeLayout(mixed $raw): ?array
    {
        if (! is_array($raw)) {
            return null;
        }
        $placementsRaw = $raw['placements'] ?? null;
        if (! is_array($placementsRaw) || $placementsRaw === []) {
            return null;
        }

        $placements = [];
        foreach ($placementsRaw as $item) {
            if (! is_array($item)) {
                continue;
            }
            $nameKey = trim((string) ($item['name_key'] ?? ''));
            if ($nameKey === '') {
                continue;
            }
            $page = (int) ($item['page'] ?? -1);
            $x = (float) ($item['x'] ?? -1);
            $y = (float) ($item['y'] ?? -1);
            $w = (float) ($item['w'] ?? 0);
            $h = (float) ($item['h'] ?? 0);
            if ($page < 0 || $w < 0.005 || $h < 0.005) {
                continue;
            }
            $placements[] = [
                'name_key' => $nameKey,
                'page' => $page,
                'x' => max(0, min(1, $x)),
                'y' => max(0, min(1, $y)),
                'w' => max(0.005, min(1, $w)),
                'h' => max(0.005, min(1, $h)),
                'font_size' => isset($item['font_size']) ? (float) $item['font_size'] : null,
                'align' => $item['align'] ?? 'left',
                'kind' => $item['kind'] ?? 'text',
            ];
        }

        if ($placements === []) {
            return null;
        }

        return [
            'version' => 1,
            'placements' => $placements,
        ];
    }

    private function readSourceDocument(FormTemplate $template): ?string
    {
        $path = (string) ($template->source_document_path ?? '');
        if ($path === '') {
            return null;
        }
        $disk = (string) ($template->source_document_disk ?: 'minio_assets');
        try {
            if (! Storage::disk($disk)->exists($path)) {
                return null;
            }
            $bytes = Storage::disk($disk)->get($path);

            return is_string($bytes) && $bytes !== '' ? $bytes : null;
        } catch (\Throwable $e) {
            report($e);

            return null;
        }
    }

    private function readSignatureBytes(string $path): ?string
    {
        try {
            if (Storage::disk('minio_submissions')->exists($path)) {
                $bytes = Storage::disk('minio_submissions')->get($path);
                if (is_string($bytes) && $bytes !== '') {
                    return $bytes;
                }
            }
            if (Storage::disk('public')->exists($path)) {
                $bytes = Storage::disk('public')->get($path);
                if (is_string($bytes) && $bytes !== '') {
                    return $bytes;
                }
            }
        } catch (\Throwable $e) {
            report($e);
        }

        return null;
    }

    private function valueToText(mixed $valueRow): string
    {
        if ($valueRow === null) {
            return '';
        }
        if (is_object($valueRow)) {
            if ($valueRow->value_json !== null) {
                $json = $valueRow->value_json;
                if (is_array($json)) {
                    return implode(', ', array_map(fn ($v) => trim((string) $v), $json));
                }
                if (is_bool($json)) {
                    return $json ? 'Sim' : 'Não';
                }

                return trim((string) $json);
            }

            return trim((string) ($valueRow->value_text ?? ''));
        }

        return trim((string) $valueRow);
    }

    private function isChecked(mixed $valueRow): bool
    {
        $text = mb_strtolower($this->valueToText($valueRow));
        if (in_array($text, ['1', 'true', 'sim', 'yes', 'x', 'on'], true)) {
            return true;
        }
        if (is_object($valueRow) && is_bool($valueRow->value_json)) {
            return (bool) $valueRow->value_json;
        }

        return $text !== '' && ! in_array($text, ['0', 'false', 'não', 'nao', 'no', 'off'], true);
    }

    private function drawCheckmark(Fpdi $pdf, float $x, float $y, float $w, float $h): void
    {
        $size = min($w, $h);
        $pad = $size * 0.15;
        $left = $x + $pad;
        $top = $y + $pad;
        $right = $x + $size - $pad;
        $bottom = $y + $size - $pad;
        $midX = $left + ($right - $left) * 0.35;
        $midY = $top + ($bottom - $top) * 0.65;

        $pdf->SetDrawColor(15, 23, 42);
        $pdf->SetLineWidth(max(0.4, $size * 0.08));
        $pdf->Line($left, $top + ($bottom - $top) * 0.45, $midX, $midY);
        $pdf->Line($midX, $midY, $right, $top);
    }

    private function safeText(string $text): string
    {
        // Helvetica core font: translitera acentos quando possível
        $converted = @iconv('UTF-8', 'ISO-8859-1//TRANSLIT', $text);
        if (is_string($converted) && $converted !== '') {
            return $converted;
        }

        return preg_replace('/[^\x20-\x7E\n\r\t]/', '?', $text) ?? $text;
    }
}
