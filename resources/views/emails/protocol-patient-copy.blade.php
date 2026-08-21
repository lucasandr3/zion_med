@extends('emails.layout-branded')

@section('content')
    <p style="margin:0 0 16px 0;">Olá!</p>
    <p style="margin:0 0 20px 0;">
        Segue o acesso à <strong>sua cópia</strong> do documento registrado
        @if(!empty($clinicName)) em <strong>{{ $clinicName }}</strong>@endif.
    </p>
    <table cellpadding="0" cellspacing="0" role="presentation" style="width:100%;margin:0 0 24px 0;font-size:15px;color:#374151;">
        <tr>
            <td style="padding:8px 0;border-bottom:1px solid #e5e7eb;"><span style="color:#6b7280;">Protocolo</span></td>
            <td style="padding:8px 0;border-bottom:1px solid #e5e7eb;text-align:right;font-weight:600;">{{ $protocolNumber }}</td>
        </tr>
        @if(!empty($templateName))
        <tr>
            <td style="padding:8px 0;"><span style="color:#6b7280;">Documento</span></td>
            <td style="padding:8px 0;text-align:right;">{{ $templateName }}</td>
        </tr>
        @endif
    </table>
    @if(!empty($downloadUrl))
    <p style="text-align:center;margin:28px 0;">
        <a href="{{ $downloadUrl }}" style="display:inline-block;padding:14px 28px;background:{{ $brandPrimary }};color:#ffffff;text-decoration:none;border-radius:8px;font-weight:600;font-size:15px;">Baixar PDF</a>
    </p>
    <p style="margin:0;font-size:13px;color:#6b7280;text-align:center;">
        O link expira em {{ $expiresHours ?? 72 }} horas por segurança.
    </p>
    @endif
@endsection
