<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('submission_signatures', function (Blueprint $table) {
            $table->string('signed_name', 255)->nullable()->after('field_key');
            $table->string('signed_ip', 45)->nullable()->after('signed_name');
            $table->string('signed_user_agent', 512)->nullable()->after('signed_ip');
            $table->string('signed_hash', 64)->nullable()->after('signed_user_agent');
            $table->timestamp('signed_at')->nullable()->after('signed_hash');
        });
    }

    public function down(): void
    {
        Schema::table('submission_signatures', function (Blueprint $table) {
            $table->dropColumn([
                'signed_name',
                'signed_ip',
                'signed_user_agent',
                'signed_hash',
                'signed_at',
            ]);
        });
    }
};
