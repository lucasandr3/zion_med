<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('organizations', function (Blueprint $table) {
            $table->string('form_public_theme')->nullable()->after('accent_hex');
            $table->string('form_accent_hex', 7)->nullable()->after('form_public_theme');
            $table->boolean('hide_platform_branding')->default(false)->after('form_accent_hex');
        });

        DB::table('organizations')->orderBy('id')->chunk(100, function ($rows): void {
            foreach ($rows as $row) {
                $extra = $row->link_bio_extra;
                if ($extra === null || $extra === '') {
                    continue;
                }
                $decoded = is_string($extra) ? json_decode($extra, true) : (array) $extra;
                if (! is_array($decoded) || empty($decoded['hide_platform_branding'])) {
                    continue;
                }
                DB::table('organizations')
                    ->where('id', $row->id)
                    ->update(['hide_platform_branding' => true]);
            }
        });
    }

    public function down(): void
    {
        Schema::table('organizations', function (Blueprint $table) {
            $table->dropColumn(['form_public_theme', 'form_accent_hex', 'hide_platform_branding']);
        });
    }
};
