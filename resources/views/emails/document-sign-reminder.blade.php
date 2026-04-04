@extends('emails.layout-branded')

@section('content')
    <p style="margin:0 0 16px 0;">Olá@if(!empty($recipientName)), {{ $recipientName }}@endif!</p>
    <p style="margin:0 0 20px 0;">Lembrete: o documento <strong>{{ $templateName }}</strong> enviado por <strong>{{ $organizationName }}</strong> ainda está <strong>pendente de assinatura</strong>.</p>
    <p style="text-align:center;margin:28px 0;">
        <a href="{{ $signUrl }}" style="display:inline-block;padding:14px 28px;background:{{ $brandPrimary }};color:#ffffff;text-decoration:none;border-radius:8px;font-weight:600;font-size:15px;">Concluir assinatura</a>
    </p>
    <p style="margin:0 0 12px 0;font-size:14px;color:#6b7280;">Link direto:</p>
    <p style="margin:0;word-break:break-all;font-size:13px;color:#4b5563;">{{ $signUrl }}</p>
@endsection
