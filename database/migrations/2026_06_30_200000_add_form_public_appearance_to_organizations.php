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
            $table->string('form_public_theme', 50)->nullable()->after('accent_hex');
            $table->string('form_accent_hex', 7)->nullable()->after('form_public_theme');
            $table->boolean('hide_platform_branding')->default(false)->after('form_accent_hex');
        });

        DB::table('organizations')
            ->whereNotNull('link_bio_extra')
            ->orderBy('id')
            ->each(function ($row): void {
                $extra = json_decode((string) $row->link_bio_extra, true);
                if (! is_array($extra) || empty($extra['hide_platform_branding'])) {
                    return;
                }
                DB::table('organizations')
                    ->where('id', $row->id)
                    ->update(['hide_platform_branding' => true]);
            });
    }

    public function down(): void
    {
        Schema::table('organizations', function (Blueprint $table) {
            $table->dropColumn(['form_public_theme', 'form_accent_hex', 'hide_platform_branding']);
        });
    }
};
