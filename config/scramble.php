<?php

use Dedoc\Scramble\Http\Middleware\RestrictedDocsAccess;

return [
    /*
     * Your API path. By default, all routes starting with this path will be added to the docs.
     * If you need to change this behavior, you can add your custom routes resolver using `Scramble::routes()`.
     */
    'api_path' => 'api',

    /*
     * Your API domain. By default, app domain is used. This is also a part of the default API routes
     * matcher, so when implementing your own, make sure you use this config if needed.
     */
    'api_domain' => null,

    /*
     * The path where your OpenAPI specification will be exported.
     */
    'export_path' => 'api.json',

    'info' => [
        /*
         * API version.
         */
        'version' => env('API_VERSION', '1.0.0'),

        /*
         * Description rendered on the home page of the API documentation (`/docs/api`).
         */
        'description' => <<<'MD'
API REST do GestGo para **sistemas externos** (ERP, prontuário, automações).

**Escopo documentado:** protocolos, templates (leitura/estrutura), pessoas, links públicos e webhooks.

**Autenticação:** token gerado em **Empresa → Integrações**. Header `Authorization: Bearer SEU_TOKEN`.

Rotas do app (login, painel, cobrança, plataforma etc.) existem na API, mas **não** fazem parte deste contrato de integração.
MD,
    ],

    /*
    | Texto adicional na documentação OpenAPI (Scramble) — webhooks de saída para o seu sistema.
    */
    'webhooks_documentation_appendix' => <<<'MD'
### Webhooks de protocolo (saída)

O Gestgo envia **HTTP POST** para a URL cadastrada em **Empresa → Integrações → Webhooks**, quando o evento está habilitado no registro do webhook.

**Método e corpo:** `POST` com corpo JSON (`Content-Type: application/json`).

**Cabeçalhos enviados:**
- `X-Webhook-Event`: nome do evento (igual ao campo `event` no JSON).
- `User-Agent`: `Gestgo-Webhooks/1.0`
- `X-Webhook-Signature`: presente quando o webhook tem **segredo** configurado. Formato `sha256=` + HMAC-SHA256 do **corpo bruto** (string JSON exatamente como enviada), usando o segredo como chave. Valide no receptor recomputando o HMAC sobre o raw body.

**Eventos disponíveis:**
| Evento | Quando dispara |
|--------|----------------|
| `submission.created` | Novo protocolo criado (formulário público ou interno). |
| `submission.signed` | Assinatura registrada no fluxo (quando aplicável ao template). |
| `submission.approved` | Protocolo aprovado na revisão. |
| `submission.rejected` | Protocolo reprovado na revisão. |

**Corpo JSON (campos principais):** o payload inclui sempre a chave `event` (redundante com o header) mais os dados do protocolo, por exemplo:
- `protocol_id` (int)
- `protocol_number` (string)
- `template_id` (int)
- `template_name` (string|null)
- `status` (string, ex.: `pending`, `approved`, `rejected`)
- `submitter_name`, `submitter_email` (string|null)
- `submitted_at`, `approved_at` (ISO 8601|null)
- `organization_id`, `clinic_id` (int — compat)
- `timestamp` (ISO 8601, momento do disparo)

Configure URLs e lista de eventos na API autenticada de integrações (`GET/POST` webhooks da clínica).
MD
    ,

    /*
     * Título da página de documentação (usado pela view do renderer).
     * Não incluir `ui.logo` aqui: no Scramble atual, a presença de `ui.logo`
     * força o renderer Stoplight Elements.
     */
    'ui' => [
        'title' => 'Documentação da API',
    ],

    /*
     * UI da documentação: Scalar (MIT). O OpenAPI continua gerado pelo Scramble.
     */
    'renderer' => 'scalar',

    'renderers' => [
        'elements' => [
            'view' => 'scramble::docs',
            'theme' => 'light',
            'hideTryIt' => false,
            'hideSchemas' => false,
            'logo' => '',
            'tryItCredentialsPolicy' => 'include',
            'layout' => 'responsive',
            'router' => 'hash',
        ],
        'scalar' => [
            'view' => 'docs.api-scalar',
            'cdn' => 'https://cdn.jsdelivr.net/npm/@scalar/api-reference@1.77.0/dist/browser/standalone.js',
            'theme' => 'default',
            'proxyUrl' => 'https://proxy.scalar.com',
            'darkMode' => false,
            'showDeveloperTools' => 'never',
            'agent' => ['disabled' => true],
            'credentials' => 'include',
            'modelsSectionLabel' => 'Modelos',
            'localization' => [
                'locale' => 'pt',
                'direction' => 'ltr',
            ],
        ],
    ],

    /*
     * The list of servers of the API. By default, when `null`, server URL will be created from
     * `scramble.api_path` and `scramble.api_domain` config variables. When providing an array, you
     * will need to specify the local server URL manually (if needed).
     *
     * Example of non-default config (final URLs are generated using Laravel `url` helper):
     *
     * ```php
     * 'servers' => [
     *     'Live' => 'api',
     *     'Prod' => 'https://scramble.dedoc.co/api',
     * ],
     * ```
     */
    'servers' => null,

    /**
     * Determines how Scramble stores the descriptions of enum cases.
     * Available options:
     * - 'description' – Case descriptions are stored as the enum schema's description using table formatting.
     * - 'extension' – Case descriptions are stored in the `x-enumDescriptions` enum schema extension.
     *
     *    @see https://redocly.com/docs-legacy/api-reference-docs/specification-extensions/x-enum-descriptions
     * - false - Case descriptions are ignored.
     */
    'enum_cases_description_strategy' => 'description',

    /**
     * Determines how Scramble stores the names of enum cases.
     * Available options:
     * - 'names' – Case names are stored in the `x-enumNames` enum schema extension.
     * - 'varnames' - Case names are stored in the `x-enum-varnames` enum schema extension.
     * - false - Case names are not stored.
     */
    'enum_cases_names_strategy' => false,

    /**
     * When Scramble encounters deep objects in query parameters, it flattens the parameters so the generated
     * OpenAPI document correctly describes the API. Flattening deep query parameters is relevant until
     * OpenAPI 3.2 is released and query string structure can be described properly.
     *
     * For example, this nested validation rule describes the object with `bar` property:
     * `['foo.bar' => ['required', 'int']]`.
     *
     * When `flatten_deep_query_parameters` is `true`, Scramble will document the parameter like so:
     * `{"name":"foo[bar]", "schema":{"type":"int"}, "required":true}`.
     *
     * When `flatten_deep_query_parameters` is `false`, Scramble will document the parameter like so:
     *  `{"name":"foo", "schema": {"type":"object", "properties":{"bar":{"type": "int"}}, "required": ["bar"]}, "required":true}`.
     */
    'flatten_deep_query_parameters' => true,

    'middleware' => env('SCRAMBLE_PUBLIC', false)
        ? ['web']
        : [
            'web',
            RestrictedDocsAccess::class,
        ],

    'extensions' => [],
];
