<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $trialDays = (int) (config('asaas.trial_days') ?? env('ASAAS_TRIAL_DAYS', 14));
        $endsAt = now()->addDays($trialDays)->format('Y-m-d H:i:s');

        DB::table('clinics')
            ->whereNull('subscription_status')
            ->update([
                'trial_ends_at' => $endsAt,
                'subscription_status' => 'trial',
                'billing_status' => 'ok',
            ]);
    }

    public function down(): void
    {
        // Não reverter para não afetar uso em produção
    }
};
