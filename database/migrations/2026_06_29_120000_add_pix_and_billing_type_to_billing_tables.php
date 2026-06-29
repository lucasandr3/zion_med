<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('subscriptions', function (Blueprint $table) {
            $table->string('billing_type', 20)->default('BOLETO')->after('plan_key');
        });

        Schema::table('payments', function (Blueprint $table) {
            $table->text('pix_qr_encoded_image')->nullable()->after('bank_slip_url');
            $table->text('pix_copy_paste')->nullable()->after('pix_qr_encoded_image');
        });
    }

    public function down(): void
    {
        Schema::table('payments', function (Blueprint $table) {
            $table->dropColumn(['pix_qr_encoded_image', 'pix_copy_paste']);
        });

        Schema::table('subscriptions', function (Blueprint $table) {
            $table->dropColumn('billing_type');
        });
    }
};
