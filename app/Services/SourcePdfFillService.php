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
    /** Indica se o protocolo pode sair no layout original da clínica. */
    public function canFill(FormSubmission $submission): bool
    {
        $submission->loadMissing(['template']);
        $template = $submission->template;
        if (! $template instanceof FormTemplate || ! $template->hasSourceDocument()) {
            return false;
        }

        return $this->resolvePrintLayout($submission, $template) !== null;
    }

    /**
     * @return string|null Bytes do PDF preenchido, ou null se não houver layout/PDF fonte.
     */
    public function fillSubmission(FormSubmission $submission): ?string
    {
        $submission->loadMissing(['template', 'values', 'signatures', 'organization', 'approvedByUser']);
        $template = $submission->template;
        if (! $template instanceof FormTemplate || ! $template->hasSourceDocument()) {
            return null;
        }

        $layout = $this->resolvePrintLayout($submission, $template);
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
     * Aparência atual do modelo tem prioridade. Snapshot vazio/`[]` NÃO pode bloquear o mapa vivo
     * (o operador `??` do PHP não faz fallback quando a chave existe com array vazio).
     *
     * @return array{version:int,placements:list<array<string,mixed>>}|null
     */
    private function resolvePrintLayout(FormSubmission $submission, FormTemplate $template): ?array
    {
        return $this->normalizeLayout($template->print_layout ?? null)
            ?? $this->normalizeLayout($submission->document_snapshot['print_layout'] ?? null);
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
                    $fontSize = (float) ($placement['font_size'] ?? max(7, min(10, $h * 0.72)));
                    $align = strtoupper((string) ($placement['align'] ?? 'L'));
                    $align = in_array($align, ['L', 'C', 'R'], true) ? $align : 'L';

                    if ($kind === 'signature' || $kind === 'image') {
                        $img = $this->resolvePlacementImage(
                            $nameKey,
                            $signaturesByField,
                            $submission,
                        );
                        if ($img === null) {
                            continue;
                        }
                        [$imgBytes, $imgType] = $img;
                        $stripWhite = $kind === 'signature' || $nameKey === '__clinic_signature';
                        if ($stripWhite) {
                            $cleared = $this->makeWhitePixelsTransparent($imgBytes);
                            if ($cleared !== null) {
                                $imgBytes = $cleared;
                                $imgType = 'PNG';
                            }
                        }
                        $ext = $imgType === 'JPEG' ? '.jpg' : '.png';
                        $tmp = tempnam(sys_get_temp_dir(), 'sigimg_');
                        if ($tmp === false) {
                            continue;
                        }
                        $tmpFile = $tmp.$ext;
                        @unlink($tmp);
                        file_put_contents($tmpFile, $imgBytes);
                        $tempFiles[] = $tmpFile;
                        $pdf->Image($tmpFile, $x, $y, $w, $h, $imgType);
                        continue;
                    }

                    if ($kind === 'checkbox') {
                        $option = trim((string) ($placement['option'] ?? ''));
                        if ($option !== '') {
                            if ($this->optionIsSelected($values->get($nameKey), $option)) {
                                $this->drawCheckmark($pdf, $x, $y, $w, $h);
                            }
                        } elseif ($this->isChecked($values->get($nameKey))) {
                            $this->drawCheckmark($pdf, $x, $y, $w, $h);
                        }
                        continue;
                    }

                    $text = $nameKey === '__verify_code'
                        ? $this->verificationCode($submission)
                        : $this->valueToText($values->get($nameKey));
                    $option = trim((string) ($placement['option'] ?? ''));
                    if ($option !== '' && $this->isDatePartOption($option)) {
                        $text = $this->extractDatePart($text, strtolower($option));
                    }
                    if ($text === '') {
                        continue;
                    }

                    $this->drawFittedText($pdf, $text, $x, $y, $w, $h, $fontSize, $align, $option);
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
                'option' => isset($item['option']) && trim((string) $item['option']) !== ''
                    ? trim((string) $item['option'])
                    : null,
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

    /**
     * @param  array<string, mixed>  $signaturesByField
     * @return array{0:string,1:'PNG'|'JPEG'}|null
     */
    private function resolvePlacementImage(
        string $nameKey,
        array $signaturesByField,
        FormSubmission $submission,
    ): ?array {
        if ($nameKey === '__clinic_logo') {
            return $this->readOrganizationLogo($submission);
        }

        if ($nameKey === '__verify_qr') {
            return $this->buildVerificationQrPng($submission);
        }

        if ($nameKey === '__clinic_signature') {
            $clinicSig = $this->resolveClinicSignatureImage($signaturesByField, $submission);
            if ($clinicSig !== null) {
                return $clinicSig;
            }

            return $this->readOrganizationLogo($submission);
        }

        $sig = $signaturesByField[$nameKey] ?? null;
        $path = is_object($sig) ? (string) ($sig->image_path ?? '') : '';
        if ($path === '') {
            return null;
        }
        $bytes = $this->readSignatureBytes($path);
        if ($bytes === null) {
            return null;
        }

        return [$bytes, $this->detectImageType($bytes)];
    }

    /**
     * Assinatura institucional: eletrônica do aprovador/usuário > co-assinatura profissional > (logo no caller).
     *
     * @param  array<string, mixed>  $signaturesByField
     * @return array{0:string,1:'PNG'|'JPEG'}|null
     */
    private function resolveClinicSignatureImage(array $signaturesByField, FormSubmission $submission): ?array
    {
        $electronic = $this->resolveElectronicSignatureImage($submission);
        if ($electronic !== null) {
            return $electronic;
        }

        $preferredKeys = ['assinatura_profissional'];
        $submission->loadMissing('template.fields');
        $template = $submission->template;
        if ($template) {
            try {
                $preferredKeys[] = app(SignatureFieldResolver::class)->resolveProfessionalFieldKey($template);
            } catch (\Throwable $e) {
                report($e);
            }
        }

        foreach (array_unique($preferredKeys) as $key) {
            $sig = $signaturesByField[$key] ?? null;
            $path = is_object($sig) ? (string) ($sig->image_path ?? '') : '';
            if ($path === '') {
                continue;
            }
            $bytes = $this->readSignatureBytes($path);
            if ($bytes !== null) {
                return [$bytes, $this->detectImageType($bytes)];
            }
        }

        // Heurística: campo de assinatura com etiqueta de profissional/clínica
        foreach ($signaturesByField as $key => $sig) {
            $hay = mb_strtolower((string) $key);
            $label = '';
            $field = $template?->fields?->first(fn ($f) => (string) ($f->name_key ?? '') === (string) $key);
            if ($field) {
                $label = mb_strtolower((string) ($field->label ?? ''));
            }
            $blob = $hay.' '.$label;
            $score = 0;
            foreach (['profissional', 'responsavel', 'responsável', 'clinica', 'clínica', 'equipe', 'medico', 'médico'] as $w) {
                if (str_contains($blob, $w)) {
                    $score += 3;
                }
            }
            foreach (['paciente', 'cliente', 'titular', 'acompanhante'] as $w) {
                if (str_contains($blob, $w)) {
                    $score -= 5;
                }
            }
            if ($score <= 0) {
                continue;
            }
            $path = is_object($sig) ? (string) ($sig->image_path ?? '') : '';
            if ($path === '') {
                continue;
            }
            $bytes = $this->readSignatureBytes($path);
            if ($bytes !== null) {
                return [$bytes, $this->detectImageType($bytes)];
            }
        }

        return null;
    }

    /**
     * Assinatura eletrônica do perfil (aprovador do protocolo ou usuário autenticado).
     *
     * @return array{0:string,1:'PNG'|'JPEG'}|null
     */
    private function resolveElectronicSignatureImage(FormSubmission $submission): ?array
    {
        $submission->loadMissing('approvedByUser');
        $candidates = [];
        if ($submission->approvedByUser) {
            $candidates[] = $submission->approvedByUser;
        }
        try {
            $authUser = auth()->user();
            if ($authUser instanceof \App\Models\User) {
                $candidates[] = $authUser;
            }
        } catch (\Throwable) {
            // CLI / fila sem auth
        }

        foreach ($candidates as $user) {
            $path = (string) ($user->electronic_signature_path ?? '');
            if ($path === '') {
                continue;
            }
            $bytes = $this->readSignatureBytes($path);
            if ($bytes !== null) {
                return [$bytes, $this->detectImageType($bytes)];
            }
        }

        return null;
    }

    /**
     * @return array{0:string,1:'PNG'|'JPEG'}|null
     */
    private function readOrganizationLogo(FormSubmission $submission): ?array
    {
        $submission->loadMissing('organization');
        $org = $submission->organization;
        if (! $org) {
            return null;
        }
        $path = (string) ($org->logo_path ?? '');
        if ($path === '') {
            return null;
        }

        try {
            if (Storage::disk('minio_assets')->exists($path)) {
                $bytes = Storage::disk('minio_assets')->get($path);
                if (is_string($bytes) && $bytes !== '') {
                    return [$bytes, $this->detectImageType($bytes)];
                }
            }
            if (Storage::disk('public')->exists($path)) {
                $bytes = Storage::disk('public')->get($path);
                if (is_string($bytes) && $bytes !== '') {
                    return [$bytes, $this->detectImageType($bytes)];
                }
            }
        } catch (\Throwable $e) {
            report($e);
        }

        return null;
    }

    /** @return 'PNG'|'JPEG' */
    private function detectImageType(string $bytes): string
    {
        if (str_starts_with($bytes, "\xFF\xD8\xFF")) {
            return 'JPEG';
        }

        return 'PNG';
    }

    private function verificationCode(FormSubmission $submission): string
    {
        $hash = strtolower(trim((string) ($submission->document_hash ?? '')));
        if ($hash === '') {
            $submission->loadMissing('signatures');
            foreach ($submission->signatures as $sig) {
                $h = strtolower(trim((string) ($sig->document_hash ?? '')));
                if (strlen($h) >= 8) {
                    $hash = $h;
                    break;
                }
            }
        }

        return $hash !== '' ? strtoupper(substr($hash, 0, 8)) : '';
    }

    private function verificationUrl(FormSubmission $submission): string
    {
        $code = $this->verificationCode($submission);
        if ($code === '') {
            return '';
        }
        $base = rtrim((string) config('app.frontend_url', config('app.url')), '/');

        return $base.'/verificar/'.$code;
    }

    /**
     * @return array{0:string,1:'PNG'}|null
     */
    private function buildVerificationQrPng(FormSubmission $submission): ?array
    {
        $url = $this->verificationUrl($submission);
        if ($url === '' || ! class_exists(\chillerlan\QRCode\QRCode::class)) {
            return null;
        }

        try {
            $options = new \chillerlan\QRCode\QROptions;
            $options->outputInterface = \chillerlan\QRCode\Output\QRGdImagePNG::class;
            $options->scale = 6;
            $options->outputBase64 = false;
            $options->quietzoneSize = 1;

            $png = (new \chillerlan\QRCode\QRCode($options))->render($url);
            if (! is_string($png) || $png === '') {
                return null;
            }
            if (str_starts_with($png, 'data:image')) {
                $raw = preg_replace('#^data:image/[^;]+;base64,#', '', $png) ?? '';
                $decoded = base64_decode($raw, true);
                if (! is_string($decoded) || $decoded === '') {
                    return null;
                }

                return [$decoded, 'PNG'];
            }

            return [$png, 'PNG'];
        } catch (\Throwable $e) {
            report($e);

            return null;
        }
    }

    /**
     * Torna pixels brancos/quase brancos transparentes (assinatura sem retângulo opaco no PDF).
     */
    private function makeWhitePixelsTransparent(string $bytes): ?string
    {
        if (! function_exists('imagecreatefromstring') || ! function_exists('imagepng')) {
            return null;
        }

        try {
            $src = @imagecreatefromstring($bytes);
            if ($src === false) {
                return null;
            }

            $w = imagesx($src);
            $h = imagesy($src);
            if ($w < 1 || $h < 1) {
                imagedestroy($src);

                return null;
            }

            $dst = imagecreatetruecolor($w, $h);
            if ($dst === false) {
                imagedestroy($src);

                return null;
            }

            imagealphablending($dst, false);
            imagesavealpha($dst, true);
            $transparent = imagecolorallocatealpha($dst, 0, 0, 0, 127);
            imagefilledrectangle($dst, 0, 0, $w, $h, $transparent);

            for ($y = 0; $y < $h; $y++) {
                for ($x = 0; $x < $w; $x++) {
                    $rgba = imagecolorat($src, $x, $y);
                    $a = ($rgba & 0x7F000000) >> 24;
                    $r = ($rgba >> 16) & 0xFF;
                    $g = ($rgba >> 8) & 0xFF;
                    $b = $rgba & 0xFF;

                    // GD alpha: 0=opaco, 127=transparente
                    if ($a >= 120 || ($r >= 245 && $g >= 245 && $b >= 245)) {
                        continue;
                    }

                    $outA = $a;
                    if ($r >= 230 && $g >= 230 && $b >= 230) {
                        $brightness = (int) (($r + $g + $b) / 3);
                        $outA = max($a, min(127, (int) ((255 - $brightness) / 2)));
                    }

                    $col = imagecolorallocatealpha($dst, $r, $g, $b, $outA);
                    imagesetpixel($dst, $x, $y, $col);
                }
            }

            ob_start();
            imagepng($dst);
            $png = ob_get_clean();
            imagedestroy($src);
            imagedestroy($dst);

            return is_string($png) && $png !== '' ? $png : null;
        } catch (\Throwable $e) {
            report($e);

            return null;
        }
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

    private function isDatePartOption(string $option): bool
    {
        return in_array(strtolower(trim($option)), ['day', 'month', 'month_name', 'year'], true);
    }

    /** Extrai dia/mês/ano (ou mês por extenso) de texto BR (dd/mm/aaaa) ou ISO (aaaa-mm-dd). */
    private function extractDatePart(string $text, string $part): string
    {
        $raw = trim($text);
        if ($raw === '') {
            return '';
        }

        $day = '';
        $month = '';
        $year = '';

        if (preg_match('/^(\d{1,2})[\/\-.](\d{1,2})[\/\-.](\d{2,4})$/', $raw, $m)) {
            $day = str_pad($m[1], 2, '0', STR_PAD_LEFT);
            $month = str_pad($m[2], 2, '0', STR_PAD_LEFT);
            $year = $m[3];
            if (strlen($year) === 2) {
                $year = '20'.$year;
            }
        } elseif (preg_match('/^(\d{4})-(\d{2})-(\d{2})/', $raw, $m)) {
            $year = $m[1];
            $month = $m[2];
            $day = $m[3];
        }

        if ($day === '' || $month === '' || $year === '') {
            return '';
        }

        return match ($part) {
            'day' => $day,
            'month' => $month,
            'month_name' => $this->monthNamePt((int) $month),
            default => $year,
        };
    }

    private function monthNamePt(int $month): string
    {
        $names = [
            1 => 'janeiro',
            2 => 'fevereiro',
            3 => 'marco',
            4 => 'abril',
            5 => 'maio',
            6 => 'junho',
            7 => 'julho',
            8 => 'agosto',
            9 => 'setembro',
            10 => 'outubro',
            11 => 'novembro',
            12 => 'dezembro',
        ];

        return $names[$month] ?? '';
    }

    /**
     * Escreve texto cabendo na caixa: reduz fonte em vez de quebrar no meio
     * (evita "12/11/2026" virar "1" + "2/11/2026" ou colar campos vizinhos).
     */
    private function drawFittedText(
        Fpdi $pdf,
        string $text,
        float $x,
        float $y,
        float $w,
        float $h,
        float $preferredSize,
        string $align,
        string $option = '',
    ): void {
        $safe = $this->safeText($text);
        if ($safe === '') {
            return;
        }

        $singleLine = $this->isDatePartOption($option)
            || ! preg_match('/\s/u', $text)
            || mb_strlen($text) <= 24;

        $fontSize = max(6.0, $preferredSize);
        $pdf->SetTextColor(15, 23, 42);

        if ($singleLine) {
            $pdf->SetFont('Helvetica', '', $fontSize);
            while ($fontSize > 6.0 && $pdf->GetStringWidth($safe) > max(1.0, $w - 0.6)) {
                $fontSize -= 0.5;
                $pdf->SetFont('Helvetica', '', $fontSize);
            }
            while ($fontSize > 6.0 && $fontSize > $h * 0.92) {
                $fontSize -= 0.5;
                $pdf->SetFont('Helvetica', '', $fontSize);
            }
            // Base da caixa = underline (igual ao preview do mapa).
            $cellH = max($fontSize * 1.05, min($h, $fontSize * 1.2));
            $pdf->SetXY($x, $y + max(0, $h - $cellH));
            $pdf->Cell($w, $cellH, $safe, 0, 0, $align);

            return;
        }

        $pdf->SetFont('Helvetica', '', $fontSize);
        $lineHeight = max(3.0, $fontSize * 0.42);
        for ($i = 0; $i < 20; $i++) {
            $lines = max(1, (int) ceil($pdf->GetStringWidth($safe) / max(1.0, $w - 0.6)));
            if ($lines * $lineHeight <= $h + 0.5 && $pdf->GetStringWidth($safe) / max(1, $lines) <= $w + 1) {
                break;
            }
            $fontSize = max(6.0, $fontSize - 0.5);
            $pdf->SetFont('Helvetica', '', $fontSize);
            $lineHeight = max(2.8, $fontSize * 0.42);
        }

        $blockH = $lineHeight * max(1, (int) ceil($pdf->GetStringWidth($safe) / max(1.0, $w - 0.6)));
        $pdf->SetXY($x, $y + max(0, $h - min($h, $blockH)));
        $pdf->MultiCell($w, $lineHeight, $safe, 0, $align);
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

    private function optionIsSelected(mixed $valueRow, string $option): bool
    {
        $target = mb_strtolower(trim($option));
        if ($target === '') {
            return false;
        }

        if (is_object($valueRow) && $valueRow->value_json !== null && is_array($valueRow->value_json)) {
            foreach ($valueRow->value_json as $item) {
                if (mb_strtolower(trim((string) $item)) === $target) {
                    return true;
                }
            }

            return false;
        }

        $text = mb_strtolower(trim($this->valueToText($valueRow)));
        if ($text === $target) {
            return true;
        }

        foreach (preg_split('/[;,]/', $text) ?: [] as $part) {
            if (mb_strtolower(trim((string) $part)) === $target) {
                return true;
            }
        }

        return false;
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
