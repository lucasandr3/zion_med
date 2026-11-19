<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\FormTemplate;

/**
 * PDF-01C — scaffolding de detecção assistida por IA.
 * Flag desligada por padrão; sem provedor externo nesta etapa.
 */
class PrintLayoutAiDetectService
{
    public function isEnabled(): bool
    {
        return filter_var(env('PRINT_LAYOUT_AI_ENABLED', false), FILTER_VALIDATE_BOOLEAN);
    }

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
        if (! $this->isEnabled()) {
            return [
                'version' => 1,
                'mode' => 'ai_disabled',
                'page_count' => 0,
                'has_text' => false,
                'message' => 'Detecção assistida por IA está desligada nesta organização/ambiente. Use a detecção determinística ou o mapeamento manual.',
                'suggestions' => [],
            ];
        }

        if (! $template->hasSourceDocument()) {
            return [
                'version' => 1,
                'mode' => 'empty',
                'page_count' => 0,
                'has_text' => false,
                'message' => 'Anexe o PDF original antes de solicitar detecção assistida.',
                'suggestions' => [],
            ];
        }

        return [
            'version' => 1,
            'mode' => 'ai_unavailable',
            'page_count' => 0,
            'has_text' => false,
            'message' => 'Detecção assistida habilitada, mas nenhum provedor de IA está configurado. Continue com revisão manual.',
            'suggestions' => [],
        ];
    }
}
