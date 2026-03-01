<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="utf-8">
    <title>Protocolo {{ $submission->protocol_number ?? $submission->id }}</title>
    <link rel="icon" type="image/png" href="{{ asset('favicon-96x96.png') }}" sizes="96x96" />
    <link rel="icon" type="image/svg+xml" href="{{ asset('favicon.svg') }}" />
    <link rel="shortcut icon" href="{{ asset('favicon.ico') }}" />
    <link rel="apple-touch-icon" sizes="180x180" href="{{ asset('apple-touch-icon.png') }}" />
    <meta name="apple-mobile-web-app-title" content="ZionMed" />
    <link rel="manifest" href="{{ asset('site.webmanifest') }}" />
    <style>
        body { font-family: DejaVu Sans, sans-serif; font-size: 11px; color: #333; }
        .header { border-bottom: 2px solid #1e40af; padding-bottom: 12px; margin-bottom: 20px; }
        .header img { max-height: 60px; }
        .header h1 { margin: 0; font-size: 18px; color: #1e40af; }
        .header-address { margin: 4px 0 0 0; font-size: 10px; color: #64748b; }
        table { width: 100%; border-collapse: collapse; margin-bottom: 16px; }
        th { text-align: left; padding: 8px 6px; background: #f1f5f9; border: 1px solid #e2e8f0; }
        td { padding: 8px 6px; border: 1px solid #e2e8f0; }
        .signature-block { margin-top: 24px; padding-top: 16px; border-top: 1px solid #e2e8f0; }
        .signature-block img { max-width: 280px; max-height: 80px; }
        .footer { margin-top: 24px; font-size: 9px; color: #64748b; }
        .attachments-list { margin-top: 16px; }
        .attachments-list li { margin: 4px 0; }
    </style>
</head>
<body>
    <div class="header">
        @if($logoPath)
            <img src="{{ $logoPath }}" alt="Logo">
        @endif
        <h1>{{ $clinic->name }}</h1>
        @if($clinic->address)
            <p class="header-address">{{ $clinic->address }}</p>
        @endif
        <p>{{ $submission->template->name }}</p>
        <p>Protocolo: {{ $submission->protocol_number ?? $submission->id }} | Data: {{ $submission->submitted_at?->format('d/m/Y H:i') ?? $submission->created_at->format('d/m/Y H:i') }}</p>
    </div>

    <table>
        <thead>
            <tr>
                <th style="width:35%">Campo</th>
                <th>Resposta</th>
            </tr>
        </thead>
        <tbody>
            @foreach($fields as $field)
                @if($field->type !== 'signature' && $field->type !== 'file')
                    @php $val = $valuesKeyed->get($field->name_key); @endphp
                    <tr>
                        <td><strong>{{ $field->label }}</strong></td>
                        <td>{{ $val ? ($val->value_json ?? $val->value_text) : '—' }}</td>
                    </tr>
                @endif
            @endforeach
        </tbody>
    </table>

    @if($submission->signatures->isNotEmpty())
        <div class="signature-block">
            <strong>Assinatura(s)</strong>
            <p class="signature-notice" style="font-size:9px;color:#64748b;margin:4px 0 8px 0;">A assinatura digital tem validade jurídica nos termos da legislação vigente.</p>
            @foreach($submission->signatures as $sig)
                <div style="margin-top:8px">
                    @if($sig->image_path && \Illuminate\Support\Facades\Storage::disk('public')->exists($sig->image_path))
                        <img src="{{ \Illuminate\Support\Facades\Storage::disk('public')->path($sig->image_path) }}" alt="Assinatura">
                    @endif
                </div>
            @endforeach
        </div>
    @endif

    @if($submission->attachments->isNotEmpty())
        <div class="attachments-list">
            <strong>Anexos:</strong>
            <ul>
                @foreach($submission->attachments as $att)
                    <li>{{ $att->original_name }} ({{ number_format($att->size / 1024, 1) }} KB)</li>
                @endforeach
            </ul>
        </div>
    @endif

    <div class="footer">
        Documento gerado em {{ now()->format('d/m/Y H:i') }} — Emitido por {{ $clinic->name }} — Zion Med
    </div>
</body>
</html>
