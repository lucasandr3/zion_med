# Plano operacional de resposta a incidentes (LGPD / disponibilidade)

Modelo interno de operação. **Não substitui assessoria jurídica.** Ajuste prazos, contatos e canais antes de usar em produção.

## 1. Objetivo

Detectar, conter, erradicar e recuperar incidentes que afetem:

- confidencialidade, integridade ou disponibilidade de dados pessoais/sensíveis;
- continuidade do serviço (API, filas, storage MinIO, pagamento Asaas, e-mail).

## 2. Severidade

| Nível | Exemplos | Meta de primeira resposta |
|-------|----------|---------------------------|
| **S1** | Vazamento confirmado, ransomware, perda de backup, indisponibilidade total > 1h | 30 min |
| **S2** | Falha parcial (MinIO, fila, webhook Asaas), suspeita de acesso indevido | 2 h |
| **S3** | Degradação, alerta preventivo, falha em job não crítico | próximo dia útil |

## 3. Papéis

- **Comandante do incidente:** responsável técnico de plantão (definir nome/contato).
- **Comunicação:** notifica controladores (clínicas) quando houver risco relevante aos titulares (LGPD art. 48).
- **Jurídico/DPO:** decide comunicação à ANPD e texto aos titulares, quando cabível.

## 4. Fluxo (resumo)

1. **Detectar** — alerta `platform:health-check`, logs, denúncia, monitoramento de host.
2. **Registrar** — data/hora UTC, sistemas afetados, evidências (sem colar PII em chat público).
3. **Conter** — revogar chaves, isolar storage, pausar jobs, rotacionar segredos.
4. **Erradicar** — corrigir causa raiz, aplicar patch, invalidar tokens.
5. **Recuperar** — restaurar a partir do runbook de backup; validar amostragem.
6. **Notificar** — controladores afetados sem demora injustificada; ANPD se risco/dano relevante.
7. **Post-mortem** — até 5 dias úteis após encerramento (S1/S2).

## 5. Contatos (preencher)

| Função | Nome | Canal |
|--------|------|-------|
| Plantão técnico | [NOME] | [e-mail/telefone] |
| Encarregado / DPO | [NOME] | [e-mail] |
| Suporte clínicas | [e-mail suporte] | |

## 6. O que NÃO fazer

- Enviar dumps clínicos, CPF ou PDFs preenchidos para Slack/e-mail pessoal.
- Confirmar vazamento publicamente sem alinhamento jurídico.
- Restaurar backup em produção sem snapshot prévio do estado atual.
