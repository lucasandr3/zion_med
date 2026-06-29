<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    private const OLD_UNIQUE = 'landing_site_visits_ip_hash_visit_date_unique';

    private const NEW_UNIQUE = 'landing_site_visits_ip_date_path_unique';

    public function up(): void
    {
        if (! Schema::hasTable('landing_site_visits')) {
            return;
        }

        $hasOldUnique = $this->indexExists(self::OLD_UNIQUE);
        $hasNewUnique = $this->indexExists(self::NEW_UNIQUE);

        if ($hasNewUnique && ! $hasOldUnique) {
            return;
        }

        Schema::table('landing_site_visits', function (Blueprint $table) use ($hasOldUnique, $hasNewUnique) {
            if ($hasOldUnique) {
                $table->dropUnique(['ip_hash', 'visit_date']);
            }

            if (! $hasNewUnique) {
                $table->unique(['ip_hash', 'visit_date', 'path'], self::NEW_UNIQUE);
            }
        });
    }

    public function down(): void
    {
        if (! Schema::hasTable('landing_site_visits')) {
            return;
        }

        $hasOldUnique = $this->indexExists(self::OLD_UNIQUE);
        $hasNewUnique = $this->indexExists(self::NEW_UNIQUE);

        Schema::table('landing_site_visits', function (Blueprint $table) use ($hasOldUnique, $hasNewUnique) {
            if ($hasNewUnique) {
                $table->dropUnique(self::NEW_UNIQUE);
            }

            if (! $hasOldUnique) {
                $table->unique(['ip_hash', 'visit_date']);
            }
        });
    }

    private function indexExists(string $indexName): bool
    {
        $driver = Schema::getConnection()->getDriverName();

        return match ($driver) {
            'pgsql' => (bool) DB::selectOne(
                'SELECT 1 FROM pg_indexes WHERE tablename = ? AND indexname = ? LIMIT 1',
                ['landing_site_visits', $indexName]
            ),
            'sqlite' => (bool) DB::selectOne(
                "SELECT 1 FROM sqlite_master WHERE type = 'index' AND tbl_name = ? AND name = ? LIMIT 1",
                ['landing_site_visits', $indexName]
            ),
            'mysql' => (bool) DB::selectOne(
                'SELECT 1 FROM information_schema.statistics WHERE table_schema = DATABASE() AND table_name = ? AND index_name = ? LIMIT 1',
                ['landing_site_visits', $indexName]
            ),
            default => false,
        };
    }
};
