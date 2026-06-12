@php
    $brandName = $brandName ?? config('app.name');
    $brandLogoUrl = $brandLogoUrl ?? null;
    $brandPrimary = $brandPrimary ?? '#1a3fae';
    $brandSupportEmail = $brandSupportEmail ?? null;
    $signaturePhotoUrl = $signaturePhotoUrl ?? null;
    $senderName = $senderName ?? ($brandName ?? 'Equipe');
    $senderRole = $senderRole ?? null;
    $senderEmail = $senderEmail ?? $brandSupportEmail;
    $whatsappNumber = $whatsappNumber ?? null;
    $actionLink = $actionLink ?? null;
    $actionText = $actionText ?? null;
    $manualEmail = $manualEmail ?? false;
    $year = $year ?? now()->year;
    $whatsappDigits = is_string($whatsappNumber) ? preg_replace('/\D+/', '', $whatsappNumber) : null;
@endphp
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $emailTitle ?? $brandName }}</title>
</head>
<body style="margin:0; padding:0; background-color:#f4f4f4; font-family:Arial, Helvetica, sans-serif;">

  <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="background-color:#f4f4f4; padding:30px 0;">
    <tr>
      <td align="center">

        <table role="presentation" width="600" cellpadding="0" cellspacing="0" style="background-color:#ffffff; border-radius:8px; overflow:hidden; box-shadow:0 1px 3px rgba(0,0,0,0.08); max-width:600px; width:100%;">

          <tr>
            <td align="center" style="padding:30px 20px 20px 20px; border-bottom:1px solid #eeeeee;">
              @if(!empty($brandLogoUrl))
                <img src="{{ $brandLogoUrl }}" alt="{{ $brandName }}" style="max-height:52px; width:auto; height:auto; display:block;">
              @else
                <h1 style="margin:0; color:{{ $brandPrimary }}; font-size:26px; font-weight:bold;">{{ $brandName }}</h1>
              @endif
            </td>
          </tr>

          <tr>
            <td style="padding:30px 30px 20px 30px; color:#333333; font-size:15px; line-height:1.6;">
              @yield('content')

              @if(!empty($actionLink) && !empty($actionText))
                <table role="presentation" cellpadding="0" cellspacing="0" style="margin:25px 0;">
                  <tr>
                    <td align="center" bgcolor="{{ $brandPrimary }}" style="border-radius:5px;">
                      <a href="{{ $actionLink }}" target="_blank" style="display:inline-block; padding:12px 28px; color:#ffffff; text-decoration:none; font-weight:bold; font-size:14px; border-radius:5px;">
                        {{ $actionText }}
                      </a>
                    </td>
                  </tr>
                </table>
              @endif

              @if(empty($manualEmail))
                @hasSection('after_content')
                  @yield('after_content')
                @else
                  <p style="margin:16px 0;">Qualquer dúvida, estamos à disposição.</p>
                @endif
              @endif

              @if(!empty($whatsappDigits))
                <table role="presentation" cellpadding="0" cellspacing="0" style="margin:15px 0;">
                  <tr>
                    <td align="center" bgcolor="#25D366" style="border-radius:5px;">
                      <a href="https://wa.me/{{ $whatsappDigits }}" target="_blank" style="display:inline-block; padding:12px 28px; color:#ffffff; text-decoration:none; font-weight:bold; font-size:14px; border-radius:5px;">
                        Fale com a gente no WhatsApp
                      </a>
                    </td>
                  </tr>
                </table>
              @endif

              <p style="margin-bottom:0;">Atenciosamente,</p>

              <table role="presentation" cellpadding="0" cellspacing="0" style="margin-top:15px;">
                <tr>
                  @if(!empty($signaturePhotoUrl))
                    <td style="padding-right:15px; vertical-align:top;">
                      <img src="{{ $signaturePhotoUrl }}" alt="{{ $senderName }}" width="60" height="60" style="border-radius:50%; display:block; object-fit:cover;">
                    </td>
                  @endif
                  <td style="vertical-align:top; font-size:14px; color:#333333; line-height:1.4;">
                    <strong>{{ $senderName }}</strong><br>
                    @if(!empty($senderRole))
                      {{ $senderRole }}<br>
                    @endif
                    Equipe {{ $brandName }}<br>
                    @if(!empty($senderEmail))
                      <span style="color:{{ $brandPrimary }};">{{ $senderEmail }}</span>
                    @endif
                  </td>
                </tr>
              </table>

            </td>
          </tr>

          <tr>
            <td align="center" style="background-color:#f7f7f7; padding:20px; border-top:1px solid #eeeeee;">
              <p style="margin:0; font-size:12px; color:#888888;">
                @if(!empty($manualEmail))
                  {{ $brandName }} — responda este e-mail se precisar falar conosco.
                @else
                  {{ $brandName }} — este é um e-mail automático; não é necessário responder.
                @endif
              </p>
              <p style="margin:8px 0 0 0; font-size:12px; color:#aaaaaa;">
                © {{ $year }} {{ $brandName }}. Todos os direitos reservados.
              </p>
            </td>
          </tr>

        </table>

      </td>
    </tr>
  </table>

</body>
</html>
