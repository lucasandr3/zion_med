<?php

declare(strict_types=1);

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Artisan;

/**
 * Wrapper de deploy: migration (opcional) + provisionamento dos documentos Stefanie Lino.
 *
 * Produção:
 *   php artisan gestgo:provision-stefanie-lino --organization=123 --migrate --force
 *   php artisan gestgo:provision-stefanie-lino --email=cliente@email.com --migrate --force
 *   php artisan gestgo:provision-stefanie-lino --list
 */
class ProvisionStefanieLinoCommand extends Command
{
    protected $signature = 'gestgo:provision-stefanie-lino
                            {--organization= : ID da organização}
                            {--slug= : Slug da organização}
                            {--email= : E-mail de usuário da conta}
                            {--contact-email= : contact_email / notification_email}
                            {--list : Lista organizações candidatas}
                            {--migrate : Roda php artisan migrate --force antes}
                            {--dry-run : Só simula o seed}
                            {--force : Sem prompt em produção}';

    protected $description = 'Produção: migra (opcional) e provisiona documentos Stefanie Lino na conta informada';

    public function handle(): int
    {
        if ((bool) $this->option('list')) {
            return Artisan::call('templates:seed-stefanie-lino', [
                '--list' => true,
            ], $this->output);
        }

        if ((bool) $this->option('migrate')) {
            $this->info('Rodando migrations…');
            $migrateExit = Artisan::call('migrate', [
                '--force' => true,
            ], $this->output);

            if ($migrateExit !== 0) {
                $this->error('Migrations falharam.');

                return self::FAILURE;
            }
            $this->newLine();
        }

        $params = array_filter([
            '--organization' => $this->option('organization'),
            '--slug' => $this->option('slug'),
            '--email' => $this->option('email'),
            '--contact-email' => $this->option('contact-email'),
            '--dry-run' => $this->option('dry-run') ? true : null,
            '--force' => $this->option('force') ? true : null,
        ], static fn ($v) => $v !== null && $v !== '');

        return Artisan::call('templates:seed-stefanie-lino', $params, $this->output);
    }
}
