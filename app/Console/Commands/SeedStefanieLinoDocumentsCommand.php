<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Enums\Role;
use App\Models\Organization;
use App\Models\Tenant;
use App\Models\User;
use App\Services\ClientDocumentPacks\StefanieLinoDocumentProvisioner;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\App;

class SeedStefanieLinoDocumentsCommand extends Command
{
    protected $signature = 'templates:seed-stefanie-lino
                            {--organization= : ID numérico da organização}
                            {--slug= : Slug da organização}
                            {--email= : E-mail de um usuário da conta}
                            {--contact-email= : contact_email ou notification_email da organização}
                            {--list : Lista organizações candidatas (Stefanie / odontologia)}
                            {--dry-run : Simula sem gravar}
                            {--force : Em produção, confirma sem prompt interativo}
                            {--create-local-demo : Cria conta demo local (proibido em produção)}';

    protected $description = 'Remove os outros templates da organização e provisiona só os 9 documentos da Dra. Stefanie Lino (+ PDF original)';

    public function handle(StefanieLinoDocumentProvisioner $provisioner): int
    {
        if ((bool) $this->option('list')) {
            return $this->listCandidates();
        }

        if ((bool) $this->option('create-local-demo')) {
            if (App::environment('production')) {
                $this->error('--create-local-demo é proibido em produção.');

                return self::FAILURE;
            }

            $organization = $this->createLocalDemoOrganization();
            $this->info("Conta demo: #{$organization->id} {$organization->name} ({$organization->slug})");
        } else {
            $organization = $this->resolveOrganization();
            if ($organization === null) {
                return self::FAILURE;
            }
        }

        $this->line(sprintf(
            'Alvo: #%d · %s · slug=%s · e-mail=%s',
            $organization->id,
            $organization->name,
            $organization->slug ?: '—',
            $organization->contact_email ?: ($organization->notification_email ?: '—'),
        ));

        if (App::environment('production') && ! (bool) $this->option('dry-run') && ! (bool) $this->option('force')) {
            if (! $this->confirm('Confirma provisionar os documentos Stefanie Lino nesta organização de PRODUÇÃO?', false)) {
                $this->warn('Cancelado.');

                return self::SUCCESS;
            }
        }

        $owner = User::withoutGlobalScopes()
            ->where('organization_id', $organization->id)
            ->orderByRaw("CASE WHEN role = ? THEN 0 ELSE 1 END", [Role::Owner->value])
            ->orderBy('id')
            ->first();

        try {
            $result = $provisioner->provision(
                $organization,
                $owner,
                (bool) $this->option('dry-run'),
            );
        } catch (\Throwable $e) {
            $this->error($e->getMessage());

            return self::FAILURE;
        }

        foreach ($result['items'] as $line) {
            $this->line($line);
        }

        $this->newLine();
        $this->info(sprintf(
            'Removidos: %d | Criados: %d | Atualizados: %d | PDFs anexados: %d | Pulados: %d',
            $result['removed'],
            $result['created'],
            $result['updated'],
            $result['attached'],
            $result['skipped'],
        ));

        if (! (bool) $this->option('dry-run')) {
            $this->comment('A organização fica só com os 9 documentos do pacote Stefanie Lino.');
            $this->comment('Próximo: no painel, abra Templates → Editar para conferir o PDF original e publique os links.');
        }

        return self::SUCCESS;
    }

    private function resolveOrganization(): ?Organization
    {
        $id = trim((string) $this->option('organization'));
        $slug = trim((string) $this->option('slug'));
        $email = strtolower(trim((string) $this->option('email')));
        $contactEmail = strtolower(trim((string) $this->option('contact-email')));

        $filters = array_filter([$id, $slug, $email, $contactEmail], static fn ($v) => $v !== '');
        if ($filters === []) {
            $this->error('Informe a conta com --organization=ID, --slug=, --email= ou --contact-email=.');
            $this->line('Dica: php artisan templates:seed-stefanie-lino --list');

            return null;
        }

        if ($id !== '') {
            $organization = Organization::query()->find((int) $id);
            if ($organization === null) {
                $this->error("Organização #{$id} não encontrada.");

                return null;
            }

            return $organization;
        }

        if ($slug !== '') {
            $organization = Organization::query()->where('slug', $slug)->first();
            if ($organization === null) {
                $this->error("Organização com slug \"{$slug}\" não encontrada.");

                return null;
            }

            return $organization;
        }

        if ($email !== '') {
            $user = User::withoutGlobalScopes()->whereRaw('LOWER(email) = ?', [$email])->first();
            if ($user === null || ! $user->organization_id) {
                $this->error("Nenhum usuário de clínica com e-mail \"{$email}\".");

                return null;
            }

            $organization = Organization::query()->find((int) $user->organization_id);
            if ($organization === null) {
                $this->error("Usuário encontrado, mas organização #{$user->organization_id} não existe.");

                return null;
            }

            return $organization;
        }

        $organization = Organization::query()
            ->where(function ($q) use ($contactEmail) {
                $q->whereRaw('LOWER(contact_email) = ?', [$contactEmail])
                    ->orWhereRaw('LOWER(notification_email) = ?', [$contactEmail]);
            })
            ->orderBy('id')
            ->get();

        if ($organization->isEmpty()) {
            $this->error("Nenhuma organização com contact/notification e-mail \"{$contactEmail}\".");

            return null;
        }

        if ($organization->count() > 1) {
            $this->error('Mais de uma organização bateu no e-mail. Use --organization=ID:');
            foreach ($organization as $org) {
                $this->line("  #{$org->id} · {$org->name} · {$org->slug}");
            }

            return null;
        }

        return $organization->first();
    }

    private function listCandidates(): int
    {
        $orgs = Organization::query()
            ->where(function ($q) {
                $q->where('name', 'like', '%Stefanie%')
                    ->orWhere('name', 'like', '%stefanie%')
                    ->orWhere('slug', 'like', '%stefanie%')
                    ->orWhere('contact_email', 'like', '%stefanie%')
                    ->orWhere('notification_email', 'like', '%stefanie%')
                    ->orWhere('niche', 'odontologia');
            })
            ->orderBy('id')
            ->limit(50)
            ->get(['id', 'name', 'slug', 'niche', 'contact_email', 'notification_email']);

        if ($orgs->isEmpty()) {
            $this->warn('Nenhuma candidata encontrada. Busque pelo e-mail do login no painel plataforma e use --organization=ID.');

            return self::SUCCESS;
        }

        $this->table(
            ['ID', 'Nome', 'Slug', 'Nicho', 'Contact', 'Notification'],
            $orgs->map(static fn (Organization $o) => [
                $o->id,
                $o->name,
                $o->slug,
                $o->niche,
                $o->contact_email,
                $o->notification_email,
            ])->all(),
        );

        $this->comment('Ex.: php artisan templates:seed-stefanie-lino --organization='.$orgs->first()->id.' --force');

        return self::SUCCESS;
    }

    private function createLocalDemoOrganization(): Organization
    {
        $tenant = Tenant::query()->firstOrCreate(
            ['slug' => 'stefanie-lino'],
            ['name' => 'Dra. Stefanie Lino'],
        );

        $organization = Organization::query()->firstOrCreate(
            ['slug' => 'stefanie-lino-odontologia'],
            [
                'tenant_id' => $tenant->id,
                'name' => 'Dra. Stefanie Lino — Odontologia Estética',
                'niche' => 'odontologia',
                'notification_email' => 'stefanie@gestgo.test',
                'contact_email' => 'stefaniemlino@yahoo.com.br',
                'phone' => '(11) 99566-0934',
                'address' => 'Rua Dr. Angelo Vita, 256 — Tatuapé — São Paulo/SP',
            ],
        );

        $organization->update([
            'tenant_id' => $tenant->id,
            'trial_ends_at' => now()->setDate(2050, 12, 31)->endOfDay(),
            'subscription_status' => 'trial',
            'billing_status' => 'ok',
            'hide_platform_branding' => true,
            'niche' => 'odontologia',
        ]);

        $user = User::withoutGlobalScopes()->firstOrCreate(
            ['email' => 'stefanie@gestgo.test'],
            [
                'organization_id' => $organization->id,
                'name' => 'Dra. Stefanie Lino',
                'password' => '12345678',
                'role' => Role::Owner->value,
                'active' => true,
            ],
        );

        $user->forceFill([
            'organization_id' => $organization->id,
            'email_verified_at' => now(),
            'active' => true,
            'role' => Role::Owner->value,
        ])->save();

        return $organization->fresh();
    }
}
