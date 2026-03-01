# PRD --- ZionMed

## 1. Visão Geral do Produto

**Nome:** ZionMed\
**Tipo:** SaaS B2B para clínicas\
**Objetivo:** Digitalizar, padronizar e organizar processos operacionais
de clínicas por meio de formulários inteligentes com assinatura,
rastreabilidade e fluxo de aprovação.

------------------------------------------------------------------------

## 2. Problema

Clínicas enfrentam:

-   Uso excessivo de papel\
-   Documentos dispersos em planilhas e WhatsApp\
-   Falta de histórico e controle\
-   Risco jurídico por ausência de registro formal\
-   Processos internos desorganizados

Sistemas médicos tradicionais focam em prontuário e faturamento, mas
deixam lacunas na organização operacional.

------------------------------------------------------------------------

## 3. Solução

ZionMed oferece:

-   Formulários digitais padronizados\
-   Assinatura eletrônica integrada\
-   Geração automática de PDF\
-   Fluxo simples de aprovação\
-   Histórico e rastreabilidade\
-   Organização multiusuário por clínica

------------------------------------------------------------------------

## 4. Público-Alvo

### Primário

-   Clínicas médicas de pequeno e médio porte\
-   Clínicas odontológicas\
-   Estética e harmonização\
-   Fisioterapia

### Perfil decisor

-   Dono(a) da clínica\
-   Gestor(a) administrativo\
-   Coordenador(a)

------------------------------------------------------------------------

## 5. Proposta de Valor

> Organize e proteja os processos da sua clínica sem substituir seu
> sistema atual.

Benefícios: - Redução de papel\
- Segurança documental\
- Controle interno\
- Facilidade de auditoria\
- Padronização operacional

------------------------------------------------------------------------

## 6. Funcionalidades (MVP)

### 6.1 Gestão de Clínica

-   Cadastro da clínica\
-   Tema visual personalizado\
-   Configuração de e-mail de notificação

### 6.2 Gestão de Usuários

Perfis: - Owner\
- Manager\
- Staff

Permissões básicas por perfil.

------------------------------------------------------------------------

### 6.3 Templates de Formulários

-   Criar e editar templates\
-   Campos dinâmicos (texto, select, radio, checkbox, data, número,
    textarea)\
-   Ordenação de campos\
-   Ativar/desativar template\
-   Link público opcional

Templates iniciais: - Anamnese\
- Termo de consentimento\
- Checklist de sala\
- Triagem\
- Solicitação interna\
- Pesquisa de satisfação

------------------------------------------------------------------------

### 6.4 Submissões

-   Registro de envio\
-   Status:
    -   Pendente\
    -   Aprovado\
    -   Reprovado\
-   Comentários internos\
-   Histórico por data

------------------------------------------------------------------------

### 6.5 Assinatura Digital

-   Captura via canvas\
-   Armazenamento da imagem\
-   Inclusão no PDF final

------------------------------------------------------------------------

### 6.6 PDF Automático

-   Geração após submissão\
-   Logo da clínica\
-   Dados do formulário\
-   Assinatura\
-   Data e hora

------------------------------------------------------------------------

### 6.7 Relatórios

-   Listagem filtrável\
-   Exportação CSV\
-   Download de PDF individual

------------------------------------------------------------------------

## 7. Requisitos Técnicos

-   Laravel 12\
-   Blade + Tailwind\
-   Multi-clínica via clinic_id\
-   Autenticação Laravel Breeze\
-   Armazenamento seguro de arquivos\
-   Proteção CSRF\
-   Rate limit em links públicos

------------------------------------------------------------------------

## 8. Fora do MVP

-   Integração com ERP médico\
-   Integração com WhatsApp\
-   Prontuário completo\
-   BI avançado\
-   Automação complexa\
-   LGPD avançado (apenas base estrutural)

------------------------------------------------------------------------

## 9. Métricas de Sucesso

-   10 clínicas pagantes no primeiro trimestre\
-   Ticket médio ≥ R\$ 249\
-   Churn mensal \< 8%\
-   80% dos clientes utilizando pelo menos 3 templates

------------------------------------------------------------------------

## 10. Roadmap

### Fase 1 --- MVP validado

-   Venda local\
-   Ajustes baseados em feedback

### Fase 2 --- V1

-   Dashboard analítico\
-   Notificações automáticas\
-   Templates por nicho

### Fase 3 --- Expansão

-   Integração com ERPs\
-   API pública\
-   Módulos pagos adicionais

------------------------------------------------------------------------

## 11. Posicionamento Final

ZionMed não é um "form builder".

É:

> Sistema de organização documental e operacional para clínicas.
