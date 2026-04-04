@extends('emails.layout-branded')

@section('content')
    <p style="margin:0 0 16px 0;">Olá!</p>
    <p style="margin:0 0 20px 0;">Use o código abaixo para continuar. Ele é válido por <strong>{{ (int) $validMinutes }}</strong> minutos.</p>
    <p style="text-align:center;margin:32px 0;font-size:32px;font-weight:700;letter-spacing:0.25em;color:{{ $brandPrimary }};">{{ $code }}</p>
    <p style="margin:0;font-size:14px;color:#6b7280;">Por segurança, não compartilhe este código com ninguém. Se você não solicitou este código, ignore este e-mail.</p>
@endsection
