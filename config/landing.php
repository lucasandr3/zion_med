<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Landing Page Copy (posicionamento comercial)
    |--------------------------------------------------------------------------
    |
    | Textos para a landing conforme SPEC: consentimento e documentação clínica
    | digital, não prontuário completo. Foco em estética e odontologia.
    | Integrações (ex.: Feegow) são plus opcional — não o núcleo da mensagem.
    |
    */

    'headline' => env('LANDING_HEADLINE', 'Consentimentos e fichas assinados — sem papel.'),

    'subheadline' => env(
        'LANDING_SUBHEADLINE',
        'O paciente preenche e assina no celular, Instagram ou tablet. Você recebe o PDF com evidências no Gestgo — anamneses, termos e documentos clínicos organizados, sem digitação na recepção.'
    ),

    'niches' => ['estetica', 'odontologia', 'veterinaria'],

    /** Pontos de confiança (hero / trust bar) — sobrescreva via LANDING_TRUST_POINTS JSON array opcional */
    'trust_points' => json_decode((string) env('LANDING_TRUST_POINTS', ''), true) ?: [
        'PDF com evidências no Gestgo',
        'Celular, link na bio ou tablet',
        'Complementa o sistema que você já usa',
    ],

];
