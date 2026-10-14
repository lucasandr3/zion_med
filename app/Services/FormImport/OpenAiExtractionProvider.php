<?php

namespace App\Services\FormImport;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * Extrai estrutura de ficha via OpenAI (visão multimodal + JSON).
 * Preferir fichas em branco; nunca logar conteúdo clínico completo.
 */
class OpenAiExtractionProvider implements DocumentExtractionProvider
{
    public function __construct(
        private HeuristicExtractionProvider $heuristic,
    ) {}

    public function extract(string $absolutePath, string $mime, ?string $originalName = null): array
    {
        $apiKey = trim((string) config('form_import.openai_api_key', ''));
        if ($apiKey === '') {
            $result = $this->heuristic->extract($absolutePath, $mime, $originalName);
            $result['warnings'][] = [
                'code' => 'openai_key_missing',
                'level' => 'warning',
                'message' => 'OPENAI_API_KEY não configurada. Usando extração heurística local.',
            ];
            $result['provider'] = 'heuristic_fallback';

            return $result;
        }

        if (! is_file($absolutePath)) {
            throw new \RuntimeException('Arquivo fonte não encontrado para envio à OpenAI.');
        }

        $bytes = filesize($absolutePath) ?: 0;
        $maxBytes = (int) config('form_import.openai_max_bytes', 15 * 1024 * 1024);
        if ($bytes <= 0 || $bytes > $maxBytes) {
            throw new \RuntimeException('Arquivo inválido ou acima do limite para a OpenAI (máx. '.round($maxBytes / 1024 / 1024, 1).' MB).');
        }

        $mime = strtolower($mime ?: 'application/octet-stream');
        $model = (string) config('form_import.openai_model', 'gpt-4o-mini');
        $base64 = base64_encode((string) file_get_contents($absolutePath));
        $filename = $originalName ?: basename($absolutePath);

        $userContent = [
            [
                'type' => 'text',
                'text' => $this->userPrompt($filename),
            ],
            $this->buildFilePart($mime, $filename, $base64),
        ];

        try {
            $response = Http::withToken($apiKey)
                ->timeout((int) config('form_import.openai_timeout', 90))
                ->acceptJson()
                ->post(rtrim((string) config('form_import.openai_base_url', 'https://api.openai.com/v1'), '/').'/chat/completions', [
                    'model' => $model,
                    'temperature' => 0.1,
                    'response_format' => ['type' => 'json_object'],
                    'messages' => [
                        ['role' => 'system', 'content' => $this->systemPrompt()],
                        ['role' => 'user', 'content' => $userContent],
                    ],
                ]);
        } catch (\Throwable $e) {
            Log::warning('OpenAI form import request failed', ['error' => $e->getMessage()]);

            return $this->fallback($absolutePath, $mime, $originalName, 'openai_request_failed', $e->getMessage());
        }

        if (! $response->successful()) {
            $status = $response->status();
            $body = mb_substr((string) $response->body(), 0, 400);
            Log::warning('OpenAI form import HTTP error', ['status' => $status]);

            // PDF pode falhar em alguns modelos — tenta texto + chat
            if (str_contains($mime, 'pdf')) {
                return $this->extractFromPdfTextViaOpenAi($apiKey, $model, $absolutePath, $mime, $originalName, $status.' '.$body);
            }

            return $this->fallback($absolutePath, $mime, $originalName, 'openai_http_'.$status, $body);
        }

        $content = (string) data_get($response->json(), 'choices.0.message.content', '');
        $parsed = $this->parseJsonContent($content);
        if ($parsed === null) {
            return $this->fallback($absolutePath, $mime, $originalName, 'openai_invalid_json', 'Resposta da OpenAI não era JSON válido.');
        }

        $fields = is_array($parsed['fields'] ?? null) ? $parsed['fields'] : [];
        $warnings = is_array($parsed['warnings'] ?? null) ? $parsed['warnings'] : [];
        $warnings = array_map(static function ($w) {
            if (is_string($w)) {
                return ['code' => 'openai_warning', 'level' => 'warning', 'message' => $w];
            }
            if (is_array($w)) {
                return [
                    'code' => (string) ($w['code'] ?? 'openai_warning'),
                    'level' => (string) ($w['level'] ?? 'warning'),
                    'message' => (string) ($w['message'] ?? json_encode($w)),
                ];
            }

            return ['code' => 'openai_warning', 'level' => 'warning', 'message' => 'Aviso não tipado da OpenAI.'];
        }, $warnings);

        $warnings[] = [
            'code' => 'human_review_required',
            'level' => 'warning',
            'message' => 'Revise cada campo antes de salvar. A OpenAI não publica o modelo automaticamente.',
        ];

        if ($fields === []) {
            return $this->fallback($absolutePath, $mime, $originalName, 'openai_empty_fields', 'A OpenAI não retornou campos.');
        }

        return [
            'fields' => $fields,
            'warnings' => $warnings,
            'provider' => 'openai',
            'model_version' => $model,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function buildFilePart(string $mime, string $filename, string $base64): array
    {
        if (str_starts_with($mime, 'image/') || preg_match('/\.(jpe?g|png|webp|gif)$/i', $filename)) {
            $imgMime = str_starts_with($mime, 'image/') ? $mime : 'image/jpeg';

            return [
                'type' => 'image_url',
                'image_url' => [
                    'url' => 'data:'.$imgMime.';base64,'.$base64,
                    'detail' => (string) config('form_import.openai_image_detail', 'high'),
                ],
            ];
        }

        if (str_contains($mime, 'pdf') || str_ends_with(strtolower($filename), '.pdf')) {
            return [
                'type' => 'file',
                'file' => [
                    'filename' => $filename,
                    'file_data' => 'data:application/pdf;base64,'.$base64,
                ],
            ];
        }

        // TXT / outros: manda como texto embutido no prompt (parte texto já existe); anexa bloco curto
        return [
            'type' => 'text',
            'text' => "Conteúdo do arquivo ({$filename}):\n".mb_substr(base64_decode($base64, true) ?: '', 0, 12000),
        ];
    }

    /**
     * @return array{fields: list<array<string, mixed>>, warnings: list<array<string, mixed>>, provider: string, model_version: string}
     */
    private function extractFromPdfTextViaOpenAi(
        string $apiKey,
        string $model,
        string $absolutePath,
        string $mime,
        ?string $originalName,
        string $priorError,
    ): array {
        $heuristic = $this->heuristic->extract($absolutePath, $mime, $originalName);
        $sourceLines = [];
        foreach ($heuristic['fields'] ?? [] as $f) {
            if (! empty($f['source_text'])) {
                $sourceLines[] = (string) $f['source_text'];
            } elseif (! empty($f['label'])) {
                $sourceLines[] = (string) $f['label'];
            }
        }
        $blob = trim(implode("\n", $sourceLines));
        if ($blob === '' || strlen($blob) < 40) {
            return $this->fallback($absolutePath, $mime, $originalName, 'openai_pdf_unsupported', $priorError);
        }

        try {
            $response = Http::withToken($apiKey)
                ->timeout((int) config('form_import.openai_timeout', 90))
                ->acceptJson()
                ->post(rtrim((string) config('form_import.openai_base_url', 'https://api.openai.com/v1'), '/').'/chat/completions', [
                    'model' => $model,
                    'temperature' => 0.1,
                    'response_format' => ['type' => 'json_object'],
                    'messages' => [
                        ['role' => 'system', 'content' => $this->systemPrompt()],
                        ['role' => 'user', 'content' => $this->userPrompt($originalName ?? 'ficha.pdf')."\n\nTexto extraído do PDF:\n".$blob],
                    ],
                ]);
        } catch (\Throwable $e) {
            return $this->fallback($absolutePath, $mime, $originalName, 'openai_pdf_text_failed', $e->getMessage());
        }

        if (! $response->successful()) {
            return $this->fallback($absolutePath, $mime, $originalName, 'openai_pdf_text_http', mb_substr((string) $response->body(), 0, 300));
        }

        $parsed = $this->parseJsonContent((string) data_get($response->json(), 'choices.0.message.content', ''));
        if ($parsed === null || empty($parsed['fields']) || ! is_array($parsed['fields'])) {
            return $this->fallback($absolutePath, $mime, $originalName, 'openai_pdf_text_empty', $priorError);
        }

        $warnings = is_array($parsed['warnings'] ?? null) ? $parsed['warnings'] : [];
        $warnings[] = [
            'code' => 'openai_pdf_text_mode',
            'level' => 'warning',
            'message' => 'PDF processado via texto (fallback). Confira se nenhum campo visual foi omitido.',
        ];
        $warnings[] = [
            'code' => 'human_review_required',
            'level' => 'warning',
            'message' => 'Revise cada campo antes de salvar.',
        ];

        return [
            'fields' => $parsed['fields'],
            'warnings' => array_map(static function ($w) {
                return is_string($w)
                    ? ['code' => 'openai_warning', 'level' => 'warning', 'message' => $w]
                    : [
                        'code' => (string) ($w['code'] ?? 'openai_warning'),
                        'level' => (string) ($w['level'] ?? 'warning'),
                        'message' => (string) ($w['message'] ?? ''),
                    ];
            }, $warnings),
            'provider' => 'openai',
            'model_version' => $model,
        ];
    }

    /**
     * @return array{fields: list<array<string, mixed>>, warnings: list<array<string, mixed>>, provider: string, model_version: string}
     */
    private function fallback(string $path, string $mime, ?string $name, string $code, string $detail): array
    {
        $result = $this->heuristic->extract($path, $mime, $name);
        $result['warnings'][] = [
            'code' => $code,
            'level' => 'warning',
            'message' => 'OpenAI indisponível ou falhou ('.mb_substr($detail, 0, 180).'). Usando heurística local para revisão.',
        ];
        $result['provider'] = 'heuristic_fallback';

        return $result;
    }

    /**
     * @return array<string, mixed>|null
     */
    private function parseJsonContent(string $content): ?array
    {
        $content = trim($content);
        if ($content === '') {
            return null;
        }
        // Remove fences se o modelo ignorar response_format
        if (str_starts_with($content, '```')) {
            $content = preg_replace('/^```(?:json)?\s*/i', '', $content) ?? $content;
            $content = preg_replace('/\s*```$/', '', $content) ?? $content;
        }
        $decoded = json_decode($content, true);

        return is_array($decoded) ? $decoded : null;
    }

    private function systemPrompt(): string
    {
        return <<<'PROMPT'
Você extrai a ESTRUTURA de formulários clínicos/documentais em branco (anamnese, consentimento, ficha).
NÃO invente respostas de paciente. NÃO copie dados pessoais preenchidos — ignore valores manuscritos/digitados.
Devolva APENAS JSON válido no formato:
{
  "fields": [
    {
      "type": "text|textarea|number|date|radio|checkbox|select|signature|heading|notice|section_break",
      "label": "pergunta ou título",
      "name_key": "snake_case_unico",
      "required": false,
      "options": ["opção A", "opção B"],
      "sort_order": 1,
      "page": 1,
      "source_text": "trecho original curto",
      "visibility_rules": null
    }
  ],
  "warnings": [{"code":"string","level":"warning","message":"string"}]
}
Regras:
- Tipos devem ser só os listados.
- Perguntas Sim/Não => radio com options ["Sim","Não"].
- "Sim/Não; Qual?" => radio + textarea/text com visibility_rules quando Sim:
  {"logic":"and","conditions":[{"field":"name_key_do_radio","op":"eq","value":"Sim"}]}
- Seções => heading ou section_break.
- Assinatura => signature.
- options vazio [] quando não aplicável.
- Máximo 60 campos; priorize campos reais do documento.
- Se trecho ilegível, inclua warning e não chute o rótulo.
PROMPT;
    }

    private function userPrompt(string $filename): string
    {
        return 'Analise o documento "'.$filename.'" (ficha preferencialmente em branco) e extraia a estrutura de campos conforme o schema JSON combinado. Responda só JSON.';
    }
}
