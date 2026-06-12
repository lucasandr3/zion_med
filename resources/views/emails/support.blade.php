@php
    $brandName = $brandName ?? config('app.name');
    $brandLogoUrl = $brandLogoUrl ?? null;
    $brandPrimary = $brandPrimary ?? '#1a3fae';
    $recipientName = $recipientName ?? 'cliente';
    $introductionHtml = $introductionHtml ?? '';
    $messageBodyHtml = $messageBodyHtml ?? '';
    $closingThanksHtml = $closingThanksHtml ?? '';
    $signaturePhotoUrl = $signaturePhotoUrl ?? null;
    $senderName = $senderName ?? ($brandName ?? 'Equipe');
    $senderRole = $senderRole ?? null;
    $senderEmail = $senderEmail ?? null;
    $whatsappUrl = $whatsappUrl ?? null;
    $showWhatsappButton = (bool) ($showWhatsappButton ?? false);
    $messageAfterWhatsappHtml = $messageAfterWhatsappHtml ?? '';
    $year = $year ?? now()->year;

    if ($showWhatsappButton && empty($whatsappUrl)) {
        $whatsappUrl = 'https://wa.me/5534996460818?text='.rawurlencode('Olá, tudo bem? Vim pelo e-mail.');
    }
@endphp
<!DOCTYPE html>
<html lang="pt-BR">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Template Suporte {{ $brandName }}</title>
</head>
<body style="margin:0; padding:0; background-color:#f4f4f4; font-family:Arial, Helvetica, sans-serif;">

  <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="background-color:#f4f4f4; padding:30px 0;">
    <tr>
      <td align="center">

        <table role="presentation" width="600" cellpadding="0" cellspacing="0" style="background-color:#ffffff; border-radius:8px; overflow:hidden; box-shadow:0 1px 3px rgba(0,0,0,0.08); max-width:600px; width:100%;">

          <tr>
            <td align="center" style="padding:25px 20px; background-color:{{ $brandPrimary }};">
              <table role="presentation" cellpadding="0" cellspacing="0">
                <tr>
                  @if(!empty($brandLogoUrl))
                    <td style="vertical-align:middle; padding-right:12px;">
                      <img src="{{ $brandLogoUrl }}" alt="{{ $brandName }}" width="40" height="40" style="display:block;">
                    </td>
                  @endif
                  <td style="vertical-align:middle;">
                    <span style="color:#ffffff; font-size:22px; font-weight:bold; font-family:Arial, Helvetica, sans-serif;">{{ $brandName }}</span>
                  </td>
                </tr>
              </table>
            </td>
          </tr>

          <tr>
            <td style="padding:30px 30px 20px 30px; color:#333333; font-size:15px; line-height:1.6;">

              <p style="margin-top:0;">Olá, <strong>{{ $recipientName }}</strong>, tudo bem?</p>

              {!! $introductionHtml !!}

              <p style="margin:0 0 16px 0;">Queremos garantir que você tenha todo o suporte necessário...</p>

              {!! $messageBodyHtml !!}

              @if($showWhatsappButton && !empty($whatsappUrl))
                <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="margin:20px 0;">
                  <tr>
                    <td align="center">
                      <table role="presentation" cellpadding="0" cellspacing="0">
                        <tr>
                          <td align="center" bgcolor="#25D366" style="border-radius:5px; mso-padding-alt:12px 28px;">
                            <a href="{{ $whatsappUrl }}" target="_blank" rel="noopener noreferrer" style="display:inline-block; padding:12px 28px; color:#ffffff; text-decoration:none; font-weight:bold; font-size:14px; line-height:1.2; border-radius:5px; background-color:#25D366;">
                              Falar no WhatsApp
                            </a>
                          </td>
                        </tr>
                      </table>
                    </td>
                  </tr>
                </table>
              @endif

              {!! $messageAfterWhatsappHtml !!}

              {!! $closingThanksHtml !!}

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
                      <a href="mailto:{{ $senderEmail }}" style="color:{{ $brandPrimary }}; text-decoration:none;">{{ $senderEmail }}</a>
                    @endif
                  </td>
                </tr>
              </table>

            </td>
          </tr>

          <tr>
            <td align="center" style="background-color:#f7f7f7; padding:20px; border-top:1px solid #eeeeee;">
              <p style="margin:0; font-size:12px; color:#888888;">
                {{ $brandName }} — responda este e-mail se precisar falar conosco.
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
