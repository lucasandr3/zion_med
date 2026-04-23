<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Landing Page Copy (posicionamento comercial)
    |--------------------------------------------------------------------------
    |
    | Textos para a landing conforme SPEC: consentimento e documentação clínica
    | digital, não prontuário completo. Foco em estética e odontologia.
    |
    */

    'headline' => env('LANDING_HEADLINE', 'Consentimentos, anamneses e documentos clínicos com assinatura eletrônica e protocolo.'),

    'subheadline' => env('LANDING_SUBHEADLINE', 'Organize a documentação da sua clínica, reduza papelada e tenha trilha de evidências em um fluxo simples para equipe e pacientes.'),

    'niches' => ['estetica', 'odontologia'],

    /** Pontos de confiança (hero / trust bar) — sobrescreva via LANDING_TRUST_POINTS JSON array opcional */
    'trust_points' => json_decode((string) env('LANDING_TRUST_POINTS', ''), true) ?: [
        'Templates e link na bio',
        'Assinatura com evidências (IP, UA, hashes)',
        'OTP por e-mail e WhatsApp',
        'Dossiê ZIP (PDF + JSON)',
        'LGPD e rastreabilidade',
    ],

];
