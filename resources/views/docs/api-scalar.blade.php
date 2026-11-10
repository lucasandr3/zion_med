<!doctype html>
<html lang="pt-BR">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <title>{{ $config->get('ui.title') ?? (config('app.name') . ' — Documentação da API') }}</title>
</head>
<body>
<div id="app"></div>
<script src="{{ $config->renderer()->get('cdn', 'https://cdn.jsdelivr.net/npm/@scalar/api-reference') }}"></script>

<script>
    const CSRF_TOKEN_COOKIE_KEY = "XSRF-TOKEN";
    const CSRF_TOKEN_HEADER_KEY = "X-XSRF-TOKEN";
    const getCookieValue = (key) => {
        const cookie = document.cookie.split(';').find((cookie) => cookie.trim().startsWith(key));
        return cookie?.split("=")[1];
    };

    Scalar.createApiReference('#app', {
        content: @json($spec),
        ...@json($config->renderer()->all(except: ['cdn', 'credentials', 'view'])),
        onBeforeRequest: ({ requestBuilder }) => {
            const token = getCookieValue(CSRF_TOKEN_COOKIE_KEY);
            if (token) {
                requestBuilder.headers.set(CSRF_TOKEN_HEADER_KEY, decodeURIComponent(token));
            }
        },
        customFetch: (input, init) => {
            return window.fetch(input, { ...init, credentials: @json($config->renderer()->get('credentials', 'include')) });
        }
    })
</script>
</body>
</html>
