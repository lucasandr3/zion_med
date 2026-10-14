<?php

namespace App\Services\FormImport;

/**
 * Provedor PoC sem dependência externa.
 * Extrai perguntas a partir de texto (.txt) ou heurística de linhas de PDF textual;
 * para imagens/PDF escaneado, devolve estrutura base + warnings para revisão humana.
 */
class HeuristicExtractionProvider implements DocumentExtractionProvider
{
    public function extract(string $absolutePath, string $mime, ?string $originalName = null): array
    {
        $warnings = [];
        $text = $this->readText($absolutePath, $mime, $warnings);
        $fields = $this->parseFieldsFromText($text);

        if ($fields === []) {
            $warnings[] = [
                'code' => 'low_text_signal',
                'level' => 'warning',
                'message' => 'Pouco texto legível detectado. Revise e complete os campos manualmente (ideal para PDF escaneado/foto).',
            ];
            $fields = $this->fallbackAnamneseSkeleton($originalName);
        }

        $warnings[] = [
            'code' => 'human_review_required',
            'level' => 'warning',
            'message' => 'Nenhum resultado de IA é publicado automaticamente. Confira cada campo antes de salvar o modelo.',
        ];

        return [
            'fields' => $fields,
            'warnings' => $warnings,
            'provider' => 'heuristic',
            'model_version' => 'heuristic-v1',
        ];
    }

    /**
     * @param  list<array<string, mixed>>  $warnings
     */
    private function readText(string $path, string $mime, array &$warnings): string
    {
        if (! is_file($path)) {
            $warnings[] = ['code' => 'missing_file', 'level' => 'error', 'message' => 'Arquivo fonte não encontrado.'];

            return '';
        }

        $mime = strtolower($mime);
        if (str_contains($mime, 'text') || str_ends_with(strtolower($path), '.txt')) {
            return (string) file_get_contents($path);
        }

        if (str_contains($mime, 'pdf') || str_ends_with(strtolower($path), '.pdf')) {
            $raw = (string) file_get_contents($path);
            // Extração simples de strings entre streams (PDF textual); escaneados ficam vazios.
            preg_match_all('/\\(([^\\\\()]{3,200})\\)/', $raw, $m);
            $chunks = $m[1] ?? [];
            $text = trim(implode("\n", array_map(static function ($s) {
                return trim(stripcslashes($s));
            }, $chunks)));
            if ($text === '') {
                $warnings[] = [
                    'code' => 'pdf_no_text_layer',
                    'level' => 'warning',
                    'message' => 'PDF sem camada de texto. Use foto nítida ou configure um provedor multimodal.',
                ];
            }

            return $text;
        }

        $warnings[] = [
            'code' => 'image_requires_vision',
            'level' => 'warning',
            'message' => 'Arquivo de imagem recebido. Sem provedor multimodal, apenas a estrutura base foi sugerida.',
        ];

        return '';
    }

    /**
     * @return list<array<string, mixed>>
     */
    private function parseFieldsFromText(string $text): array
    {
        $lines = preg_split('/\\R+/', $text) ?: [];
        $fields = [];
        $order = 0;
        $prevKey = null;

        foreach ($lines as $line) {
            $line = trim(preg_replace('/\\s+/', ' ', $line) ?? '');
            if ($line === '' || mb_strlen($line) < 3 || mb_strlen($line) > 180) {
                continue;
            }
            if (! preg_match('/[A-Za-zÀ-ÿ]/u', $line)) {
                continue;
            }
            // Ignore PDF noise
            if (preg_match('/^(endstream|endobj|obj|BT|ET|\\d+ \\d+ obj)/i', $line)) {
                continue;
            }

            $order++;
            $isYesNo = (bool) preg_match('/\\?\\s*$|\\b(sim\\s*\\/?\\s*não|sim\\s*ou\\s*não)\\b/iu', $line);
            $hasQual = (bool) preg_match('/\\bqual\\b|\\bespecifique\\b|\\bdescreva\\b/iu', $line);
            $type = $isYesNo ? 'radio' : (mb_strlen($line) > 70 ? 'textarea' : 'text');
            $key = $this->slug($line, $order);

            $field = [
                'type' => $type,
                'label' => rtrim($line, ':'),
                'name_key' => $key,
                'required' => false,
                'options' => $type === 'radio' ? ['Sim', 'Não'] : [],
                'sort_order' => $order,
                'page' => 1,
                'source_text' => $line,
            ];

            if ($hasQual && $prevKey) {
                $field['visibility_rules'] = [
                    'logic' => 'and',
                    'conditions' => [
                        ['field' => $prevKey, 'op' => 'eq', 'value' => 'Sim'],
                    ],
                ];
            }

            $fields[] = $field;
            $prevKey = $key;

            if (count($fields) >= 40) {
                break;
            }
        }

        return $fields;
    }

    /**
     * @return list<array<string, mixed>>
     */
    private function fallbackAnamneseSkeleton(?string $originalName): array
    {
        $title = $originalName ? pathinfo($originalName, PATHINFO_FILENAME) : 'Ficha importada';

        return [
            [
                'type' => 'heading',
                'label' => $title,
                'name_key' => 'sec_titulo',
                'required' => false,
                'options' => [],
                'sort_order' => 1,
                'page' => 1,
            ],
            [
                'type' => 'text',
                'label' => 'Nome completo',
                'name_key' => 'nome_completo',
                'required' => true,
                'options' => [],
                'sort_order' => 2,
                'page' => 1,
            ],
            [
                'type' => 'date',
                'label' => 'Data de nascimento',
                'name_key' => 'data_nascimento',
                'required' => true,
                'options' => [],
                'sort_order' => 3,
                'page' => 1,
            ],
            [
                'type' => 'radio',
                'label' => 'Possui alguma condição de saúde relevante?',
                'name_key' => 'condicao_saude',
                'required' => true,
                'options' => ['Sim', 'Não'],
                'sort_order' => 4,
                'page' => 1,
            ],
            [
                'type' => 'textarea',
                'label' => 'Se sim, qual?',
                'name_key' => 'condicao_saude_qual',
                'required' => false,
                'options' => [],
                'sort_order' => 5,
                'page' => 1,
                'visibility_rules' => [
                    'logic' => 'and',
                    'conditions' => [
                        ['field' => 'condicao_saude', 'op' => 'eq', 'value' => 'Sim'],
                    ],
                ],
            ],
            [
                'type' => 'signature',
                'label' => 'Assinatura',
                'name_key' => 'assinatura',
                'required' => true,
                'options' => [],
                'sort_order' => 6,
                'page' => 1,
            ],
        ];
    }

    private function slug(string $label, int $order): string
    {
        $s = iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', $label) ?: $label;
        $s = strtolower((string) preg_replace('/[^a-z0-9]+/i', '_', $s));
        $s = trim($s, '_');
        if ($s === '' || preg_match('/^\\d/', $s)) {
            $s = 'campo_'.$order;
        }

        return mb_substr($s, 0, 60);
    }
}
