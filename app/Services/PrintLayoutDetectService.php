<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\FormTemplate;
use Illuminate\Support\Facades\Storage;
use setasign\Fpdi\Fpdi;
use setasign\Fpdi\PdfParser\StreamReader;

/**
 * PDF-01B — detecção determinística (AcroForm + metadados).
 * Sugestões exigem revisão humana; não publica automaticamente.
 */
class PrintLayoutDetectService
{
    /**
     * @return array{
     *   version:int,
     *   mode:string,
     *   page_count:int,
     *   has_text:bool,
     *   message:string,
     *   suggestions:list<array<string,mixed>>
     * }
     */
    public function detect(FormTemplate $template): array
    {
        $bytes = $this->readSourceDocument($template);
        if ($bytes === null) {
            return [
                'version' => 1,
                'mode' => 'empty',
                'page_count' => 0,
                'has_text' => false,
                'message' => 'Anexe o PDF original antes de detectar campos.',
                'suggestions' => [],
            ];
        }

        $pageCount = $this->pageCount($bytes);
        $hasText = $this->looksDigital($bytes);
        $suggestions = $this->extractAcroFormSuggestions($bytes, $pageCount);

        $mode = 'empty';
        if ($suggestions !== []) {
            $mode = 'acroform';
        } elseif (! $hasText) {
            $mode = 'scanned';
        } else {
            $mode = 'text';
        }

        $message = match ($mode) {
            'acroform' => count($suggestions).' campo(s) AcroForm encontrados. Revise no editor.',
            'scanned' => 'PDF sem indícios de texto digital (provável escaneado). Use mapeamento manual ou detecção no editor.',
            'text' => 'PDF digital sem AcroForm. Use “Detectar” no editor para sugestões por rótulos de texto.',
            default => 'Nenhuma sugestão encontrada.',
        };

        return [
            'version' => 1,
            'mode' => $mode,
            'page_count' => $pageCount,
            'has_text' => $hasText,
            'message' => $message,
            'suggestions' => $suggestions,
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

    private function pageCount(string $bytes): int
    {
        try {
            $pdf = new Fpdi;
            return max(0, $pdf->setSourceFile(StreamReader::createByString($bytes)));
        } catch (\Throwable $e) {
            report($e);

            return 0;
        }
    }

    private function looksDigital(string $bytes): bool
    {
        // Heurística leve: presença de fontes / ToUnicode sugere texto digital.
        return str_contains($bytes, '/Font') || str_contains($bytes, '/ToUnicode') || str_contains($bytes, '/TJ');
    }

    /**
     * Extrai widgets AcroForm via varredura de dicionários /Annots (best-effort, sem lib extra).
     *
     * @return list<array<string,mixed>>
     */
    private function extractAcroFormSuggestions(string $bytes, int $pageCount): array
    {
        if (! str_contains($bytes, '/AcroForm') && ! str_contains($bytes, '/FT')) {
            return [];
        }

        $suggestions = [];
        // Campos com /T (nome) + /Rect [x1 y1 x2 y2] próximos
        if (! preg_match_all('/\/T\s*\(([^)]+)\)/s', $bytes, $names)) {
            return [];
        }

        $rectMatches = [];
        preg_match_all('/\/Rect\s*\[\s*([0-9.\-]+)\s+([0-9.\-]+)\s+([0-9.\-]+)\s+([0-9.\-]+)\s*\]/s', $bytes, $rectMatches, PREG_SET_ORDER);

        $count = min(count($names[1]), count($rectMatches), 80);
        for ($i = 0; $i < $count; $i++) {
            $name = trim($this->unescapePdfString($names[1][$i]));
            if ($name === '') {
                continue;
            }
            $r = $rectMatches[$i];
            $x1 = (float) $r[1];
            $y1 = (float) $r[2];
            $x2 = (float) $r[3];
            $y2 = (float) $r[4];
            // Sem MediaBox por página: assume A4 pt aproximado se coords tipicamente em pt
            $pageW = max(abs($x2 - $x1) * 4, 595.0);
            $pageH = max(abs($y2 - $y1) * 8, 842.0);
            // Tenta inferir a partir das próprias coords
            $pageW = max($pageW, max($x1, $x2) * 1.05);
            $pageH = max($pageH, max($y1, $y2) * 1.05);

            $x = min($x1, $x2) / $pageW;
            $w = abs($x2 - $x1) / $pageW;
            $h = abs($y2 - $y1) / $pageH;
            $y = 1 - (max($y1, $y2) / $pageH);

            if ($w < 0.01 || $h < 0.006) {
                continue;
            }

            $kind = 'text';
            $hay = mb_strtolower($name);
            if (str_contains($hay, 'assin') || str_contains($hay, 'signature')) {
                $kind = 'signature';
            } elseif (str_contains($hay, 'check') || str_contains($hay, 'aceite')) {
                $kind = 'checkbox';
            }

            $suggestions[] = [
                'temporary_id' => 'sug-'.bin2hex(random_bytes(4)),
                'label' => $name,
                'name_key' => $this->sanitizeNameKey($name),
                'kind' => $kind,
                'page' => 0,
                'x' => max(0, min(1, $x)),
                'y' => max(0, min(1, $y)),
                'w' => max(0.01, min(1, $w)),
                'h' => max(0.008, min(1, $h)),
                'confidence' => 0.7,
                'source' => 'acroform',
                'needs_review' => true,
            ];
        }

        return $suggestions;
    }

    private function unescapePdfString(string $value): string
    {
        return stripcslashes($value);
    }

    private function sanitizeNameKey(string $label): string
    {
        $ascii = @iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', $label);
        $base = strtolower((string) ($ascii !== false ? $ascii : $label));
        $base = preg_replace('/[^a-z0-9]+/', '_', $base) ?? 'campo';
        $base = trim($base, '_');

        return $base !== '' ? substr($base, 0, 48) : 'campo';
    }
}
