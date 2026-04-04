@php
    $brandName = $brandName ?? config('app.name');
    $brandLogoUrl = $brandLogoUrl ?? null;
    $brandPrimary = $brandPrimary ?? '#1e40af';
    $brandSupportEmail = $brandSupportEmail ?? null;
@endphp
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $emailTitle ?? $brandName }}</title>
</head>
<body style="margin:0;padding:0;background:#f4f4f5;font-family:-apple-system,BlinkMacSystemFont,'Segoe UI',Roboto,'Helvetica Neue',Arial,sans-serif;">
    <table width="100%" cellpadding="0" cellspacing="0" role="presentation" style="background:#f4f4f5;padding:24px 12px;">
        <tr>
            <td align="center">
                <table width="100%" cellpadding="0" cellspacing="0" role="presentation" style="max-width:560px;background:#ffffff;border-radius:12px;overflow:hidden;box-shadow:0 1px 3px rgba(0,0,0,0.08);">
                    <tr>
                        <td style="padding:28px 28px 12px 28px;text-align:center;border-bottom:1px solid #e5e7eb;">
                            @if(!empty($brandLogoUrl))
                                <img src="{{ $brandLogoUrl }}" alt="{{ $brandName }}" width="160" style="max-height:52px;width:auto;height:auto;display:inline-block;">
                            @else
                                <div style="font-size:22px;font-weight:700;color:{{ $brandPrimary }};letter-spacing:-0.02em;">{{ $brandName }}</div>
                            @endif
                        </td>
                    </tr>
                    <tr>
                        <td style="padding:24px 28px 8px 28px;color:#374151;font-size:16px;line-height:1.65;">
                            @yield('content')
                        </td>
                    </tr>
                    <tr>
                        <td style="padding:20px 28px 24px 28px;background:#f9fafb;border-top:1px solid #e5e7eb;font-size:12px;color:#6b7280;line-height:1.5;text-align:center;">
                            <p style="margin:0 0 8px 0;">{{ $brandName }} — este é um e-mail automático; não é necessário responder.</p>
                            @if(!empty($brandSupportEmail))
                                <p style="margin:0;">Suporte: <a href="mailto:{{ $brandSupportEmail }}" style="color:{{ $brandPrimary }};">{{ $brandSupportEmail }}</a></p>
                            @endif
                        </td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>
</body>
</html>
