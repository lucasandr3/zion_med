<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Política de Privacidade - Zion Med</title>
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
        <h1 class="text-2xl font-bold mb-6">Política de Privacidade</h1>
        <p class="text-sm text-muted mb-6">Última atualização: {{ now()->format('d/m/Y') }}</p>

        <div class="space-y-6 text-sm">
            <section>
                <h2 class="font-semibold text-base mb-2">1. Responsável</h2>
                <p>O Zion Med é um sistema que permite a clínicas e profissionais de saúde coletar e organizar dados por meio de formulários digitais. Cada clínica que utiliza o serviço é responsável pelos dados que coleta e armazena através da plataforma.</p>
            </section>

            <section>
                <h2 class="font-semibold text-base mb-2">2. Dados que coletamos</h2>
                <p>Os dados preenchidos nos formulários (incluindo nome, e-mail, dados de saúde e assinaturas) são fornecidos voluntariamente pelo titular e utilizados exclusivamente para finalidades de atendimento, registro de consentimento e organização operacional da clínica, em conformidade com a Lei Geral de Proteção de Dados (LGPD – Lei 13.709/2018).</p>
            </section>

            <section>
                <h2 class="font-semibold text-base mb-2">3. Base legal e finalidade</h2>
                <p>O tratamento é realizado com base em consentimento, execução de contrato ou obrigação legal, conforme o caso. Os dados são utilizados apenas para as finalidades informadas em cada formulário e para cumprimento de obrigações legais e regulatórias aplicáveis ao setor de saúde.</p>
            </section>

            <section>
                <h2 class="font-semibold text-base mb-2">4. Retenção de dados</h2>
                <p>Os protocolos e documentos gerados (incluindo formulários preenchidos e assinaturas) são mantidos pelo período necessário ao cumprimento das finalidades descritas e das obrigações legais aplicáveis (incluindo prazos de guarda de prontuários e documentos médicos conforme legislação vigente). Após esse período, os dados podem ser anonimizados ou eliminados conforme política interna da clínica e da plataforma.</p>
            </section>

            <section>
                <h2 class="font-semibold text-base mb-2">5. Compartilhamento</h2>
                <p>Os dados não são vendidos. Eles podem ser compartilhados apenas quando necessário para cumprimento de ordem judicial ou determinação de autoridade competente, ou com seu consentimento expresso.</p>
            </section>

            <section>
                <h2 class="font-semibold text-base mb-2">6. Seus direitos (LGPD)</h2>
                <p>Você tem direito a acesso, correção, anonimização, portabilidade, eliminação dos dados desnecessários ou excessivos, revogação do consentimento e informação sobre compartilhamento. Para exercer esses direitos, entre em contato com a clínica que coletou seus dados ou, quando aplicável, com o canal de suporte do Zion Med.</p>
            </section>

            <section>
                <h2 class="font-semibold text-base mb-2">7. Segurança</h2>
                <p>Adotamos medidas técnicas e organizacionais para proteger os dados contra acesso não autorizado, alteração, divulgação ou destruição.</p>
            </section>
        </div>

        <p class="mt-10 text-sm text-muted">
            <a href="{{ url('/') }}" class="underline hover:no-underline">Voltar ao início</a>
            &nbsp;·&nbsp;
            <a href="{{ route('termos') }}" class="underline hover:no-underline">Termos de Uso</a>
        </p>
    </div>
</body>
</html>
