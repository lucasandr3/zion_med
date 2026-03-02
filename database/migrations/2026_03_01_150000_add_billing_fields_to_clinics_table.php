<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('clinics', function (Blueprint $table) {
            $table->string('asaas_customer_id')->nullable()->after('dark_mode');
            $table->string('billing_email')->nullable()->after('asaas_customer_id');
            $table->string('billing_name')->nullable()->after('billing_email');
            $table->string('billing_document')->nullable()->after('billing_name');
            $table->string('plan_key')->nullable()->after('billing_document'); // core, executive, enterprise
            $table->dateTime('trial_ends_at')->nullable()->after('plan_key');
            $table->dateTime('grace_ends_at')->nullable()->after('trial_ends_at');
            $table->string('subscription_status')->nullable()->after('grace_ends_at'); // inactive|trial|active|past_due|canceled
            $table->string('billing_status')->nullable()->after('subscription_status'); // ok|attention|blocked
        });
    }

    public function down(): void
    {
        Schema::table('clinics', function (Blueprint $table) {
            $table->dropColumn([
                'asaas_customer_id',
                'billing_email',
                'billing_name',
                'billing_document',
                'plan_key',
                'trial_ends_at',
                'grace_ends_at',
                'subscription_status',
                'billing_status',
            ]);
        });
    }
};
