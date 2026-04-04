@extends('emails.layout-branded')

@section('content')
    <p style="margin:0 0 16px 0;">Olá@if(!empty($userName)), {{ $userName }}@endif!</p>
    <p style="margin:0 0 20px 0;">Obrigado por se cadastrar. Clique no botão abaixo para <strong>confirmar seu endereço de e-mail</strong> e concluir a ativação da conta.</p>
    <p style="text-align:center;margin:28px 0;">
        <a href="{{ $actionUrl }}" style="display:inline-block;padding:14px 28px;background:{{ $brandPrimary }};color:#ffffff;text-decoration:none;border-radius:8px;font-weight:600;font-size:15px;">Confirmar e-mail</a>
    </p>
    <p style="margin:0 0 12px 0;font-size:14px;color:#6b7280;">Se o botão não funcionar, copie e cole este link no navegador:</p>
    <p style="margin:0;word-break:break-all;font-size:13px;color:#4b5563;">{{ $actionUrl }}</p>
    <p style="margin:24px 0 0 0;font-size:14px;color:#6b7280;">Se você não criou uma conta, pode ignorar esta mensagem com segurança.</p>
@endsection
