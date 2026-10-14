<?php

return [
    'disk' => env('FORM_IMPORT_DISK', 'local'),
    /** heuristic|openai — openai usa OPENAI_API_KEY; sem chave cai na heurística. */
    'provider' => env('FORM_IMPORT_PROVIDER', 'openai'),
    'openai_api_key' => env('OPENAI_API_KEY'),
    'openai_base_url' => env('OPENAI_BASE_URL', 'https://api.openai.com/v1'),
    'openai_model' => env('FORM_IMPORT_OPENAI_MODEL', 'gpt-4o-mini'),
    'openai_timeout' => (int) env('FORM_IMPORT_OPENAI_TIMEOUT', 90),
    'openai_max_bytes' => (int) env('FORM_IMPORT_OPENAI_MAX_BYTES', 15728640),
    'openai_image_detail' => env('FORM_IMPORT_OPENAI_IMAGE_DETAIL', 'high'),
    'max_upload_kb' => (int) env('FORM_IMPORT_MAX_UPLOAD_KB', 10240),
    'retention_days' => (int) env('FORM_IMPORT_RETENTION_DAYS', 30),
];
