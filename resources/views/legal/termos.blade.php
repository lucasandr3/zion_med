<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Termos de Uso - Zion Med</title>
    <link rel="icon" type="image/png" href="{{ asset('favicon-96x96.png') }}" sizes="96x96" />
    <link rel="icon" type="image/svg+xml" href="{{ asset('favicon.svg') }}" />
    <link rel="shortcut icon" href="{{ asset('favicon.ico') }}" />
    <link rel="apple-touch-icon" sizes="180x180" href="{{ asset('apple-touch-icon.png') }}" />
    <meta name="apple-mobile-web-app-title" content="ZionMed" />
    <link rel="manifest" href="{{ asset('site.webmanifest') }}" />
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="bg-bg-main min-h-screen py-12 px-4" style="font-family:Inter,sans-serif;color:var(--c-text)">
    <div class="max-w-3xl mx-auto">
        <h1 class="text-2xl font-bold mb-6">Termos de Uso</h1>
        <p class="text-sm text-muted mb-6">Última atualização: {{ now()->format('d/m/Y') }}</p>

        <div class="space-y-6 text-sm">
            <section>
                <h2 class="font-semibold text-base mb-2">1. Aceitação</h2>
                <p>Ao acessar e utilizar o Zion Med, você concorda com estes Termos de Uso. Se não concordar, não utilize o serviço.</p>
            </section>

            <section>
                <h2 class="font-semibold text-base mb-2">2. Descrição do serviço</h2>
                <p>O Zion Med é uma plataforma de governança documental para clínicas, que permite a criação de formulários operacionais, coleta de consentimentos, assinatura eletrônica, geração de PDF e fluxo de aprovação de protocolos. O uso é destinado a profissionais e estabelecimentos de saúde.</p>
            </section>

            <section>
                <h2 class="font-semibold text-base mb-2">3. Uso adequado</h2>
                <p>O usuário compromete-se a utilizar o serviço de forma lícita, em conformidade com a legislação vigente (incluindo LGPD e normas do setor de saúde), e a não utilizar a plataforma para fins ilícitos, fraudulentos ou que violem direitos de terceiros.</p>
            </section>

            <section>
                <h2 class="font-semibold text-base mb-2">4. Conta e responsabilidade</h2>
                <p>As clínicas e usuários são responsáveis pela veracidade dos dados cadastrais e pelo uso de suas credenciais. O Zion Med não se responsabiliza por uso indevido da conta em caso de negligência do titular.</p>
            </section>

            <section>
                <h2 class="font-semibold text-base mb-2">5. Propriedade intelectual</h2>
                <p>O software, marcas e conteúdos da plataforma Zion Med são de propriedade do titular do serviço. O usuário não adquire direitos sobre eles, exceto o direito de uso conforme contratado.</p>
            </section>

            <section>
                <h2 class="font-semibold text-base mb-2">6. Limitação de responsabilidade</h2>
                <p>O Zion Med é oferecido "como está". Na medida permitida pela lei, não nos responsabilizamos por danos indiretos, incidentais ou consequenciais decorrentes do uso ou da indisponibilidade do serviço. A responsabilidade pela adequação dos formulários e do fluxo de trabalho às normas aplicáveis é da clínica contratante.</p>
            </section>

            <section>
                <h2 class="font-semibold text-base mb-2">7. Alterações</h2>
                <p>Estes termos podem ser alterados a qualquer momento. O uso continuado do serviço após a publicação de alterações constitui aceitação dos novos termos. Recomendamos a leitura periódica desta página.</p>
            </section>

            <section>
                <h2 class="font-semibold text-base mb-2">8. Contato</h2>
                <p>Para dúvidas sobre estes Termos de Uso, entre em contato através do canal disponível no site ou na plataforma.</p>
            </section>
        </div>

        <p class="mt-10 text-sm text-muted">
            <a href="{{ url('/') }}" class="underline hover:no-underline">Voltar ao início</a>
            &nbsp;·&nbsp;
            <a href="{{ route('privacidade') }}" class="underline hover:no-underline">Política de Privacidade</a>
        </p>
    </div>
</body>
</html>
