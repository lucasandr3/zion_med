<?php

return [

    'headline' => env('LANDING_HEADLINE', 'Consentimentos e fichas assinados — sem papel.'),

    'subheadline' => env(
        'LANDING_SUBHEADLINE',
        'O paciente preenche e assina no celular, Instagram ou tablet. Você recebe o PDF com evidências no Gestgo — anamneses, termos e documentos clínicos organizados, sem digitação na recepção.'
    ),

    'niches' => ['estetica', 'odontologia', 'veterinaria', 'fisioterapia'],

    'trust_points' => json_decode((string) env('LANDING_TRUST_POINTS', ''), true) ?: [
        'PDF com evidências no Gestgo',
        'Celular, link na bio ou tablet',
        'Complementa o sistema que você já usa',
    ],

    /*
    | Páginas /para/:slug — conteúdo comercial por segmento (M-07).
    | demo_model aponta para /l/demo/{n} no app.
    */
    'segments' => [
        'fisioterapia-dermatofuncional' => [
            'slug' => 'fisioterapia-dermatofuncional',
            'seo_title' => 'Anamnese digital para fisioterapia dermatofuncional | Gestgo',
            'h1' => 'Sua ficha de anamnese dermatofuncional, pronta para preencher pelo celular.',
            'subtitle' => 'Use um modelo adaptável, envie antes do atendimento e organize respostas e documentos assinados em um só lugar.',
            'cta_primary' => 'Testar ficha digital grátis',
            'cta_secondary' => 'Ver demonstração do Link na Bio',
            'demo_model' => 8,
            'pain' => 'Fichas em papel atrasam o atendimento e somem no arquivo.',
            'outcomes' => [
                'Paciente preenche e assina no celular antes da sessão',
                'Histórico e PDF com evidências no Gestgo',
                'Perguntas editáveis sem depender de planilha',
            ],
            'faq' => [
                ['q' => 'Serve para dermatofuncional?', 'a' => 'Sim. Você parte de um modelo adaptável e inclui as perguntas da sua ficha.'],
                ['q' => 'Posso adicionar minhas perguntas?', 'a' => 'Sim. Importe sua ficha ou edite os campos do modelo antes de publicar.'],
                ['q' => 'Substitui agenda ou prontuário?', 'a' => 'Não. O Gestgo complementa a documentação; agenda e prontuário completos ficam nos sistemas especializados.'],
                ['q' => 'Como o paciente assina?', 'a' => 'Pelo celular ou tablet, com evidências registradas no protocolo.'],
            ],
        ],
        'estetica-facial' => [
            'slug' => 'estetica-facial',
            'seo_title' => 'Ficha de anamnese e consentimento para estética facial | Gestgo',
            'h1' => 'Anamneses e consentimentos digitais para os procedimentos da sua clínica.',
            'subtitle' => 'Organize as perguntas por procedimento, colete assinatura e mantenha versões e protocolos disponíveis para consulta.',
            'cta_primary' => 'Ver uma anamnese digital',
            'cta_secondary' => 'Ver demonstração do Link na Bio',
            'demo_model' => 3,
            'pain' => 'Termos e anamneses em papel dificultam versão, assinatura e busca depois.',
            'outcomes' => [
                'Fluxo anamnese → termo → assinatura → protocolo',
                'Campos configuráveis por procedimento',
                'Histórico por pessoa para a recepção',
            ],
            'faq' => [
                ['q' => 'Posso usar meus termos?', 'a' => 'Sim. Importe ou monte o modelo com o texto da sua clínica e revise antes de publicar.'],
                ['q' => 'Como funciona o histórico?', 'a' => 'Cada envio gera protocolo com status, PDF e eventos de assinatura/revisão.'],
                ['q' => 'É preciso tablet?', 'a' => 'Não. O paciente pode usar o próprio celular pelo link.'],
                ['q' => 'Como ficam as evidências?', 'a' => 'O Gestgo registra assinatura e metadados no protocolo — sem prometer validade jurídica universal.'],
            ],
        ],
        'odontologia' => [
            'slug' => 'odontologia',
            'seo_title' => 'Anamnese e consentimentos digitais para odontologia | Gestgo',
            'h1' => 'Fichas e termos odontológicos digitais, sem recriar tudo do zero.',
            'subtitle' => 'Envie a ficha antes da consulta, colete assinatura e encontre o documento depois em poucos cliques.',
            'cta_primary' => 'Testar grátis por 14 dias',
            'cta_secondary' => 'Ver demonstração do Link na Bio',
            'demo_model' => 4,
            'pain' => 'Papel na recepção e termos sem versão clara atrasam o fluxo clínico.',
            'outcomes' => [
                'Modelos por tipo de atendimento',
                'Assinatura no celular com protocolo organizado',
                'Link na bio para captar e pré-preencher',
            ],
            'faq' => [
                ['q' => 'Integra com meu sistema?', 'a' => 'O Gestgo foca na documentação; integrações opcionais existem conforme o plano e a configuração da clínica.'],
                ['q' => 'Posso importar minha ficha?', 'a' => 'Sim. Envie PDF ou foto, revise os campos e salve como modelo.'],
            ],
        ],
    ],

    'how_it_works' => [
        'importar-ficha' => [
            'slug' => 'importar-ficha',
            'seo_title' => 'Como importar sua ficha para o Gestgo | Gestgo',
            'h1' => 'A ficha que você já usa, sem recriar do zero.',
            'subtitle' => 'Envie um PDF ou foto, confira os campos identificados e publique sua ficha digital para o paciente preencher e assinar.',
            'steps' => [
                'Envie PDF ou foto da ficha em branco',
                'Revise os campos sugeridos ao lado do original',
                'Salve como modelo e compartilhe o link',
            ],
            'cta_primary' => 'Começar teste grátis',
            'cta_secondary' => 'Ver modelos por segmento',
        ],
    ],
];
