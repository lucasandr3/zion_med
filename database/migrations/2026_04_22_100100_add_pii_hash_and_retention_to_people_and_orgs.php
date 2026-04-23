<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('people', function (Blueprint $table) {
            $table->string('cpf_hash', 64)->nullable()->after('cpf');
            $table->string('email_hash', 64)->nullable()->after('email');
            $table->index('cpf_hash');
            $table->index('email_hash');
        });

        $this->widenPeoplePiiColumnsForEncryption();

        Schema::table('organizations', function (Blueprint $table) {
            $table->unsignedTinyInteger('data_retention_years')->nullable()->after('signing_security_level');
        });

        $key = (string) config('app.key');
        if ($key !== '') {
            DB::table('people')->orderBy('id')->chunkById(200, function ($rows) use ($key): void {
                foreach ($rows as $row) {
                    $cpfHash = null;
                    $emailHash = null;
                    if (! empty($row->cpf) && is_string($row->cpf)) {
                        $d = preg_replace('/\D+/', '', $row->cpf) ?? '';
                        if (strlen($d) === 11) {
                            $cpfHash = hash_hmac('sha256', $d, $key);
                        }
                    }
                    if (! empty($row->email) && is_string($row->email)) {
                        $emailHash = hash_hmac('sha256', strtolower(trim($row->email)), $key);
                    }
                    if ($cpfHash !== null || $emailHash !== null) {
                        DB::table('people')->where('id', $row->id)->update(array_filter([
                            'cpf_hash' => $cpfHash,
                            'email_hash' => $emailHash,
                        ], fn ($v) => $v !== null));
                    }
                }
            });
        }

        DB::table('people')->select(['id', 'cpf', 'email', 'phone'])->orderBy('id')->chunkById(200, function ($rows): void {
            foreach ($rows as $row) {
                $updates = [];
                foreach (['cpf', 'email', 'phone'] as $col) {
                    $val = $row->{$col};
                    if (! is_string($val) || $val === '') {
                        continue;
                    }
                    if (str_starts_with($val, 'eyJ')) {
                        continue;
                    }
                    try {
                        $updates[$col] = Crypt::encryptString($val);
                    } catch (\Throwable) {
                        // ignora valor inválido
                    }
                }
                if ($updates !== []) {
                    DB::table('people')->where('id', $row->id)->update($updates);
                }
            }
        });
    }

    /**
     * Ciphertext do Laravel (encryptString) é muito maior que varchar(14|50|255).
     */
    private function widenPeoplePiiColumnsForEncryption(): void
    {
        $driver = Schema::getConnection()->getDriverName();

        if ($driver === 'pgsql') {
            DB::statement('ALTER TABLE people ALTER COLUMN cpf TYPE TEXT');
            DB::statement('ALTER TABLE people ALTER COLUMN email TYPE TEXT');
            DB::statement('ALTER TABLE people ALTER COLUMN phone TYPE TEXT');

            return;
        }

        if (in_array($driver, ['mysql', 'mariadb'], true)) {
            DB::statement('ALTER TABLE people MODIFY cpf TEXT NULL');
            DB::statement('ALTER TABLE people MODIFY email TEXT NULL');
            DB::statement('ALTER TABLE people MODIFY phone TEXT NULL');
        }

        // sqlite: afinidade TEXT já comporta strings longas; colunas declaradas como VARCHAR não truncam no SQLite.
    }

    public function down(): void
    {
        Schema::table('people', function (Blueprint $table) {
            $table->dropIndex(['cpf_hash']);
            $table->dropIndex(['email_hash']);
            $table->dropColumn(['cpf_hash', 'email_hash']);
        });
        Schema::table('organizations', function (Blueprint $table) {
            $table->dropColumn('data_retention_years');
        });
    }
};
