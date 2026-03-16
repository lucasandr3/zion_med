# SPEC - Zion Med como opção viável contra Anaclara e ElevaSign

## Objetivo
Transformar o Zion Med de um sistema de formulários/protocolos em uma plataforma competitiva de documentação clínica digital, consentimento e assinatura eletrônica com trilha de evidências, foco inicial em clínicas pequenas e médias no Brasil.

## Contexto
Hoje o produto já possui:
- Multiempresa / multi-clínica
- Templates e formulários públicos
- Assinatura desenhada em canvas
- Protocolo automático
- PDF gerado
- Aprovação e revisão interna
- Auditoria básica
- Billing com trial e assinatura

Hoje o produto ainda não possui, de forma robusta:
- Fluxo de assinatura por WhatsApp
- Validação forte de identidade do signatário
- Snapshot imutável do documento assinado
- Evidência completa orientada a litígio
- Pacote LGPD/compliance empacotado
- Posicionamento comercial claro como solução de consentimento/documentação

## Meta de Mercado
Ser percebido como:
- Plataforma de consentimento e documentação clínica digital
- Solução simples para clínicas pequenas e médias
- Alternativa mais enxuta e mais rápida de implantar que soluções grandes
- Opção juridicamente mais confiável que improvisos com papel, PDF e WhatsApp

## Não Objetivos
Nesta fase, o produto não deve tentar competir como:
- Prontuário completo de alta complexidade
- ERP completo para clínicas
- Plataforma de convênios, faturamento hospitalar ou telemedicina avançada
- Sistema generalista para toda a saúde sem nicho definido

## Posicionamento Recomendado
- **Mensagem principal:** "Consentimentos, anamneses e documentos clínicos com assinatura eletrônica, protocolo e trilha de evidências para clínicas pequenas e médias."
- **Mensagem secundária:** "Menos papel, mais organização, mais rastreabilidade e mais segurança operacional."
- **Nicho inicial:** Estética, Odontologia

## Requisitos de Produto (Epics)

### Epic 1 - Assinatura eletrônica robusta
- Capturar nome, IP, user agent, data/hora, hash do documento, hash do payload
- Salvar versão do template, snapshot do conteúdo, aceite explícito do texto
- Salvar idioma, fuso, canal (web, WhatsApp, e-mail), status (iniciado, visualizado, aceito, assinado, concluído)

### Epic 2 - Validação de identidade
- OTP por e-mail / SMS / WhatsApp
- CPF opcional e validação
- Nível de segurança configurável por clínica (básico / reforçado)

### Epic 3 - Fluxo de assinatura por link
- Envio por e-mail e WhatsApp, reenvio, lembretes, expiração configurável
- Página de assinatura mobile-first, tela de conclusão com protocolo

### Epic 4 - Gestão de documentos e monitoramento
- Caixas: pendentes, assinados, expirados
- Timeline por documento, filtros, reenvio, cancelamento
- Exportação do dossiê, dashboard de métricas

### Epic 5 - Biblioteca de templates por nicho
- Templates por especialidade (estética, odontologia)
- Versionamento, duplicação, publicação/despublicação

### Epic 6 - Dossiê de evidências
- PDF + metadados + hash + timeline
- Exportação ZIP (PDF + JSON)

### Epic 7 - Compliance LGPD
- Política de privacidade, termos, DPA, retenção, incidentes, logs de acesso

### Epic 8 - Contratos e jurídico comercial
- Revisão de copy e alegações sobre validade jurídica

## Requisitos Técnicos
- Versionamento de template, snapshot do documento, hashes SHA-256
- Timeline de eventos do fluxo, storage write-once para dossiês
- Segurança: segregação tenant, expiração/revogação de link, antifraude
- Observabilidade: logs de abertura, conclusão, OTP, reenvio, métricas

## Landing Page
- **Headline:** "Consentimentos, anamneses e documentos clínicos com assinatura eletrônica e protocolo."
- **Subheadline:** "Organize a documentação da sua clínica, reduza papelada e tenha trilha de evidências em um fluxo simples para equipe e pacientes."
- Blocos: Hero, 3 passos, segurança, nichos, templates, evidências, FAQ, CTAs

## Preço Recomendado
- Hoje: R$ 147–197/mês
- Com spec implementada: R$ 247–297/mês
- Com WhatsApp + OTP + dossiê forte: R$ 297–397/mês
- Fundador: R$ 149–197 por 6–12 meses

## Roadmap
- **Fase 1:** Viabilidade (nicho, landing, templates, privacidade/termos, evidência mínima, dossiê exportável)
- **Fase 2:** OTP, WhatsApp, reenvio, snapshot versionado, hash documento
- **Fase 3:** Casos de uso, depoimentos, multiunidade, materiais jurídicos

## Decisão Final
Competir como **solução de consentimento e documentação clínica digital**, não como prontuário completo. Vantagem: simplicidade, rapidez, rastreabilidade, templates, preço, foco em nicho.
