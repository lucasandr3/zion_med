# Zion Med

MVP para clínicas: formulários operacionais, assinatura digital, geração de PDF e fluxo de aprovação. Multi-clínica (tenancy por `clinic_id`).

## Stack

- **Laravel 12** (PHP 8.3+)
- **Blade** + **Tailwind CSS**
- **PostgreSQL** (produção) — migrations compatíveis com MySQL/SQLite
- **barryvdh/laravel-dompdf** para PDF
- Filas: driver `sync` no MVP (pronto para async)

## Requisitos

- PHP 8.3+
- Composer
- Node.js/npm (para Vite/Tailwind)
- PostgreSQL (ou MySQL/SQLite para desenvolvimento)

## Setup local

```bash
# Clone e entre na pasta
cd zion_med

# Dependências PHP
composer install

# Variáveis de ambiente
cp .env.example .env
php artisan key:generate

# Banco: use PostgreSQL em produção. Para dev, no .env:
# DB_CONNECTION=sqlite
# DB_DATABASE=/caminho/para/database/database.sqlite
# Ou DB_CONNECTION=pgsql e preencha DB_HOST, DB_DATABASE, DB_USERNAME, DB_PASSWORD

# Criar DB SQLite (se usar)
touch database/database.sqlite

# Migrations e seed (clínica demo + 3 templates)
php artisan migrate --seed

# Storage: MinIO (produção) ou link público (dev)
# Produção: configure MinIO (ex.: Easy Panel). No .env defina MINIO_ENDPOINT, MINIO_ACCESS_KEY, MINIO_SECRET_KEY e os buckets (MINIO_SUBMISSIONS_BUCKET, MINIO_ATTACHMENTS_BUCKET, MINIO_ASSETS_BUCKET, MINIO_INVOICES_BUCKET). Crie os buckets no Console MinIO.
php artisan storage:link   # Apenas se usar disco local para logos (dev)

# Frontend
npm install
npm run build
```

## Acesso após o seed

- **URL:** `http://localhost:8000` (ou `php artisan serve`)
- **Login:** `admin@demo.zionmed.com` / `senha123`
- Perfil: **Owner** (acesso a clínica, usuários, templates, protocolos)

## Fluxo básico

1. **Login** → Dashboard (resumo de pendentes e templates).
2. **Clínica > Configurações** (Owner): nome, logo, e-mail para notificações.
3. **Usuários** (Owner): criar/editar/desativar; perfis: Owner, Manager, Staff.
4. **Templates:** criar/editar, gerenciar campos (texto, textarea, número, data, select, radio, checkbox, arquivo, assinatura). Botão **Gerar link público** gera token e URL.
5. **Formulário público:** acesse `/f/{token}` (sem login). Preencha, desenhe assinatura no canvas, anexe arquivos. Ao enviar, gera protocolo e (se configurado) envia e-mail para a clínica.
6. **Protocolos:** listar (filtros por template, status, data), ver detalhes, aprovar/reprovar (Manager/Owner), baixar PDF, **Exportar CSV** (sem anexos).

## Multi-clínica (tenancy)

- Todas as tabelas principais têm `clinic_id`.
- O **middleware SetClinic** define a clínica atual na sessão a partir do usuário logado (`user->clinic_id`).
- **Global Scope** (opcional) em `FormTemplate` e `FormSubmission`: consultas ficam automaticamente filtradas por `session('current_clinic_id')`.
- Para operações administrativas que precisem ver todas as clínicas, use `Model::withoutGlobalScopes()`.

### Como criar uma nova clínica

1. Inserir registro em `clinics` (nome, slug, notification_email).
2. Criar ao menos um usuário em `users` com `clinic_id` apontando para essa clínica e `role = 'owner'`.
3. Fazer login com esse usuário: o sistema define a clínica na sessão e o escopo passa a ser dessa clínica.

Não há subdomínio no MVP: a clínica é definida pelo usuário logado. Para futuro com subdomínio (ex.: `clinica1.zionmed.com`), pode-se no middleware ler o subdomínio e definir `session('current_clinic_id')` a partir de uma tabela `clinics.subdomain` ou equivalente.

## Perfis e permissões

| Perfil   | Clínica | Usuários | Templates | Aprovar protocolos | Ver protocolos |
|----------|---------|-----------|-----------|--------------------|----------------|
| Owner    | Sim     | Sim       | Sim       | Sim                | Sim            |
| Manager  | Não     | Não       | Sim       | Sim                | Sim            |
| Staff    | Não     | Não       | Não       | Não                | Sim (somente leitura) |

## Templates seedados

1. **Anamnese (Básica)** — nome, data nascimento, CPF, queixa principal, histórico, alergias, assinatura.
2. **Termo de Consentimento** — paciente, data, procedimento, declarações, assinatura.
3. **Checklist de Sala** — data, responsável, itens de conferência, temperatura, observações, assinatura.

## Integração ASAAS (Sandbox) e assinatura

Pagamentos e trial são integrados ao **ASAAS** (ambiente Sandbox para testes).

### Variáveis de ambiente (.env)

```env
ASAAS_BASE_URL=https://sandbox.asaas.com/api/v3
ASAAS_API_KEY=SEU_TOKEN_SANDBOX
ASAAS_WEBHOOK_SECRET=um-segredo-gerado
ASAAS_TRIAL_DAYS=14
ASAAS_GRACE_DAYS=7
ASAAS_BLOCK_MODE=soft
ASAAS_PRODUCT_NAME=ZionMed
```

- **ASAAS_BASE_URL:** use `https://sandbox.asaas.com/api/v3` para Sandbox.
- **ASAAS_API_KEY:** token da API no [painel Sandbox ASAAS](https://sandbox.asaas.com/) (Integrações > API).
- **ASAAS_WEBHOOK_SECRET:** valor definido ao criar o webhook no ASAAS; o endpoint verifica o header `asaas-access-token`.

### Configurar webhook no Sandbox

1. Acesse [Sandbox ASAAS](https://sandbox.asaas.com/) → Integrações → Webhooks.
2. Cadastre a URL do seu ambiente (ex.: `https://seu-dominio.com/webhooks/asaas` ou via ngrok/Cloudflare Tunnel para localhost).
3. Selecione os eventos: **PAYMENT_CREATED**, **PAYMENT_RECEIVED**, **PAYMENT_CONFIRMED**, **PAYMENT_OVERDUE**, **SUBSCRIPTION_CREATED**, **SUBSCRIPTION_UPDATED**, **SUBSCRIPTION_DELETED** (ou equivalentes).
4. Defina um **Token de autenticação** e coloque o mesmo valor em `ASAAS_WEBHOOK_SECRET` no `.env`.

### Simular pagamento no Sandbox

- No painel Sandbox, use a opção de simular pagamento de cobrança (boleto/PIX).
- Ou crie uma assinatura via app (menu **Assinatura** → escolher plano) e use o link de pagamento que o ASAAS envia por e-mail no Sandbox para marcar como pago.

### Regras de trial e bloqueio

- **Trial:** novas clínicas (seed ou criação) recebem `trial_ends_at = now() + ASAAS_TRIAL_DAYS`, `subscription_status = trial`, `billing_status = ok`.
- **Fim do trial:** se não houver assinatura ativa, o sistema marca `subscription_status = inactive`, `billing_status = blocked` e bloqueia o acesso (redireciona para `/billing`).
- **Inadimplência (past_due):** ao receber webhook de pagamento **OVERDUE**, o sistema define `subscription_status = past_due`, `billing_status = attention` e `grace_ends_at = now() + ASAAS_GRACE_DAYS`. O usuário continua acessando o app com um aviso; após `grace_ends_at`, o acesso é bloqueado e apenas `/billing` e `/logout` permanecem liberados.
- **Pagamento recebido:** webhooks **PAYMENT_RECEIVED** / **CONFIRMED** reativam a clínica (`active`, `ok`) e limpam `grace_ends_at`.

Rotas sempre permitidas (mesmo com bloqueio): `/billing`, `/billing/*`, `/logout`, `/webhooks/asaas`, `/f/*` (formulário público).

## Deploy em produção (Easy Panel)

O build usa **Dockerfile** + **Supervisor**. No arranque do container (entrypoint) o seguinte já roda de forma automática:

| O quê | Como |
|-------|------|
| **Migrações** | `php artisan migrate --force` no entrypoint (single-instance). |
| **Queue worker** | Processo `php artisan queue:work` via Supervisor (webhooks, jobs assíncronos). |
| **Scheduler** | Loop a cada 60s `php artisan schedule:run` via Supervisor (ex.: `platform:notify-billing` diário). |
| **Webhook ASAAS** | Rota `POST /webhooks/asaas` excluída do CSRF em `bootstrap/app.php`. |

No Easy Panel basta configurar as **variáveis de ambiente** de produção (APP_ENV=production, APP_DEBUG=false, APP_URL, DB_*, ASAAS_* produção, MinIO, etc.) e fazer o deploy. Não é necessário rodar queue ou cron manualmente.

Se usar mais de um container (réplicas), rode `migrate` apenas em um job de deploy e desative o `migrate` no entrypoint.

## Comandos úteis

```bash
php artisan migrate --seed   # Recriar DB e seed
php artisan storage:link      # Link público (dev). Produção: use MinIO (veja .env.example MINIO_*).
php artisan test              # Testes (mín. 6 feature)
npm run dev                   # Vite em desenvolvimento
```

## Testes

Mínimo 6 testes feature:

- Login (página carrega, credenciais válidas/inválidas, dashboard exige auth).
- Formulário público (página com token válido, envio gera protocolo).
- Escopo por clínica (usuário de uma clínica não vê protocolos de outra).
- Export CSV (exige autenticação, retorna CSV para usuário logado).
- Templates (exige autenticação, owner pode criar template).

Execute: `php artisan test`

## Segurança

- CSRF em rotas web.
- Rotas públicas protegidas por token (32+ caracteres).
- Rate limit no endpoint público (`/f/{token}` POST).
- Validação de upload (tamanho e MIME).
- Auditoria (AuditService) para ações principais.
- Gates/Policies para autorização por perfil e clínica.

## Estrutura principal

- **Models:** Clinic, User, FormTemplate, FormField, FormSubmission, SubmissionValue, SubmissionAttachment, SubmissionSignature, AuditLog.
- **Services:** PdfService, AuditService, PublicLinkService, SubmissionService.
- **Rotas em português:** dashboard, clinica.configuracoes, usuarios.*, templates.*, protocolos.*, formulario-publico.*.

---

**Zion Med** — formulários para clínicas, sem integração com prontuário. Focado em venda rápida para clínicas pequenas e médias.
