<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('organizations', function (Blueprint $table) {
            $table->string('evolution_go_instance_name', 128)->nullable()->after('signing_security_level');
            $table->string('evolution_go_remote_id', 64)->nullable()->after('evolution_go_instance_name');
            $table->text('evolution_go_instance_token')->nullable()->after('evolution_go_remote_id');
        });
    }

    public function down(): void
    {
        Schema::table('organizations', function (Blueprint $table) {
            $table->dropColumn([
                'evolution_go_instance_name',
                'evolution_go_remote_id',
                'evolution_go_instance_token',
            ]);
        });
    }
};
