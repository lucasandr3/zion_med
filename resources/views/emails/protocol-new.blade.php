@extends('emails.layout-branded')

@section('content')
    <p style="margin:0 0 16px 0;">Olá!</p>
    <p style="margin:0 0 20px 0;">Foi recebido um <strong>novo protocolo</strong> pelo formulário público.</p>
    <table cellpadding="0" cellspacing="0" role="presentation" style="width:100%;margin:0 0 24px 0;font-size:15px;color:#374151;">
        <tr>
            <td style="padding:8px 0;border-bottom:1px solid #e5e7eb;"><span style="color:#6b7280;">Protocolo</span></td>
            <td style="padding:8px 0;border-bottom:1px solid #e5e7eb;text-align:right;font-weight:600;">{{ $protocolNumber }}</td>
        </tr>
        <tr>
            <td style="padding:8px 0;border-bottom:1px solid #e5e7eb;"><span style="color:#6b7280;">Formulário</span></td>
            <td style="padding:8px 0;border-bottom:1px solid #e5e7eb;text-align:right;">{{ $templateName }}</td>
        </tr>
        @if(!empty($submitterName))
        <tr>
            <td style="padding:8px 0;"><span style="color:#6b7280;">Remetente</span></td>
            <td style="padding:8px 0;text-align:right;">{{ $submitterName }}</td>
        </tr>
        @endif
    </table>
    @if(!empty($dashboardUrl))
    <p style="text-align:center;margin:28px 0;">
        <a href="{{ $dashboardUrl }}" style="display:inline-block;padding:14px 28px;background:{{ $brandPrimary }};color:#ffffff;text-decoration:none;border-radius:8px;font-weight:600;font-size:15px;">Abrir no sistema</a>
    </p>
    @endif
@endsection
