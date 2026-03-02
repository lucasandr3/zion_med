<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('clinics', function (Blueprint $table) {
            $table->boolean('whatsapp_notifications_enabled')->default(false)->after('billing_status');
            $table->boolean('whatsapp_notify_cobranca')->default(true)->after('whatsapp_notifications_enabled');
            $table->boolean('whatsapp_notify_faturas_boleto')->default(true)->after('whatsapp_notify_cobranca');
            $table->boolean('whatsapp_notify_avisos')->default(true)->after('whatsapp_notify_faturas_boleto');
        });
    }

    public function down(): void
    {
        Schema::table('clinics', function (Blueprint $table) {
            $table->dropColumn([
                'whatsapp_notifications_enabled',
                'whatsapp_notify_cobranca',
                'whatsapp_notify_faturas_boleto',
                'whatsapp_notify_avisos',
            ]);
        });
    }
};
