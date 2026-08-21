# Gap-list — Fluxo ideal do paciente (consentimento / protocolo)

**Fonte canônica (completa):**  
→ [`zion_med_front/docs/GAP_FLUXO_PACIENTE_CONSENTIMENTO.md`](../../zion_med_front/docs/GAP_FLUXO_PACIENTE_CONSENTIMENTO.md)

## Status (2026-08-21)

| # | Desejado | Status |
|---|----------|--------|
| 1 | Identificação (Opção B: CPF/código + nascimento + confirmar nome) | **FEITO** |
| 2 | Identity no snapshot + PDF | **FEITO** |
| 3 | Confirma entendimento no PDF | **FEITO** |
| 4 | Assinatura obrigatória no publish de consentimento | **FEITO** |
| 5a | PDF persistido (`PdfService::persistSubmissionPdf` + job fallback) | **FEITO** |
| 5b | Cópia clínica no e-mail (anexo PDF) | **FEITO** |
| 5c | Cópia paciente (token + download + e-mail) | **FEITO** |
| R1–R4 | Anexo clínica, stream preferindo disk, eventos dossiê, tests | **FEITO** |

## Ops

```bash
php artisan migrate
# queue só necessária se persist sync falhar (fallback GenerateSubmissionPdfJob)
php artisan queue:work
```

Migration: `2026_08_21_100000_add_patient_copy_and_pdf_to_form_submissions.php`

## Próximos

Ver backlog **P2 (R5–R9)** no doc canônico do front.

## Relacionados

- `docs/ROADMAP_MODERNIZACAO_API.md`
- `docs/SPEC_CONCORRENCIA_ASSINATURA_DIGITAL.md`
- `docs/FRONTEND_README.md`
- Test: `tests/Feature/Api/PublicFormPatientCopyAndGateBTest.php`
