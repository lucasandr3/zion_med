# Supervisor na VPS (sem Docker) — queue + schedule

Guia para rodar **fila** e **agendador** do `zion_med` em VPS (Ubuntu/Debian), **sem Docker**.

Configs prontas nesta pasta:

| Arquivo | Destino na VPS |
|---------|----------------|
| [`supervisor/zion-med-queue.conf`](supervisor/zion-med-queue.conf) | `/etc/supervisor/conf.d/zion-med-queue.conf` |
| [`supervisor/zion-med-scheduler.conf`](supervisor/zion-med-scheduler.conf) | `/etc/supervisor/conf.d/zion-med-scheduler.conf` |

| Processo | Comando | Para quê |
|----------|---------|----------|
| `zion-med-queue` | `php artisan queue:work …` | Jobs (PDF, e-mail, webhook, auditoria) |
| `zion-med-scheduler` | `php artisan schedule:work` | Tarefas de `routes/console.php` |

**Redis não é obrigatório.** Use no `.env`:

```env
QUEUE_CONNECTION=database
CACHE_STORE=database
```

---

## 1. Pré-requisitos

- API Laravel já deployada na VPS (ex.: `/var/www/zion_med`)
- PHP CLI instalado (8.2+ / 8.4)
- Extensões Laravel ok (`pdo`, `mbstring`, etc.)
- `.env` de produção com banco acessível
- Migrations rodadas (`php artisan migrate --force`)
- Usuário do web server (geralmente `www-data`) com permissão em `storage/` e `bootstrap/cache`

Ajuste o caminho do projeto nos `.conf` se for diferente de `/var/www/zion_med`.  
Confira o binário do PHP:

```bash
which php
php -v
# se for php8.4: troque command= para /usr/bin/php8.4 nos .conf
```

---

## 2. Instalar o Supervisor

```bash
sudo apt update
sudo apt install -y supervisor
sudo systemctl enable supervisor
sudo systemctl start supervisor
sudo systemctl status supervisor
```

---

## 3. Copiar as configs

No servidor, a partir do clone do repositório (ou copie os arquivos manualmente):

```bash
# ajuste o caminho do repo se necessário
sudo cp /var/www/zion_med/docs/supervisor/zion-med-queue.conf /etc/supervisor/conf.d/
sudo cp /var/www/zion_med/docs/supervisor/zion-med-scheduler.conf /etc/supervisor/conf.d/
```

Edite se o path ou o usuário forem outros:

```bash
sudo nano /etc/supervisor/conf.d/zion-med-queue.conf
sudo nano /etc/supervisor/conf.d/zion-med-scheduler.conf
```

Pontos a conferir em cada arquivo:

- `directory=` → raiz do Laravel
- `command=` → path real do `php`
- `user=` → `www-data` (ou o user do nginx/apache)
- `stdout_logfile=` → dentro de `storage/logs/`

Garanta que o log directory existe e é gravável:

```bash
sudo mkdir -p /var/www/zion_med/storage/logs
sudo chown -R www-data:www-data /var/www/zion_med/storage /var/www/zion_med/bootstrap/cache
```

---

## 4. Ativar no Supervisor

```bash
sudo supervisorctl reread
sudo supervisorctl update
sudo supervisorctl status
```

Esperado:

```text
zion-med-queue       RUNNING
zion-med-scheduler   RUNNING
```

Se aparecer `FATAL` / `EXITED`, veja o log:

```bash
sudo supervisorctl tail zion-med-queue
sudo supervisorctl tail zion-med-scheduler
# ou
tail -f /var/www/zion_med/storage/logs/queue-worker.log
tail -f /var/www/zion_med/storage/logs/scheduler.log
```

---

## 5. Variáveis de ambiente (.env)

```env
APP_ENV=production
APP_DEBUG=false
QUEUE_CONNECTION=database
CACHE_STORE=database
```

Depois de alterar `.env`:

```bash
cd /var/www/zion_med
php artisan config:cache
sudo supervisorctl restart zion-med-queue
sudo supervisorctl restart zion-med-scheduler
```

---

## 6. Comandos úteis no dia a dia

```bash
sudo supervisorctl status
sudo supervisorctl restart zion-med-queue
sudo supervisorctl restart zion-med-scheduler
sudo supervisorctl restart all
sudo supervisorctl stop zion-med-queue
sudo supervisorctl start zion-med-queue
```

Após deploy de código PHP:

```bash
cd /var/www/zion_med
php artisan queue:restart
# ou
sudo supervisorctl restart zion-med-queue
```

Listar agendamentos:

```bash
php artisan schedule:list
```

---

## 7. Troubleshooting

| Sintoma | Causa comum | Ação |
|---------|-------------|------|
| `FATAL` ao iniciar | Path/`php`/user errados | Conferir `directory`, `command`, `user` |
| Jobs não processam | `QUEUE_CONNECTION=sync` ou worker parado | `.env` + `supervisorctl status` |
| `Permission denied` | `www-data` sem escrita em `storage` | `chown` / `chmod` em `storage` e `bootstrap/cache` |
| Schedule não roda | processo parado | `supervisorctl start zion-med-scheduler` |
| Config não carrega | arquivo fora de `conf.d` | `reread` + `update` |

Não use cron **e** `schedule:work` ao mesmo tempo (duplicaria tarefas). Escolha Supervisor **ou** cron:

```cron
# alternativa SEM o programa zion-med-scheduler:
* * * * * cd /var/www/zion_med && php artisan schedule:run >> /dev/null 2>&1
```

Com as configs desta pasta, o cron **não** é necessário.

---

## 8. Checklist

- [ ] `supervisor` instalado e `systemctl` ativo
- [ ] `.conf` em `/etc/supervisor/conf.d/`
- [ ] `directory` e `php` corretos
- [ ] `QUEUE_CONNECTION=database` (sem Redis, se for o caso)
- [ ] `supervisorctl status` → ambos `RUNNING`
- [ ] `php artisan schedule:list` lista os commands
- [ ] Teste: disparar um job / enviar formulário que gera PDF
