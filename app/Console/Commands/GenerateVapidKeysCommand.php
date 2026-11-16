<?php

namespace App\Console\Commands;

use App\Services\WebPushConfigService;
use Illuminate\Console\Command;

class GenerateVapidKeysCommand extends Command
{
    protected $signature = 'webpush:vapid {--save : Grava as chaves em platform_settings}';

    protected $description = 'Gera par de chaves VAPID para Web Push';

    public function handle(WebPushConfigService $webPush): int
    {
        try {
            if ($this->option('save')) {
                $keys = $webPush->generateAndStore();
                $this->info('Chaves VAPID geradas e salvas em platform_settings.');
            } else {
                $keys = $webPush->createVapidKeys();
                $this->line('Adicione ao .env (ou use --save para gravar na plataforma):');
            }
        } catch (\Throwable $e) {
            $this->error($e->getMessage());

            return self::FAILURE;
        }

        $this->newLine();
        $this->line('VAPID_SUBJECT='.$webPush->getSubject());
        $this->line('VAPID_PUBLIC_KEY='.$keys['publicKey']);
        $this->line('VAPID_PRIVATE_KEY='.$keys['privateKey']);
        $this->newLine();

        return self::SUCCESS;
    }
}
