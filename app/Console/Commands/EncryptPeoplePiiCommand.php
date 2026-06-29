<?php

namespace App\Console\Commands;

use App\Models\Person;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class EncryptPeoplePiiCommand extends Command
{
    protected $signature = 'people:encrypt-pii
                            {--dry-run : Apenas lista quantos registros seriam atualizados}
                            {--chunk=100 : Tamanho do lote}';

    protected $description = 'Criptografa PII legada em texto claro na tabela people (idempotente).';

    /** @var list<string> */
    private array $encryptedFields = [
        'cpf',
        'email',
        'phone',
        'phone_alt',
        'rg',
        'address',
        'neighborhood',
        'city',
        'cep',
        'health_plan_card_number',
    ];

    public function handle(): int
    {
        $dryRun = (bool) $this->option('dry-run');
        $chunkSize = max(10, (int) $this->option('chunk'));
        $pending = 0;
        $updated = 0;

        Person::withoutGlobalScopes()
            ->orderBy('id')
            ->chunkById($chunkSize, function ($people) use ($dryRun, &$pending, &$updated): void {
                foreach ($people as $person) {
                    $raw = DB::table('people')->where('id', $person->id)->first();
                    if (! $raw) {
                        continue;
                    }

                    $fieldsToEncrypt = [];
                    foreach ($this->encryptedFields as $field) {
                        $value = $raw->{$field} ?? null;
                        if ($value !== null && $value !== '' && ! $this->isLaravelEncrypted((string) $value)) {
                            $fieldsToEncrypt[] = $field;
                        }
                    }

                    if ($fieldsToEncrypt === []) {
                        continue;
                    }

                    $pending++;

                    if ($dryRun) {
                        continue;
                    }

                    foreach ($fieldsToEncrypt as $field) {
                        $person->{$field} = $raw->{$field};
                    }
                    $person->saveQuietly();
                    $updated++;
                }
            });

        if ($dryRun) {
            $this->info("Registros com PII em texto claro: {$pending} (dry-run, nada alterado).");

            return self::SUCCESS;
        }

        $this->info("PII criptografada em {$updated} registro(s).");

        return self::SUCCESS;
    }

    private function isLaravelEncrypted(string $value): bool
    {
        $decoded = base64_decode($value, true);
        if ($decoded === false) {
            return false;
        }

        $json = json_decode($decoded, true);

        return is_array($json)
            && isset($json['iv'], $json['value'], $json['mac']);
    }
}
