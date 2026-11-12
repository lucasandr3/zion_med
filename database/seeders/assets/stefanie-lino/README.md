# PDFs originais — Dra. Stefanie Lino

Usados pelo comando de provisionamento (produção e local).

## Produção

1. Confirme que o deploy levou este diretório e a migration `source_document_*`.
2. Identifique a conta:

```bash
php artisan templates:seed-stefanie-lino --list
```

3. Rode migration + documentos na organização certa:

```bash
php artisan gestgo:provision-stefanie-lino --organization=ID --migrate --force
```

Alternativas de identificação:

```bash
php artisan gestgo:provision-stefanie-lino --email=login-da-dra@email.com --migrate --force
php artisan gestgo:provision-stefanie-lino --contact-email=stefaniemlino@yahoo.com.br --migrate --force
php artisan gestgo:provision-stefanie-lino --slug=slug-da-org --migrate --force
```

Dry-run:

```bash
php artisan templates:seed-stefanie-lino --organization=ID --dry-run
```

## Local (demo)

```bash
php artisan templates:seed-stefanie-lino --create-local-demo
# ou
php artisan db:seed --class=StefanieLinoDocumentSeeder
```
