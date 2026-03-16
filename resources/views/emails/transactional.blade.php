<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $subject ?? 'Mensagem' }}</title>
    <style>
        body { font-family: sans-serif; line-height: 1.6; color: #333; max-width: 600px; margin: 0 auto; padding: 1rem; }
        .content { margin: 1.5rem 0; }
        .footer { font-size: 0.875rem; color: #666; margin-top: 2rem; }
    </style>
</head>
<body>
    <div class="content">
        {!! $body ?? '' !!}
    </div>
    <div class="footer">
        {{ config('app.name') }} — Este é um e-mail automático.
    </div>
</body>
</html>
