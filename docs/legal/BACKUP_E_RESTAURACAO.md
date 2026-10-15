# Backup e restauração (runbook operacional)

Procedimento alvo para ambiente Gestgo (PostgreSQL + MinIO + app Laravel). Ajuste paths e hosts do ambiente real.

## 1. O que fazer backup

| Ativo | Frequência sugerida | Retenção sugerida | Notas |
|-------|---------------------|-------------------|--------|
| PostgreSQL (lógico `pg_dump`) | diário | ≥ 14 dias + 1 mensal | incluir roles se necessário |
| Volumes MinIO (submissions/attachments/assets) | diário / contínuo | alinhada à política LGPD | versionamento se disponível |
| `.env` / secrets | fora do git; cofre | — | nunca no repositório |
| `platform_settings` | coberto pelo dump do DB | — | |

## 2. Backup PostgreSQL (exemplo)

```bash
# Homologação — adaptar host/usuário/db
pg_dump -Fc -h "$DB_HOST" -U "$DB_USERNAME" "$DB_DATABASE" -f "/backups/gestgo_$(date -u +%Y%m%dT%H%M%SZ).dump"
```

Testar restauração **mensalmente** em ambiente isolado:

```bash
pg_restore -h "$DB_HOST" -U "$DB_USERNAME" -d gestgo_restore_test --clean --if-exists arquivo.dump
```

## 3. MinIO / objetos

- Preferir replicação ou sync para bucket secundário (outra região/conta).
- Após incidente: restaurar objetos críticos (PDFs de protocolo, anexos) antes de reabrir uploads.
- Fontes de **importação de fichas** têm retenção curta (`form-imports:purge-expired`); não dependa delas como arquivo clínico permanente.

## 4. Jobs que apoiam retenção LGPD

Agendados em `routes/console.php`:

- `people:purge-inactive`
- `protocols:apply-retention`
- `form-imports:purge-expired`
- `platform:health-check` (alerta de infra)

## 5. Checklist pós-restauração

- [ ] App sobe; login owner funciona
- [ ] `php artisan migrate:status` coerente
- [ ] Download de 1 PDF de protocolo recente
- [ ] Webhook Asaas (sandbox) responde
- [ ] Filas Supervisor processam job de teste
- [ ] Registrar horário e responsável no log de incidente
