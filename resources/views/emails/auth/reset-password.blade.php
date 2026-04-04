@extends('emails.layout-branded')

@section('content')
    <p style="margin:0 0 16px 0;">Olá@if(!empty($userName)), {{ $userName }}@endif!</p>
    <p style="margin:0 0 20px 0;">Recebemos uma solicitação para <strong>redefinir a senha</strong> da sua conta. Use o botão abaixo para escolher uma nova senha.</p>
    <p style="text-align:center;margin:28px 0;">
        <a href="{{ $actionUrl }}" style="display:inline-block;padding:14px 28px;background:{{ $brandPrimary }};color:#ffffff;text-decoration:none;border-radius:8px;font-weight:600;font-size:15px;">Redefinir senha</a>
    </p>
    <p style="margin:0 0 12px 0;font-size:14px;color:#6b7280;">Link alternativo (copiar e colar):</p>
    <p style="margin:0;word-break:break-all;font-size:13px;color:#4b5563;">{{ $actionUrl }}</p>
    <p style="margin:24px 0 0 0;font-size:14px;color:#6b7280;">Este link expira em <strong>{{ (int) $expireMinutes }}</strong> minutos. Se você não pediu a redefinição, ignore este e-mail; sua senha permanece a mesma.</p>
@endsection
